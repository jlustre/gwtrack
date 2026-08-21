<?php

namespace App\Http\Controllers\Auth;

use App\Events\NewMemberRegistered;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RegistrationInvitation;
use App\Models\Profile;
use App\Models\Rank;
use App\Models\User;
use App\Services\Prospects\ProspectConversionService;
use App\Services\MonetizationSettingsService;
use App\Support\LocationOptions;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const REGISTRATION_COUNTRIES = [
        'Canada',
        'United States',
        'Philippines',
        'Mexico',
    ];

    /**
     * Display the registration view.
     */
    public function create(?string $code = null): View
    {
        abort_if($code === null, 403, 'Registration requires an invitation link from a current GWTrack member.');

        $invitation = RegistrationInvitation::query()
            ->with('sponsor')
            ->where('code', $code)
            ->first();

        abort_if($invitation === null, 403, 'This invitation link is no longer available.');

        abort_unless($invitation->isAvailable(), 403, 'This invitation link is no longer available.');

        $locationOptions = LocationOptions::forPortal();
        $registrationCountries = collect($locationOptions['countries'])
            ->filter(fn (string $name): bool => in_array($name, self::REGISTRATION_COUNTRIES, true))
            ->all();
        $subscriptionDisclosure = app(MonetizationSettingsService::class)->registrationDisclosure();

        return view('auth.register', [
            'invitation' => $invitation,
            'locationOptions' => $locationOptions,
            'registrationCountries' => $registrationCountries,
            'subscriptionDisclosure' => $subscriptionDisclosure,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $monetizationEnabled = app(MonetizationSettingsService::class)->enabled();

        $request->validate([
            'registration_code' => ['required', 'string', 'exists:registration_invitations,code'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'gw_associate_id' => ['required', 'string', 'max:100', 'unique:profiles,gw_associate_id'],
            'city' => ['required', 'string', 'max:120'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'state_province_id' => ['required', 'integer', 'exists:state_provinces,id'],
            'timezone_id' => ['required', 'integer', 'exists:timezones,id'],
            'sponsor_confirmed' => ['accepted'],
            'active_associate_confirmed' => ['accepted'],
            'subscription_terms_accepted' => $monetizationEnabled ? ['accepted'] : ['nullable'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $countryId = $request->integer('country_id');
        $stateProvinceId = $request->integer('state_province_id');
        $countryName = Country::query()->whereKey($countryId)->value('name');

        if (! in_array($countryName, self::REGISTRATION_COUNTRIES, true)) {
            throw ValidationException::withMessages([
                'country_id' => 'Select a supported registration country.',
            ]);
        }

        if (! LocationOptions::isValidStateProvinceId($countryId, $stateProvinceId)) {
            throw ValidationException::withMessages([
                'state_province_id' => 'Select a valid province or state for the chosen country.',
            ]);
        }

        $user = DB::transaction(function () use ($request, $countryId, $stateProvinceId, $monetizationEnabled): User {
            $invitation = RegistrationInvitation::query()
                ->with('sponsor')
                ->where('code', $request->string('registration_code')->toString())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isAvailable()) {
                throw ValidationException::withMessages([
                    'registration_code' => 'This invitation link is no longer available.',
                ]);
            }

            $email = $invitation->hasInviteeEmail()
                ? strtolower(trim((string) $invitation->email))
                : strtolower(trim((string) $request->input('email')));

            if (! $invitation->matchesInviteeEmail((string) $request->input('email'))) {
                throw ValidationException::withMessages([
                    'email' => 'This invitation can only be used with the invitee email address.',
                ]);
            }

            if (User::where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already registered as a GWTrack member.',
                ]);
            }

            $entryRank = Rank::entry();

            $user = User::create([
                'name' => trim($request->first_name.' '.$request->last_name),
                'email' => $email,
                'password' => Hash::make($request->password),
                'rank_id' => $entryRank?->id,
                'team_id' => $invitation->sponsor?->team_id,
                'sponsor_id' => $invitation->sponsor_id,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            $locationIds = [
                'country_id' => $countryId,
                'state_province_id' => $stateProvinceId,
                'timezone_id' => $request->integer('timezone_id'),
            ];

            $user->profile()->create([
                'gw_associate_id' => $request->string('gw_associate_id')->toString(),
                'city' => $request->string('city')->toString(),
                'country_id' => $locationIds['country_id'],
                'state_province_id' => $locationIds['state_province_id'],
                'timezone_id' => $locationIds['timezone_id'],
                'is_gw_active_associate' => true,
                'subscription_terms_accepted_at' => $monetizationEnabled ? now() : null,
            ]);

            $user->syncRoles([User::DEFAULT_ROLE]);

            $invitation->forceFill([
                'accepted_by' => $user->id,
                'accepted_at' => now(),
                'revoked_at' => now(),
                'uses_count' => $invitation->uses_count + 1,
            ])->save();

            if ($invitation->prospect_id) {
                app(ProspectConversionService::class)->completeAssociateConversion($invitation, $user);
            }

            return $user;
        });

        event(new Registered($user));
        event(new NewMemberRegistered($user));

        return redirect()
            ->route('login')
            ->with('status', 'Your account was created successfully. Please check your email and click the verification link before signing in.');
    }
}
