<?php

namespace App\Livewire\Profile;

use App\Mail\InvitationLinkMail;
use App\Models\EmailTemplate;
use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Services\CfmManagementService;
use App\Services\ComplianceLifecycleService;
use App\Services\DownlineHierarchyService;
use App\Services\MemberProductionService;
use App\Services\MemberProfileTabsService;
use App\Services\MemberUplineService;
use App\Services\Members\MemberInvitationService;
use App\Services\NewMemberValidatedTaskService;
use App\Services\ProfileCompletionService;
use App\Services\ProfilePhotoService;
use App\Support\EmailTemplateTokens;
use App\Support\LocationOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfilePage extends Component
{
    use WithFileUploads;

    public ?int $memberUserId = null;

    public bool $isOwnProfile = true;

    #[Url(except: 'profile')]
    public string $tab = 'profile';

    #[Url(as: 'open_invitations', except: false)]
    public bool $openInvitations = false;

    public bool $historyOpen = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $city = '';

    public string $countryId = '';

    public string $stateProvinceId = '';

    public string $timezoneId = '';

    public string $bestContactTime = '';

    public string $bio = '';

    public string $gwAssociateId = '';

    public string $gwInviteLink = '';

    public bool $isLicensedLifeInsurance = false;

    /** @var list<string> */
    public array $insuranceLicenses = [];

    /** @var array<string, string> */
    public array $insuranceLicenseNumbers = [];

    /** @var array<string, string> */
    public array $insuranceLicenseExpirations = [];

    public string $inviteEmail = '';

    public ?string $latestInvitationUrl = null;

    public ?int $latestInvitationId = null;

    public string $productionDescription = '';

    public string $productionPolicyReference = '';

    public string $productionAnnualPremium = '';

    public string $productionPostedAt = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public $photo = null;

    /** @var array{type: string, message: string}|null */
    public ?array $profileFeedback = null;

    /** @var array{type: string, message: string}|null */
    public ?array $licensesFeedback = null;

    /** @var array{type: string, message: string}|null */
    public ?array $gwDetailsFeedback = null;

    /** @var array{type: string, message: string}|null */
    public ?array $productionFeedback = null;

    public ?string $passwordStatus = null;

    public ?string $invitationStatus = null;

    public function mount(
        DownlineHierarchyService $downlineHierarchy,
        ?int $memberUserId = null,
        string $tab = 'profile',
        bool $openInvitations = false,
    ): void {
        $viewer = auth()->user();
        abort_unless($viewer instanceof User, 403);

        $this->memberUserId = $memberUserId;
        $this->isOwnProfile = $memberUserId === null;
        $this->tab = $this->normalizeTab($tab);
        $this->openInvitations = $openInvitations;
        $this->historyOpen = $openInvitations;
        $this->productionPostedAt = now()->toDateString();

        $user = $this->profileUser($downlineHierarchy);
        $this->fillFromUser($user);

        if (session()->has('profile_feedback')) {
            $this->profileFeedback = session('profile_feedback');
        }
        if (session()->has('licenses_feedback')) {
            $this->licensesFeedback = session('licenses_feedback');
            $this->tab = 'licenses';
        }
        if (session()->has('gw_details_feedback')) {
            $this->gwDetailsFeedback = session('gw_details_feedback');
        }
        if (session()->has('production_feedback')) {
            $this->productionFeedback = session('production_feedback');
            $this->tab = 'annual-premium';
        }
        if (session('status') === 'password-updated') {
            $this->passwordStatus = 'password-updated';
        }
        if (in_array(session('status'), ['invitation-created', 'invitation-deleted', 'invitation-email-sent'], true)) {
            $this->invitationStatus = session('status');
            $this->historyOpen = true;
            $this->openInvitations = true;
        }
        if (session('invitation_url')) {
            $this->latestInvitationUrl = session('invitation_url');
            $this->latestInvitationId = session('invitation_id');
        }
    }

    public function updatedTab(string $value): void
    {
        $this->tab = $this->normalizeTab($value);
    }

    public function updatedCountryId(): void
    {
        $provinces = LocationOptions::forPortal()['provincesByCountryId'][$this->countryId] ?? [];

        if ($this->stateProvinceId !== '' && ! array_key_exists($this->stateProvinceId, $provinces)
            && ! array_key_exists((int) $this->stateProvinceId, $provinces)) {
            $this->stateProvinceId = '';
        }
    }

    public function updatedOpenInvitations(bool $value): void
    {
        if ($value) {
            $this->historyOpen = true;
        }
    }

    public function updatedHistoryOpen(bool $value): void
    {
        $this->openInvitations = $value;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $this->normalizeTab($tab);
        $this->clearFeedbackForTab($tab);
    }

    public function openInvitationHistory(): void
    {
        $this->historyOpen = true;
        $this->openInvitations = true;
    }

    public function closeInvitationHistory(): void
    {
        $this->historyOpen = false;
        $this->openInvitations = false;
    }

    public function saveProfile(NewMemberValidatedTaskService $validatedTasks): void
    {
        abort_unless($this->isOwnProfile, 403);

        $user = $this->ownUser();

        try {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique(User::class)->ignore($user->id),
                ],
                'phone' => ['nullable', 'string', 'max:40'],
                'city' => ['nullable', 'string', 'max:120'],
                'countryId' => ['nullable', 'integer', 'exists:countries,id'],
                'stateProvinceId' => ['nullable', 'integer', 'exists:state_provinces,id'],
                'timezoneId' => ['nullable', 'integer', 'exists:timezones,id'],
                'bestContactTime' => ['nullable', 'string', Rule::in(array_keys(LocationOptions::contactTimes()))],
                'bio' => ['nullable', 'string', 'max:1000'],
            ]);
        } catch (ValidationException $exception) {
            $this->tab = 'profile';
            $this->profileFeedback = [
                'type' => 'error',
                'message' => 'Please correct the highlighted fields and try again.',
            ];

            throw $exception;
        }

        $countryId = filled($this->countryId) ? (int) $this->countryId : null;
        $stateProvinceId = filled($this->stateProvinceId) ? (int) $this->stateProvinceId : null;

        if (! LocationOptions::isValidStateProvinceId($countryId, $stateProvinceId)) {
            $this->tab = 'profile';
            $this->profileFeedback = [
                'type' => 'error',
                'message' => 'Please correct the highlighted fields and try again.',
            ];
            $this->addError('stateProvinceId', 'Select a valid province or state for the chosen country.');

            return;
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $validated['phone'] ?? null,
                'city' => $validated['city'] ?? null,
                'country_id' => $countryId,
                'state_province_id' => $stateProvinceId,
                'timezone_id' => filled($this->timezoneId) ? (int) $this->timezoneId : null,
                'best_contact_time' => $validated['bestContactTime'] ?? null,
                'bio' => $validated['bio'] ?? null,
            ],
        );

        $validatedTasks->completeProfileTaskIfOpen($user->fresh(['profile']));
        $this->fillFromUser($user->fresh(['profile']));
        $this->tab = 'profile';
        $this->profileFeedback = [
            'type' => 'success',
            'message' => 'Your profile was updated successfully.',
        ];
    }

    public function saveLicenses(NewMemberValidatedTaskService $validatedTasks): void
    {
        abort_unless($this->isOwnProfile, 403);

        $user = $this->ownUser();

        try {
            $this->validate([
                'isLicensedLifeInsurance' => ['nullable', 'boolean'],
                'insuranceLicenses' => [
                    Rule::requiredIf(fn (): bool => $this->isLicensedLifeInsurance),
                    'nullable',
                    'array',
                    'min:1',
                ],
                'insuranceLicenses.*' => ['string', 'max:120'],
                'insuranceLicenseNumbers' => ['nullable', 'array'],
                'insuranceLicenseNumbers.*' => ['nullable', 'string', 'max:100'],
                'insuranceLicenseExpirations' => ['nullable', 'array'],
                'insuranceLicenseExpirations.*' => ['nullable', 'date'],
            ], [
                'insuranceLicenses.required' => 'Select at least one license jurisdiction.',
                'insuranceLicenses.min' => 'Select at least one license jurisdiction.',
            ]);
        } catch (ValidationException $exception) {
            $this->tab = 'licenses';
            $this->licensesFeedback = [
                'type' => 'error',
                'message' => 'Please correct the selected licenses and try again.',
            ];

            throw $exception;
        }

        foreach ($this->insuranceLicenses as $index => $key) {
            if (! is_string($key) || ! LocationOptions::isValidJurisdictionKey($key)) {
                $this->tab = 'licenses';
                $this->licensesFeedback = [
                    'type' => 'error',
                    'message' => 'Please correct the selected licenses and try again.',
                ];
                $this->addError(
                    "insuranceLicenses.{$index}",
                    'Each license must be a valid province or state in a supported country.'
                );

                return;
            }
        }

        $licenses = $this->isLicensedLifeInsurance
            ? LocationOptions::normalizeLicensedJurisdictionKeys($this->insuranceLicenses)
            : [];
        $licenseNumbers = $this->isLicensedLifeInsurance
            ? LocationOptions::normalizeLicenseNumbers($licenses, $this->insuranceLicenseNumbers)
            : [];
        $licenseExpirations = $this->isLicensedLifeInsurance
            ? LocationOptions::normalizeLicenseExpirations($licenses, $this->insuranceLicenseExpirations)
            : [];

        foreach ($licenses as $key) {
            $label = LocationOptions::labelsForJurisdictionKeys([$key])[0] ?? $key;

            if (($licenseNumbers[$key] ?? '') === '') {
                $this->addError(
                    'insuranceLicenseNumbers.'.$key,
                    'Enter the license number for '.$label.'.'
                );
            }

            if (($licenseExpirations[$key] ?? '') === '') {
                $this->addError(
                    'insuranceLicenseExpirations.'.$key,
                    'Enter the expiration date for '.$label.'.'
                );
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->tab = 'licenses';
            $this->licensesFeedback = [
                'type' => 'error',
                'message' => 'Please correct the selected licenses and try again.',
            ];

            return;
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'is_licensed_life_insurance' => $this->isLicensedLifeInsurance,
                'insurance_licenses' => $licenses,
                'insurance_license_numbers' => $licenseNumbers,
                'insurance_license_expirations' => $licenseExpirations,
            ],
        );

        if ($user->hasRole('certified-field-mentor')) {
            app(CfmManagementService::class)->syncLicensedJurisdictions($user, $licenses);
        }

        app(ComplianceLifecycleService::class)->syncLicenseRecordsFromProfile($user->fresh(['profile']));

        $validatedTasks->completeProfileTaskIfOpen($user->fresh(['profile']));
        $this->insuranceLicenses = $licenses;
        $this->insuranceLicenseNumbers = $licenseNumbers;
        $this->insuranceLicenseExpirations = $licenseExpirations;
        $this->tab = 'licenses';
        $this->licensesFeedback = [
            'type' => 'success',
            'message' => 'Your insurance licenses were saved.',
        ];
    }

    public function saveGwDetails(NewMemberValidatedTaskService $validatedTasks): void
    {
        abort_unless($this->isOwnProfile, 403);

        $user = $this->ownUser();
        $profileId = $user->profile?->id ?? $user->profile()->value('id');

        $gwAssociateId = filled($this->gwAssociateId) ? $this->gwAssociateId : null;
        $gwInviteLink = filled($this->gwInviteLink) ? $this->gwInviteLink : null;

        try {
            $this->validate([
                'gwAssociateId' => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('profiles', 'gw_associate_id')->ignore($profileId),
                ],
                'gwInviteLink' => [
                    'nullable',
                    'string',
                    'max:2048',
                    'url',
                    Rule::unique('profiles', 'gw_invite_link')->ignore($profileId),
                ],
            ], [
                'gwInviteLink.url' => 'Enter a valid Greatway invite URL (including https://).',
                'gwInviteLink.unique' => 'This Greatway invite link is already in use by another member.',
                'gwAssociateId.unique' => 'This GW Associate ID is already in use by another member.',
            ]);
        } catch (ValidationException $exception) {
            $this->gwDetailsFeedback = [
                'type' => 'error',
                'message' => 'Please correct the GW details and try again.',
            ];

            throw $exception;
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'gw_associate_id' => $gwAssociateId,
                'gw_invite_link' => $gwInviteLink,
            ],
        );

        $validatedTasks->completeProfileTaskIfOpen($user->fresh(['profile']));
        $this->gwAssociateId = $gwAssociateId ?? '';
        $this->gwInviteLink = $gwInviteLink ?? '';
        $this->gwDetailsFeedback = [
            'type' => 'success',
            'message' => 'Your GW details were saved.',
        ];
    }

    public function uploadPhoto(
        ProfilePhotoService $profilePhotos,
        NewMemberValidatedTaskService $validatedTasks,
    ): void {
        abort_unless($this->isOwnProfile, 403);

        try {
            $this->validate([
                'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            ], [
                'photo.required' => 'Please choose a profile photo to upload.',
                'photo.image' => 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
                'photo.mimes' => 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
                'photo.max' => 'Profile photos must be 2 MB or smaller.',
            ]);
        } catch (ValidationException $exception) {
            $this->tab = 'profile';
            $this->profileFeedback = [
                'type' => 'error',
                'message' => $exception->validator->errors()->first('photo')
                    ?? 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
            ];

            throw $exception;
        }

        $user = $this->ownUser();
        $profilePhotos->update($user, $this->photo);
        $validatedTasks->completeProfileTaskIfOpen($user->fresh(['profile']));
        $this->reset('photo');
        $this->tab = 'profile';
        $this->profileFeedback = [
            'type' => 'success',
            'message' => 'Your profile photo was updated.',
        ];
    }

    public function removePhoto(ProfilePhotoService $profilePhotos): void
    {
        abort_unless($this->isOwnProfile, 403);

        $user = $this->ownUser();
        $profilePhotos->delete($user);
        $this->tab = 'profile';
        $this->profileFeedback = [
            'type' => 'success',
            'message' => 'Your profile photo was removed.',
        ];
    }

    public function updatePassword(): void
    {
        abort_unless($this->isOwnProfile, 403);

        $validated = $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required'],
        ], [], [
            'currentPassword' => 'current password',
            'password' => 'password',
            'passwordConfirmation' => 'password confirmation',
        ]);

        $this->ownUser()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->passwordStatus = 'password-updated';
        $this->tab = 'profile';
    }

    public function createInvitation(MemberInvitationService $invitations): void
    {
        abort_unless($this->isOwnProfile, 403);

        $validated = $this->validate([
            'inviteEmail' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $invitation = $invitations->createInvitation(
            $this->ownUser(),
            $validated['inviteEmail'] ?: null,
        );

        $this->inviteEmail = '';
        $this->latestInvitationUrl = $invitation->invitationUrl();
        $this->latestInvitationId = $invitation->id;
        $this->invitationStatus = 'invitation-created';
        $this->historyOpen = true;
        $this->openInvitations = true;
    }

    /**
     * @param  array{recipient_email?: string, subject?: string, message?: string}  $payload
     */
    public function sendInvitationEmail(int $invitationId, array $payload, MemberInvitationService $invitations): void
    {
        abort_unless($this->isOwnProfile, 403);

        $invitation = RegistrationInvitation::query()->findOrFail($invitationId);
        abort_unless($invitation->sponsor_id === auth()->id(), 403);

        $validated = validator($payload, [
            'recipient_email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:20000'],
        ])->validate();

        $invitations->ensureEmailCanReceiveInvitation($validated['recipient_email'], $invitation);

        if (! $this->messageContainsRegistrationLink($validated['message'], $invitation->invitationUrl())) {
            throw ValidationException::withMessages([
                'message' => 'The registration link must remain included in the email message.',
            ]);
        }

        $user = $this->ownUser();

        Mail::to($validated['recipient_email'])
            ->send(new InvitationLinkMail(
                $validated['subject'],
                $validated['message'],
                $user->name,
                $user->email,
            ));

        $invitation->forceFill([
            'email' => $validated['recipient_email'],
            'last_emailed_at' => now(),
        ])->save();

        $this->invitationStatus = 'invitation-email-sent';
        $this->historyOpen = true;
        $this->openInvitations = true;
    }

    public function destroyInvitation(int $invitationId, MemberInvitationService $invitations): void
    {
        abort_unless($this->isOwnProfile, 403);

        $invitation = RegistrationInvitation::query()->findOrFail($invitationId);
        $invitations->revokeInvitation($this->ownUser(), $invitation);

        if ($this->latestInvitationId === $invitationId) {
            $this->latestInvitationUrl = null;
            $this->latestInvitationId = null;
        }

        $this->invitationStatus = 'invitation-deleted';
        $this->historyOpen = true;
        $this->openInvitations = true;
    }

    public function storeProduction(MemberProductionService $memberProduction): void
    {
        $member = $this->profileUser();
        $this->authorize('enterProduction', $member);

        try {
            $validated = $this->validate([
                'productionDescription' => ['required', 'string', 'max:255'],
                'productionPolicyReference' => ['nullable', 'string', 'max:255'],
                'productionAnnualPremium' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
                'productionPostedAt' => ['nullable', 'date', 'before_or_equal:today'],
            ], [
                'productionDescription.required' => 'Enter a description for this production entry.',
                'productionAnnualPremium.required' => 'Enter the annual premium amount.',
                'productionAnnualPremium.min' => 'Annual premium must be greater than zero.',
            ]);
        } catch (ValidationException $exception) {
            $this->tab = 'annual-premium';
            $this->productionFeedback = [
                'type' => 'error',
                'message' => 'Please correct the production entry and try again.',
            ];

            throw $exception;
        }

        $memberProduction->createForMember($member, auth()->user(), [
            'description' => $validated['productionDescription'],
            'policy_reference' => $validated['productionPolicyReference'] ?: null,
            'annual_premium' => $validated['productionAnnualPremium'],
            'posted_at' => $validated['productionPostedAt'] ?: null,
        ]);

        $this->reset('productionDescription', 'productionPolicyReference', 'productionAnnualPremium');
        $this->productionPostedAt = now()->toDateString();
        $this->tab = 'annual-premium';
        $this->productionFeedback = [
            'type' => 'success',
            'message' => $this->isOwnProfile
                ? 'Production entry was recorded successfully.'
                : 'Production entry was recorded for '.$member->name.'.',
        ];
    }

    public function render(
        MemberProfileTabsService $memberProfileTabs,
        MemberUplineService $memberUpline,
        ProfileCompletionService $profileCompletion,
        DownlineHierarchyService $downlineHierarchy,
    ): View {
        $user = $this->profileUser($downlineHierarchy);
        $viewer = auth()->user();

        $invitationHistory = collect();
        $invitationTemplate = null;
        $invitationEmails = collect();

        if ($this->isOwnProfile) {
            $invitationHistory = RegistrationInvitation::query()
                ->where('sponsor_id', $user->id)
                ->with('acceptedBy')
                ->latest()
                ->limit(100)
                ->get();

            $invitationTemplate = EmailTemplate::query()
                ->where('key', 'member_invitation')
                ->where('is_active', true)
                ->first();

            $invitationEmails = $invitationHistory
                ->filter(fn (RegistrationInvitation $invitation): bool => $invitation->isAvailable())
                ->mapWithKeys(fn (RegistrationInvitation $invitation) => [
                    $invitation->id => $this->renderInvitationEmail($invitation, $invitationTemplate),
                ]);
        }

        return view('livewire.profile.profile-page', [
            'user' => $user,
            'viewer' => $viewer,
            'invitationHistory' => $invitationHistory,
            'invitationTemplate' => $invitationTemplate,
            'invitationEmails' => $invitationEmails,
            'memberTabs' => $memberProfileTabs->forUser($user),
            'profileCompletion' => $profileCompletion->snapshot($user),
            'profileContext' => [
                'readonly' => $memberUpline->contextFor($user),
                'locationOptions' => LocationOptions::forPortal(),
                'canViewSensitive' => $viewer->can('viewSensitive', $user),
            ],
            'canEnterProduction' => $viewer->can('enterProduction', $user),
            'useLivewire' => true,
        ]);
    }

    private function fillFromUser(User $user): void
    {
        $profile = $user->profile;

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = (string) ($profile?->phone ?? '');
        $this->city = (string) ($profile?->city ?? '');
        $this->countryId = (string) ($profile?->country_id ?? '');
        $this->stateProvinceId = (string) ($profile?->state_province_id ?? '');
        $this->timezoneId = (string) ($profile?->timezone_id ?? '');
        $this->bestContactTime = (string) ($profile?->best_contact_time ?? '');
        $this->bio = (string) ($profile?->bio ?? '');
        $this->gwAssociateId = (string) ($profile?->gw_associate_id ?? '');
        $this->gwInviteLink = (string) ($profile?->gw_invite_link ?? '');
        $this->isLicensedLifeInsurance = (bool) ($profile?->is_licensed_life_insurance
            ?? (($profile?->insurance_licenses ?? []) !== []));
        $this->insuranceLicenses = is_array($profile?->insurance_licenses)
            ? array_values($profile->insurance_licenses)
            : [];
        $this->insuranceLicenseNumbers = $profile?->insuranceLicenseNumbersByKey() ?? [];
        $this->insuranceLicenseExpirations = $profile?->insuranceLicenseExpirationsByKey() ?? [];
    }

    private function profileUser(?DownlineHierarchyService $downlineHierarchy = null): User
    {
        if ($this->isOwnProfile) {
            return $this->ownUser()->loadMissing([
                'profile',
                'rank',
                'team.owner',
                'team',
                'sponsor',
                'mentor',
            ]);
        }

        $viewer = auth()->user();
        abort_unless($viewer instanceof User, 403);

        $user = User::query()->findOrFail($this->memberUserId);
        $downlineHierarchy ??= app(DownlineHierarchyService::class);
        abort_unless($downlineHierarchy->canViewMember($viewer, $user), 403);

        return $user->loadMissing([
            'profile',
            'rank',
            'team.owner',
            'team',
            'sponsor',
            'mentor',
        ]);
    }

    private function ownUser(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function normalizeTab(string $tab): string
    {
        $allowed = ['profile', 'licenses', 'checklists', 'recruits', 'annual-premium'];

        if ($tab === 'direct-recruits') {
            return 'recruits';
        }

        return in_array($tab, $allowed, true) ? $tab : 'profile';
    }

    private function clearFeedbackForTab(string $tab): void
    {
        if ($tab !== 'profile') {
            $this->profileFeedback = null;
            $this->passwordStatus = null;
        }
        if ($tab !== 'licenses') {
            $this->licensesFeedback = null;
        }
        if ($tab !== 'annual-premium') {
            $this->productionFeedback = null;
        }
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function renderInvitationEmail(RegistrationInvitation $invitation, ?EmailTemplate $template = null): array
    {
        $template ??= EmailTemplate::query()
            ->where('key', 'member_invitation')
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
}
