<?php



namespace App\Services;



use App\Models\CalendarEvent;

use App\Models\CfmMentorProfile;

use App\Models\MentorAssignment;

use App\Models\User;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Collection;



class CalendarShareService
{
    public function canViewCfmCalendar(User $viewer, User $cfm): bool

    {

        if ($viewer->id === $cfm->id) {

            return true;

        }



        if (! $cfm->hasRole('certified-field-mentor')) {

            return false;

        }



        $profile = $cfm->cfmMentorProfile;

        if (! $profile) {

            return false;

        }



        if ($profile->share_calendar_with_apprentices && $this->viewerIsApprenticeOf($viewer, $cfm)) {

            return true;

        }



        return $profile->share_calendar_with_agency_owner

            && $this->viewerIsAgencyOwnerFor($viewer, $cfm);

    }



    /**

     * Whether the viewer can see another member's shared schedule blocks.

     * Matches existing calendar access: CFM sharing, mentorship, or explicit event visibility grants.

     */

    public function canViewUserCalendarAvailability(User $viewer, User $owner): bool

    {

        if ($viewer->id === $owner->id) {

            return false;

        }



        if ($this->canViewCfmCalendar($viewer, $owner)) {

            return true;

        }



        if ($this->viewerIsMentorOf($viewer, $owner)) {

            return true;

        }



        return CalendarEvent::query()

            ->where('organizer_id', $owner->id)

            ->where('visibility', '!=', 'private')

            ->whereHas('visibilityRules', fn (Builder $query) => $query->where('user_id', $viewer->id))

            ->exists();

    }



    /**

     * @return list<int>

     */

    public function sharedCfmOrganizerIdsFor(User $viewer): array

    {

        return $this->sharedCfmOrganizersFor($viewer)

            ->pluck('id')

            ->map(fn (int $id): int => $id)

            ->values()

            ->all();

    }



    /**

     * @return Collection<int, User>

     */

    public function sharedCfmOrganizersFor(User $viewer): Collection

    {

        $cfmIds = collect();



        if ($viewer->mentor_id) {

            $cfmIds->push((int) $viewer->mentor_id);

        }



        $cfmIds = $cfmIds->merge(

            MentorAssignment::query()

                ->where('apprentice_id', $viewer->id)

                ->where('status', 'active')

                ->pluck('mentor_id')

        )->unique()->filter();



        $apprenticeMentors = User::query()

            ->role('certified-field-mentor')

            ->whereIn('id', $cfmIds)

            ->with('cfmMentorProfile')

            ->get()

            ->filter(fn (User $cfm): bool => (bool) $cfm->cfmMentorProfile?->share_calendar_with_apprentices);



        $agencyOwnerCfms = User::query()

            ->role('certified-field-mentor')

            ->whereHas('cfmMentorProfile', fn ($query) => $query->where('share_calendar_with_agency_owner', true))

            ->with('cfmMentorProfile')

            ->get()

            ->filter(fn (User $cfm): bool => $this->viewerIsAgencyOwnerFor($viewer, $cfm));



        return $apprenticeMentors

            ->merge($agencyOwnerCfms)

            ->unique('id')

            ->reject(fn (User $cfm): bool => $cfm->id === $viewer->id)

            ->values();

    }



    public function sharingSettingsFor(User $cfm): array

    {

        $profile = $this->mentorProfile($cfm);



        return [

            'shareCalendarWithApprentices' => (bool) $profile->share_calendar_with_apprentices,

            'shareCalendarWithAgencyOwner' => (bool) $profile->share_calendar_with_agency_owner,

            'agencyOwnerName' => $this->resolveAgencyOwnerUpline($cfm)?->name ?? '—',

            'activeApprenticeCount' => $cfm->apprentices()->count(),

        ];

    }



    public function updateSharingSettings(User $cfm, array $data): void

    {

        abort_unless($cfm->hasRole('certified-field-mentor'), 403);



        $profile = $this->mentorProfile($cfm);



        $profile->update([

            'share_calendar_with_apprentices' => (bool) ($data['share_calendar_with_apprentices'] ?? false),

            'share_calendar_with_agency_owner' => (bool) ($data['share_calendar_with_agency_owner'] ?? false),

            'last_mentor_activity_at' => now(),

        ]);

    }



    /**

     * @return Collection<int, User>

     */

    public function sharedScheduleBlockOwnersFor(User $viewer): Collection

    {

        $owners = $this->sharedCfmOrganizersFor($viewer);



        if ($viewer->hasRole('certified-field-mentor')) {

            $apprenticeIds = MentorAssignment::query()

                ->where('mentor_id', $viewer->id)

                ->where('status', 'active')

                ->pluck('apprentice_id')

                ->merge(User::query()->where('mentor_id', $viewer->id)->pluck('id'))

                ->unique()

                ->filter(fn ($id): bool => (int) $id !== (int) $viewer->id);



            if ($apprenticeIds->isNotEmpty()) {

                $owners = $owners->merge(

                    User::query()->whereIn('id', $apprenticeIds)->get()

                );

            }

        }



        $explicitOwnerIds = CalendarEvent::query()

            ->where('visibility', '!=', 'private')

            ->where('organizer_id', '!=', $viewer->id)

            ->whereHas('visibilityRules', fn (Builder $query) => $query->where('user_id', $viewer->id))

            ->distinct()

            ->pluck('organizer_id');



        if ($explicitOwnerIds->isNotEmpty()) {

            $owners = $owners->merge(User::query()->whereIn('id', $explicitOwnerIds)->get());

        }



        return $owners

            ->reject(fn (User $owner): bool => $owner->id === $viewer->id)

            ->unique('id')

            ->values();

    }



    private function viewerIsMentorOf(User $viewer, User $owner): bool

    {

        return $this->viewerIsApprenticeOf($owner, $viewer);

    }



    private function mentorProfile(User $cfm): CfmMentorProfile

    {

        return CfmMentorProfile::firstOrCreate(

            ['user_id' => $cfm->id],

            [

                'certification_status' => 'certified',

                'hierarchy_access' => 'my_hierarchy',

                'max_apprentices' => 6,

                'fap_completion_rate' => 0,

                'calendar_busyness_percent' => 0,

                'avg_apprentice_progress' => 0,

                'recommendation_score' => 75,

                'languages' => ['English'],

                'specialties' => ['Field Apprenticeship'],

            ]

        );

    }



    private function viewerIsApprenticeOf(User $viewer, User $cfm): bool

    {

        if ((int) $viewer->mentor_id === (int) $cfm->id) {

            return true;

        }



        return MentorAssignment::query()

            ->where('mentor_id', $cfm->id)

            ->where('apprentice_id', $viewer->id)

            ->where('status', 'active')

            ->exists();

    }



    private function viewerIsAgencyOwnerFor(User $viewer, User $cfm): bool

    {

        $agencyOwner = $this->resolveAgencyOwnerUpline($cfm);



        return $agencyOwner && (int) $agencyOwner->id === (int) $viewer->id;

    }



    private function resolveAgencyOwnerUpline(User $cfm): ?User

    {

        $cfm->loadMissing(['sponsor', 'team.owner']);



        if ($cfm->hasRole('agency-owner')) {

            return $cfm;

        }



        $visited = [$cfm->id];

        $sponsor = $cfm->sponsor;



        while ($sponsor) {

            if ($sponsor->hasRole('agency-owner')) {

                return $sponsor;

            }



            if (in_array($sponsor->id, $visited, true) || ! $sponsor->sponsor_id) {

                break;

            }



            $visited[] = $sponsor->id;

            $sponsor = User::query()->whereKey($sponsor->sponsor_id)->first();

        }



        $teamOwner = $cfm->team?->owner;



        if ($teamOwner?->hasRole('agency-owner')) {

            return $teamOwner;

        }



        return $teamOwner;

    }

}


