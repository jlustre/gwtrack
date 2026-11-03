<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\Team;
use App\Models\User;
use App\Services\CfmManagementService;
use App\Services\NewMemberValidatedTaskService;
use App\Support\LocationOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'role', 'rank_id', 'team_id', 'status', 'trashed']);

        $users = User::query()
            ->with(['rank', 'team', 'sponsor', 'roles'])
            ->when($filters['trashed'] ?? null, fn ($query, $trashed) => match ($trashed) {
                'with' => $query->withTrashed(),
                'only' => $query->onlyTrashed(),
                default => $query,
            })
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->when($filters['rank_id'] ?? null, fn ($query, string $rankId) => $query->where('rank_id', $rankId))
            ->when($filters['team_id'] ?? null, fn ($query, string $teamId) => $query->where('team_id', $teamId))
            ->when(($filters['status'] ?? null) !== null && ($filters['status'] ?? '') !== '', fn ($query) => $query->where('is_active', (bool) $filters['status']))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
            'ranks' => Rank::orderBy('sort_order')->get(),
            'teams' => Team::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'rank_id' => $validated['rank_id'] ?? null,
            'team_id' => $validated['team_id'] ?? null,
            'sponsor_id' => $validated['sponsor_id'] ?? null,
            'mentor_id' => $validated['mentor_id'] ?? null,
            'is_active' => (bool) $validated['is_active'],
            'joined_at' => $validated['joined_at'] ?? now(),
            'is_online' => false,
        ]);

        $user->syncRoles([$validated['role']]);
        $this->syncProfile($user, $validated);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        $user->load(['rank', 'team', 'sponsor', 'mentor', 'roles', 'profile', 'messagingSuspendedBy']);

        return view('admin.users.edit', [
            'managedUser' => $user,
            ...$this->formOptions($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, $user);

        if ($request->user()->is($user) && ! (bool) $validated['is_active']) {
            return back()
                ->withInput()
                ->withErrors(['is_active' => 'You cannot deactivate your own account.']);
        }

        if ($roleError = $this->roleChangeError($request->user(), $user, $validated['role'])) {
            return back()
                ->withInput()
                ->withErrors(['role' => $roleError]);
        }

        $emailChanged = $validated['email'] !== $user->email;

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'rank_id' => $validated['rank_id'] ?? null,
            'team_id' => $validated['team_id'] ?? null,
            'sponsor_id' => $validated['sponsor_id'] ?? null,
            'mentor_id' => $validated['mentor_id'] ?? null,
            'is_active' => (bool) $validated['is_active'],
            'joined_at' => $validated['joined_at'] ?? $user->joined_at,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (! empty($validated['password'])) {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
            ])->save();
        }

        $user->syncRoles([$validated['role']]);
        $this->syncProfile($user->fresh(), $validated);
        app(NewMemberValidatedTaskService::class)->completeCfmAssignmentTasksIfOpen($user->fresh());

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'user-updated');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->forceFill([
            'is_active' => false,
            'is_online' => false,
        ])->save();

        $user->delete();

        return redirect()
            ->route('admin.users.index', ['trashed' => 'with'])
            ->with('status', 'user-deleted');
    }

    public function restore(int $user): RedirectResponse
    {
        $managedUser = User::withTrashed()->findOrFail($user);
        $managedUser->restore();

        return redirect()
            ->route('admin.users.edit', $managedUser)
            ->with('status', 'user-restored');
    }

    public function suspendMessaging(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['messaging' => 'You cannot suspend your own messaging access.']);
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return back()->withErrors(['messaging' => 'Administrators cannot be suspended from messaging.']);
        }

        $validated = $request->validate([
            'messaging_suspension_reason' => ['required', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'messaging_suspended_at' => now(),
            'messaging_suspended_by' => $request->user()->id,
            'messaging_suspension_reason' => $validated['messaging_suspension_reason'],
        ])->save();

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'messaging-suspended');
    }

    public function restoreMessaging(Request $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'messaging_suspended_at' => null,
            'messaging_suspended_by' => null,
            'messaging_suspension_reason' => null,
        ])->save();

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'messaging-restored');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        $roleNames = Role::pluck('name')->all();
        $profileId = $user?->profile?->id;
        $isLicensed = $request->boolean('is_licensed_life_insurance');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', Rule::in($roleNames)],
            'rank_id' => [
                'nullable',
                'integer',
                Rule::exists('ranks', 'id')->where(function ($query) use ($user): void {
                    $query->whereNull('deleted_at')
                        ->where(function ($query) use ($user): void {
                            $query->where('is_active', true)
                                ->orWhere('id', $user?->rank_id ?? 0);
                        });
                }),
            ],
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')->whereNull('deleted_at')],
            'sponsor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at'), Rule::notIn(array_filter([$user?->id]))],
            'mentor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at'), Rule::notIn(array_filter([$user?->id]))],
            'is_active' => ['required', 'boolean'],
            'joined_at' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_province_id' => ['nullable', 'integer', 'exists:state_provinces,id'],
            'timezone_id' => ['nullable', 'integer', 'exists:timezones,id'],
            'best_contact_time' => ['nullable', 'string', Rule::in(array_keys(LocationOptions::contactTimes()))],
            'bio' => ['nullable', 'string', 'max:1000'],
            'license_number' => ['nullable', 'string', 'max:120'],
            'gw_associate_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('profiles', 'gw_associate_id')->ignore($profileId),
            ],
            'gw_invite_link' => [
                'nullable',
                'string',
                'max:2048',
                'url',
                Rule::unique('profiles', 'gw_invite_link')->ignore($profileId),
            ],
            'is_gw_active_associate' => ['nullable', 'boolean'],
            'recruited_at' => ['nullable', 'date'],
            'is_licensed_life_insurance' => ['nullable', 'boolean'],
            'insurance_licenses' => [
                Rule::requiredIf($isLicensed),
                'nullable',
                'array',
                'min:1',
            ],
            'insurance_licenses.*' => ['string', 'max:120'],
        ], [
            'gw_invite_link.url' => 'Enter a valid Greatway invite URL (including https://).',
            'gw_invite_link.unique' => 'This Greatway invite link is already in use by another member.',
            'gw_associate_id.unique' => 'This GW Associate ID is already in use by another member.',
        ]);

        $countryId = filled($validated['country_id'] ?? null) ? (int) $validated['country_id'] : null;
        $stateProvinceId = filled($validated['state_province_id'] ?? null) ? (int) $validated['state_province_id'] : null;

        if (! LocationOptions::isValidStateProvinceId($countryId, $stateProvinceId)) {
            throw ValidationException::withMessages([
                'state_province_id' => 'Select a valid province or state for the chosen country.',
            ]);
        }

        foreach ($validated['insurance_licenses'] ?? [] as $index => $key) {
            if (! is_string($key) || ! LocationOptions::isValidJurisdictionKey($key)) {
                throw ValidationException::withMessages([
                    "insurance_licenses.{$index}" => 'Each license must be a valid province or state in a supported country.',
                ]);
            }
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['is_licensed_life_insurance'] = $isLicensed;
        $validated['is_gw_active_associate'] = $request->boolean('is_gw_active_associate');
        $validated['gw_associate_id'] = filled($validated['gw_associate_id'] ?? null) ? $validated['gw_associate_id'] : null;
        $validated['gw_invite_link'] = filled($validated['gw_invite_link'] ?? null) ? $validated['gw_invite_link'] : null;
        $validated['insurance_licenses'] = $isLicensed
            ? LocationOptions::normalizeLicensedJurisdictionKeys($validated['insurance_licenses'] ?? [])
            : [];

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncProfile(User $user, array $validated): void
    {
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $validated['phone'] ?? null,
                'city' => $validated['city'] ?? null,
                'country_id' => $validated['country_id'] ?? null,
                'state_province_id' => $validated['state_province_id'] ?? null,
                'timezone_id' => $validated['timezone_id'] ?? null,
                'best_contact_time' => $validated['best_contact_time'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'license_number' => $validated['license_number'] ?? null,
                'gw_associate_id' => $validated['gw_associate_id'] ?? null,
                'gw_invite_link' => $validated['gw_invite_link'] ?? null,
                'is_gw_active_associate' => (bool) ($validated['is_gw_active_associate'] ?? false),
                'recruited_at' => $validated['recruited_at'] ?? null,
                'is_licensed_life_insurance' => (bool) ($validated['is_licensed_life_insurance'] ?? false),
                'insurance_licenses' => $validated['insurance_licenses'] ?? [],
            ],
        );

        if ($user->hasRole('certified-field-mentor')) {
            app(CfmManagementService::class)->syncLicensedJurisdictions(
                $user->fresh(),
                $validated['insurance_licenses'] ?? [],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?User $user = null): array
    {
        $memberOptions = User::query()
            ->when($user, fn ($query) => $query->whereKeyNot($user->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return [
            'roles' => Role::orderBy('name')->pluck('name'),
            'ranks' => Rank::query()
                ->where('is_active', true)
                ->when($user?->rank_id, fn ($query, $rankId) => $query->orWhereKey($rankId))
                ->orderBy('sort_order')
                ->get(),
            'teams' => Team::orderBy('name')->get(),
            'sponsors' => $memberOptions,
            'mentors' => $memberOptions,
            'locationOptions' => LocationOptions::forPortal(),
            'profile' => $user?->profile,
            'selectedRole' => $this->primaryRoleName($user),
        ];
    }

    private function primaryRoleName(?User $user): string
    {
        if (! $user) {
            return User::DEFAULT_ROLE;
        }

        $priority = [
            'system',
            'super-admin',
            'admin',
            'agency-owner',
            'team-leader',
            'certified-field-mentor',
            'trainer',
            'support-agent',
            'associate',
            User::DEFAULT_ROLE,
            'new-recruit',
        ];

        $roleNames = $user->getRoleNames();

        foreach ($priority as $role) {
            if ($roleNames->contains($role)) {
                return $role;
            }
        }

        return (string) ($roleNames->first() ?? User::DEFAULT_ROLE);
    }

    private function roleChangeError(User $actor, User $target, string $nextRole): ?string
    {
        $managementRoles = ['super-admin', 'admin', 'agency-owner'];
        $hadManagementAccess = $target->hasAnyRole($managementRoles);
        $keepsManagementAccess = in_array($nextRole, $managementRoles, true);

        if ($actor->is($target) && $hadManagementAccess && ! $keepsManagementAccess) {
            return 'You cannot remove your own user-management access.';
        }

        if ($target->hasRole('super-admin') && $nextRole !== 'super-admin') {
            $otherSuperAdmins = User::role('super-admin')
                ->whereKeyNot($target->id)
                ->count();

            if ($otherSuperAdmins === 0) {
                return 'You cannot demote the only Super Admin account.';
            }
        }

        return null;
    }
}
