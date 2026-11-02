<?php

namespace Database\Seeders;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventActivityLog;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventNote;
use App\Models\CalendarEventRecurrence;
use App\Models\CalendarEventReminder;
use App\Models\CalendarEventType;
use App\Models\Prospect;
use App\Models\Rank;
use App\Models\Team;
use App\Models\User;
use App\Models\UserCalendarPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CalendarModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NotificationModuleSeeder::class);
        $this->call(PublicHolidayCalendarSeeder::class);

        $categories = collect([
            ['name' => 'Personal', 'slug' => 'personal', 'color' => '#64748B', 'icon' => 'user', 'sort_order' => 10],
            ['name' => 'Team', 'slug' => 'team', 'color' => '#2D3E50', 'icon' => 'users', 'sort_order' => 20],
            ['name' => 'Training', 'slug' => 'training', 'color' => '#2563EB', 'icon' => 'graduation-cap', 'sort_order' => 30],
            ['name' => 'Prospects', 'slug' => 'prospects', 'color' => '#16A34A', 'icon' => 'target', 'sort_order' => 40],
            ['name' => 'Licensing', 'slug' => 'licensing', 'color' => '#DC2626', 'icon' => 'badge-check', 'sort_order' => 50],
            ['name' => 'Field Apprenticeship', 'slug' => 'field-apprenticeship', 'color' => '#9333EA', 'icon' => 'briefcase', 'sort_order' => 60],
            ['name' => 'CFM Certification', 'slug' => 'cfm-certification', 'color' => '#84BB41', 'icon' => 'award', 'sort_order' => 70],
            ['name' => 'Rank Advancement', 'slug' => 'rank-advancement', 'color' => '#F97316', 'icon' => 'trending-up', 'sort_order' => 80],
            ['name' => 'Organization', 'slug' => 'organization', 'color' => '#0891B2', 'icon' => 'building', 'sort_order' => 90],
        ])->mapWithKeys(fn (array $category): array => [
            $category['slug'] => CalendarCategory::updateOrCreate(['slug' => $category['slug']], $category),
        ]);

        $typeSeeds = [
            ['Team Meeting', 'team-meeting', 'team', '#2D3E50'],
            ['Training Session', 'training-session', 'training', '#2563EB'],
            ['Recorded Webinar Review', 'recorded-webinar-review', 'training', '#1D4ED8'],
            ['Prospect Appointment', 'prospect-appointment', 'prospects', '#16A34A'],
            ['Follow-up Block', 'follow-up-block', 'prospects', '#22C55E'],
            ['Licensing Deadline', 'licensing-deadline', 'licensing', '#DC2626'],
            ['Licensing Review', 'licensing-review', 'licensing', '#B91C1C'],
            ['FAP Mentor Session', 'fap-mentor-session', 'field-apprenticeship', '#9333EA'],
            ['Field Observation', 'field-observation', 'field-apprenticeship', '#7E22CE'],
            ['CFM Training', 'cfm-training', 'cfm-certification', '#84BB41'],
            ['CFM Approval Review', 'cfm-approval-review', 'cfm-certification', '#74A539'],
            ['Rank Review', 'rank-review', 'rank-advancement', '#F97316'],
            ['Recognition Event', 'recognition-event', 'organization', '#0891B2'],
            ['Agency Event', 'agency-event', 'organization', '#0E7490'],
        ];

        foreach (config('crm-calendar.event_types', []) as $slug => $type) {
            $typeSeeds[] = [
                $type['name'],
                $slug,
                config('crm-calendar.category.slug', 'prospects'),
                $type['color'] ?? '#16A34A',
                $type['sort_order'] ?? null,
            ];
        }

        $types = collect($typeSeeds)->mapWithKeys(function (array $type, int $index) use ($categories): array {
            [$name, $slug, $categorySlug, $color] = $type;
            $sortOrder = $type[4] ?? (($index + 1) * 10);

            return [
                $slug => CalendarEventType::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'calendar_category_id' => $categories[$categorySlug]->id,
                        'name' => $name,
                        'slug' => $slug,
                        'color' => $color,
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                    ]
                ),
            ];
        });

        $rank = Rank::query()->where('code', 'TA')->first();
        $team = Team::query()->first();
        $organizer = $this->member('calendar-owner@gwtracks.com', 'Calendar Agency Owner', 'agency-owner', $team?->id, $rank?->id);
        $leader = $this->member('calendar-leader@gwtracks.com', 'Calendar Team Leader', 'team-leader', $team?->id, $rank?->id, $organizer->id);
        $mentor = $this->member('calendar-cfm@gwtracks.com', 'Calendar Certified Field Mentor', 'certified-field-mentor', $team?->id, $rank?->id, $leader->id);
        $trainer = $this->member('calendar-trainer@gwtracks.com', 'Calendar Trainer', 'trainer', $team?->id, $rank?->id, $leader->id);
        $member = $this->member('calendar-member@gwtracks.com', 'Calendar Member', 'member', $team?->id, $rank?->id, $leader->id);
        $prospect = Prospect::query()->where('owner_id', $organizer->id)->first() ?? Prospect::query()->first();

        foreach ([$organizer, $leader, $mentor, $trainer, $member] as $user) {
            UserCalendarPreference::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'default_view' => 'month',
                    'timezone' => 'America/Vancouver',
                    'visible_calendar_categories' => array_values(array_filter([
                        $categories['personal']->id ?? null,
                    ])),
                    'show_weekends' => true,
                ]
            );
        }

        $events = [
            [
                'title' => 'Monday Agency Leadership Huddle',
                'type' => 'team-meeting',
                'organizer' => $organizer,
                'start' => now()->startOfWeek()->addHours(9),
                'end' => now()->startOfWeek()->addHours(10),
                'visibility' => 'shared_team',
                'location' => 'Agency Zoom Room',
                'description' => 'Weekly agency-owner and team-leader alignment on recruiting, onboarding, licensing movement, and key leadership priorities.',
            ],
            [
                'title' => 'New Associate Fast Start Training',
                'type' => 'training-session',
                'organizer' => $trainer,
                'start' => now()->startOfWeek()->addDays(1)->addHours(18),
                'end' => now()->startOfWeek()->addDays(1)->addHours(19)->addMinutes(30),
                'visibility' => 'public_organization',
                'meeting_link' => 'https://zoom.us/j/gwtracks-fast-start',
                'description' => 'Foundational training for brand standards, communication scripts, appointment flow, and the first-week execution rhythm.',
            ],
            [
                'title' => 'Prospect Appointment: Career Overview',
                'type' => 'prospect-appointment',
                'organizer' => $member,
                'start' => now()->addDays(2)->setTime(15, 0),
                'end' => now()->addDays(2)->setTime(16, 0),
                'visibility' => 'private',
                'related_prospect_id' => $prospect?->id,
                'location' => 'Virtual meeting',
                'description' => 'Initial overview conversation to understand the prospect goals, background, timing, and interest in Greatway.',
            ],
            [
                'title' => 'Licensing Milestone Review',
                'type' => 'licensing-review',
                'organizer' => $leader,
                'start' => now()->addDays(3)->setTime(11, 0),
                'end' => now()->addDays(3)->setTime(11, 45),
                'visibility' => 'shared_team',
                'description' => 'Review licensing checklist items, exam readiness, provincial requirements, and next action owners.',
            ],
            [
                'title' => 'FAP Mentor Session: Field Prep',
                'type' => 'fap-mentor-session',
                'organizer' => $mentor,
                'start' => now()->addDays(4)->setTime(13, 30),
                'end' => now()->addDays(4)->setTime(14, 30),
                'visibility' => 'shared_team',
                'related_apprentice_id' => $member->id,
                'description' => 'Mentor-led preparation for supervised field activity, documentation expectations, and post-appointment debrief.',
            ],
            [
                'title' => 'CFM Certification Cohort',
                'type' => 'cfm-training',
                'organizer' => $trainer,
                'start' => now()->addDays(5)->setTime(10, 0),
                'end' => now()->addDays(5)->setTime(12, 0),
                'visibility' => 'public_organization',
                'description' => 'Certification training for eligible Associate+ members preparing to become Certified Field Mentors.',
            ],
            [
                'title' => 'Rank Advancement Review: TA to Associate',
                'type' => 'rank-review',
                'organizer' => $leader,
                'start' => now()->addDays(7)->setTime(16, 0),
                'end' => now()->addDays(7)->setTime(16, 45),
                'visibility' => 'shared_team',
                'description' => 'Progress review for production, recruiting, training completion, and documented readiness for the next rank milestone.',
            ],
            [
                'title' => 'Monthly Recognition and Momentum Call',
                'type' => 'recognition-event',
                'organizer' => $organizer,
                'start' => now()->addDays(10)->setTime(18, 0),
                'end' => now()->addDays(10)->setTime(19, 0),
                'visibility' => 'public_organization',
                'description' => 'Recognition for recruiting wins, licensing milestones, completed FAP items, CFM progress, and rank advancement.',
            ],
        ];

        foreach ($events as $seed) {
            $type = $types[$seed['type']];
            $event = CalendarEvent::updateOrCreate(
                ['title' => $seed['title'], 'organizer_id' => $seed['organizer']->id],
                [
                    'calendar_event_type_id' => $type->id,
                    'calendar_category_id' => $type->calendar_category_id,
                    'description' => $seed['description'],
                    'starts_at' => $seed['start'],
                    'ends_at' => $seed['end'],
                    'timezone' => 'America/Vancouver',
                    'is_all_day' => false,
                    'is_recurring' => str_contains($seed['title'], 'Monday'),
                    'recurrence_rule' => str_contains($seed['title'], 'Monday') ? 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO' : null,
                    'location' => $seed['location'] ?? null,
                    'meeting_link' => $seed['meeting_link'] ?? null,
                    'visibility' => $seed['visibility'],
                    'status' => 'scheduled',
                    'color' => $type->color,
                    'related_prospect_id' => $seed['related_prospect_id'] ?? null,
                    'related_apprentice_id' => $seed['related_apprentice_id'] ?? null,
                    'notes' => 'Seeded calendar event for module scaffolding.',
                ]
            );

            $attendees = collect([$organizer, $leader, $mentor, $trainer, $member])
                ->filter(fn (User $user): bool => $user->id !== $event->organizer_id)
                ->take($event->visibility === 'private' ? 1 : 4);

            foreach ($attendees as $attendee) {
                CalendarEventAttendee::updateOrCreate(
                    ['calendar_event_id' => $event->id, 'user_id' => $attendee->id],
                    [
                        'attendee_type' => 'user',
                        'rsvp_status' => $attendee->id === $member->id ? 'pending' : 'accepted',
                        'responded_at' => $attendee->id === $member->id ? null : now(),
                    ]
                );
            }

            CalendarEventReminder::updateOrCreate(
                ['calendar_event_id' => $event->id, 'user_id' => $event->organizer_id, 'minutes_before' => 30],
                ['channel' => 'in_app']
            );

            if ($event->is_recurring) {
                CalendarEventRecurrence::updateOrCreate(
                    ['calendar_event_id' => $event->id],
                    ['frequency' => 'weekly', 'interval' => 1, 'weekdays' => ['MO'], 'ends_after_occurrences' => 12]
                );
            }

            CalendarEventNote::updateOrCreate(
                ['calendar_event_id' => $event->id, 'created_by' => $event->organizer_id],
                ['note' => 'Seed note: confirm attendees, agenda, and follow-up tasks before the session.', 'is_private' => false]
            );

            CalendarEventActivityLog::updateOrCreate(
                ['calendar_event_id' => $event->id, 'action' => 'seeded'],
                ['user_id' => $event->organizer_id, 'payload' => ['source' => self::class]]
            );

            if ($event->visibility === 'shared_team' && $team) {
                $event->visibilityRules()->updateOrCreate(
                    ['visibility_type' => 'team', 'team_id' => $team->id],
                    ['role_name' => null, 'user_id' => null]
                );
            }
        }
    }

    private function member(string $email, string $name, string $role, ?int $teamId, ?int $rankId, ?int $sponsorId = null): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'team_id' => $teamId,
                'rank_id' => $rankId,
                'sponsor_id' => $sponsorId,
                'joined_at' => now()->subDays(30),
                'is_active' => true,
                'is_online' => false,
                'email_verified_at' => now(),
            ]
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->syncRoles([$role]);

        return $user;
    }
}
