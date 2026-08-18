<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\StoreMemberProductionEntryRequest;
use App\Http\Requests\UpdateProfileInsuranceLicensesRequest;
use App\Http\Requests\UpdateProfileInviteLinkRequest;
use App\Http\Requests\UpdateProfilePhotoRequest;
use App\Mail\InvitationLinkMail;
use App\Models\EmailTemplate;
use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Services\CfmManagementService;
use App\Services\DownlineHierarchyService;
use App\Services\MemberProductionService;
use App\Services\Members\MemberInvitationService;
use App\Services\NewMemberValidatedTaskService;
use App\Services\ProfileCompletionService;
use App\Services\ProfilePhotoService;
use App\Support\EmailTemplateTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfilePhotoService $profilePhotos,
        private readonly DownlineHierarchyService $downlineHierarchy,
        private readonly ProfileCompletionService $profileCompletion,
        private readonly MemberProductionService $memberProduction,
        private readonly NewMemberValidatedTaskService $validatedTasks,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        return $this->profilePage($request, $request->user(), isOwnProfile: true);
    }

    public function showMember(Request $request, User $user): View|RedirectResponse
    {
        abort_unless($this->downlineHierarchy->canViewMember($request->user(), $user), 403);

        return $this->profilePage($request, $user, isOwnProfile: false);
    }

    private function profilePage(Request $request, User $user, bool $isOwnProfile): View|RedirectResponse
    {
        if ($request->query('tab') === 'direct-recruits') {
            $redirectRoute = $isOwnProfile
                ? route('profile.edit', array_merge($request->except('tab'), ['tab' => 'recruits']))
                : route('team.member.profile', array_merge(['user' => $user], $request->except('tab'), ['tab' => 'recruits']));

            return Redirect::to($redirectRoute);
        }

        return view('profile.edit', [
            'memberUserId' => $isOwnProfile ? null : $user->id,
            'tab' => (string) $request->query('tab', 'profile'),
            'openInvitations' => $request->boolean('open_invitations'),
        ]);
    }

    public function storeProduction(StoreMemberProductionEntryRequest $request): RedirectResponse
    {
        $member = $request->user();
        $this->authorize('enterProduction', $member);

        $this->memberProduction->createForMember($member, $request->user(), $request->validated());

        return Redirect::route('profile.edit', ['tab' => 'annual-premium'])->with('production_feedback', [
            'type' => 'success',
            'message' => 'Production entry was recorded successfully.',
        ]);
    }

    public function storeMemberProduction(StoreMemberProductionEntryRequest $request, User $user): RedirectResponse
    {
        $this->authorize('enterProduction', $user);

        $this->memberProduction->createForMember($user, $request->user(), $request->validated());

        return Redirect::route('team.member.profile', ['user' => $user, 'tab' => 'annual-premium'])->with('production_feedback', [
            'type' => 'success',
            'message' => 'Production entry was recorded for '.$user->name.'.',
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $profileAttributes = $this->profileAttributesFromRequest($request, $validated);

        if ($profileAttributes !== []) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileAttributes,
            );
        }

        if ($request->hasFile('photo')) {
            $this->profilePhotos->update($user, $request->file('photo'));
        } elseif ($stagedPath = session('profile_completion_staged_photo_path')) {
            $this->profilePhotos->applyStagedPreview($user, $stagedPath);
        }

        $this->profilePhotos->clearStagedPreview($user);

        $this->validatedTasks->completeProfileTaskIfOpen($user->fresh(['profile']));

        $redirectRoute = $request->input('redirect_to') === 'dashboard'
            ? route('dashboard')
            : route('profile.edit', ['tab' => 'profile']);

        return Redirect::to($redirectRoute)->with('profile_feedback', [
            'type' => 'success',
            'message' => 'Your profile was updated successfully.',
        ]);
    }

    public function completionPhotoPreview(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $path = $this->profilePhotos->stagedPreviewPath($user);

        abort_unless($path !== null, 404);

        return response()->file(
            Storage::disk(ProfilePhotoService::DISK)->path($path),
            ['Cache-Control' => 'private, no-cache, no-store, must-revalidate'],
        );
    }

    public function updateInsuranceLicenses(UpdateProfileInsuranceLicensesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $isLicensed = $request->isLicensedLifeInsurance();
        $licenses = $request->normalizedInsuranceLicenses();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'is_licensed_life_insurance' => $isLicensed,
                'insurance_licenses' => $licenses,
            ],
        );

        if ($user->hasRole('certified-field-mentor')) {
            app(CfmManagementService::class)->syncLicensedJurisdictions($user, $licenses);
        }

        $this->validatedTasks->completeProfileTaskIfOpen($user->fresh(['profile']));

        return Redirect::route('profile.edit', ['tab' => 'licenses'])->with('licenses_feedback', [
            'type' => 'success',
            'message' => 'Your insurance licenses were saved.',
        ]);
    }

    public function updateInviteLink(UpdateProfileInviteLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->profile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'gw_associate_id' => filled($validated['gw_associate_id'] ?? null)
                    ? $validated['gw_associate_id']
                    : null,
                'gw_invite_link' => filled($validated['gw_invite_link'] ?? null)
                    ? $validated['gw_invite_link']
                    : null,
            ],
        );

        $this->validatedTasks->completeProfileTaskIfOpen($request->user()->fresh(['profile']));

        return Redirect::route('profile.edit')
            ->with('status', 'gw-invite-link-saved')
            ->with('gw_details_feedback', [
                'type' => 'success',
                'message' => 'Your GW details were saved.',
            ]);
    }

    public function updatePhoto(UpdateProfilePhotoRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $profile = $this->profilePhotos->update($user, $request->file('photo'));

        $user->unsetRelation('profile');
        $user->setRelation('profile', $profile);

        $this->validatedTasks->completeProfileTaskIfOpen($user->fresh(['profile']));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your profile photo was updated.',
                'photo_url' => $user->profilePhotoUrl(),
                'profile_completion' => $this->profileCompletion->snapshot($user),
            ]);
        }

        return Redirect::route('profile.edit', ['tab' => 'profile'])->with('profile_feedback', [
            'type' => 'success',
            'message' => 'Your profile photo was updated.',
        ]);
    }

    public function destroyPhoto(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->profilePhotos->delete($user);

        $user->unsetRelation('profile');
        $user->load('profile');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your profile photo was removed.',
                'photo_url' => null,
                'profile_completion' => $this->profileCompletion->snapshot($user),
            ]);
        }

        return Redirect::route('profile.edit', ['tab' => 'profile'])->with('profile_feedback', [
            'type' => 'success',
            'message' => 'Your profile photo was removed.',
        ]);
    }

    public function createInvitation(Request $request, MemberInvitationService $invitations): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $invitation = $invitations->createInvitation(
            $request->user(),
            $validated['email'] ?? null,
        );

        return Redirect::route('profile.edit', ['open_invitations' => 1])
            ->with('status', 'invitation-created')
            ->with('invitation_id', $invitation->id)
            ->with('invitation_url', $invitation->invitationUrl());
    }

    public function sendInvitationEmail(Request $request, RegistrationInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->sponsor_id === $request->user()->id, 403);

        $validated = $request->validate([
            'recipient_email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:20000'],
        ]);

        app(MemberInvitationService::class)->ensureEmailCanReceiveInvitation($validated['recipient_email'], $invitation);

        if (! $this->messageContainsRegistrationLink($validated['message'], $invitation->invitationUrl())) {
            throw ValidationException::withMessages([
                'message' => 'The registration link must remain included in the email message.',
            ]);
        }

        Mail::to($validated['recipient_email'])
            ->send(new InvitationLinkMail(
                $validated['subject'],
                $validated['message'],
                $request->user()->name,
                $request->user()->email,
            ));

        $invitation->forceFill([
            'email' => $validated['recipient_email'],
            'last_emailed_at' => now(),
        ])->save();

        return Redirect::route('profile.edit', ['open_invitations' => 1])
            ->with('status', 'invitation-email-sent');
    }

    public function destroyInvitation(Request $request, RegistrationInvitation $invitation): RedirectResponse
    {
        app(MemberInvitationService::class)->revokeInvitation($request->user(), $invitation);

        return Redirect::route('profile.edit', ['open_invitations' => 1])
            ->with('status', 'invitation-deleted');
    }

    private function ensureEmailCanReceiveInvitation(?string $email, ?RegistrationInvitation $currentInvitation = null): void
    {
        app(MemberInvitationService::class)->ensureEmailCanReceiveInvitation($email, $currentInvitation);
    }

    public function renderInvitationEmail(RegistrationInvitation $invitation, ?EmailTemplate $template = null): array
    {
        $template ??= EmailTemplate::where('key', 'member_invitation')
            ->where('is_active', true)
            ->first();

        $tokens = EmailTemplateTokens::merge([
            'app_name' => config('app.name', 'GWTrack'),
            'sponsor_name' => $invitation->sponsor?->name ?? 'A GWTrack member',
            'registration_link' => $invitation->invitationUrl(),
            'registration_code' => $invitation->code,
            'expires_at' => $invitation->expires_at?->format('F j, Y') ?? 'the expiration date',
        ], EmailTemplateTokens::pathFromUrl($invitation->invitationUrl()));

        return [
            'subject' => $template?->renderSubject($tokens) ?? 'You are invited to join '.config('app.name', 'GWTrack'),
            'body' => $template?->renderBody($tokens) ?? $invitation->invitationUrl(),
        ];
    }

    private function messageContainsRegistrationLink(string $message, string $registrationUrl): bool
    {
        if (str_contains($message, $registrationUrl)) {
            return true;
        }

        $encodedUrl = htmlspecialchars($registrationUrl, ENT_QUOTES, 'UTF-8');

        return $encodedUrl !== $registrationUrl && str_contains($message, $encodedUrl);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function profileAttributesFromRequest(ProfileUpdateRequest $request, array $validated): array
    {
        $attributes = [];

        foreach ([
            'phone',
            'city',
            'country_id',
            'state_province_id',
            'timezone_id',
            'best_contact_time',
            'bio',
        ] as $field) {
            if ($request->has($field)) {
                $attributes[$field] = $validated[$field] ?? null;
            }
        }

        if ($request->has('gw_associate_id')) {
            $attributes['gw_associate_id'] = filled($validated['gw_associate_id'] ?? null)
                ? $validated['gw_associate_id']
                : null;
        }

        if ($request->has('gw_invite_link')) {
            $attributes['gw_invite_link'] = filled($validated['gw_invite_link'] ?? null)
                ? $validated['gw_invite_link']
                : null;
        }

        return $attributes;
    }
}
