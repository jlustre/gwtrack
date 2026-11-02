<?php

namespace Database\Seeders;

use App\Models\AvailabilityRule;
use App\Models\AvailabilitySchedule;
use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\BookingAttendee;
use App\Models\BookingEventType;
use App\Models\BookingLink;
use App\Models\BookingQuestion;
use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventReminder;
use App\Models\CalendarEventType;
use App\Models\MentorAssignment;
use App\Models\Rank;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BookingSchedulingSeeder extends Seeder
{
    public function run(): void
    {
        $rank = Rank::query()->where('code', 'ASC')->first() ?? Rank::query()->first();
        $team = Team::query()->first();

        $cfm = $this->member('booking-cfm@gwtracks.com', 'Booking Certified Field Mentor', 'certified-field-mentor', $team?->id, $rank?->id);
        $trainee = $this->member('booking-trainee@gwtracks.com', 'Booking Apprentice Trainee', 'member', $team?->id, $rank?->id, $cfm->id);

        MentorAssignment::updateOrCreate(
            ['mentor_id' => $cfm->id, 'apprentice_id' => $trainee->id, 'status' => 'active'],
            ['assigned_by' => $cfm->id, 'started_at' => now()->subDays(10), 'completed_at' => null]
        );

        $category = CalendarCategory::query()->firstOrCreate(
            ['slug' => 'cfm-mentor-sessions'],
            ['name' => 'CFM Mentor Sessions', 'color' => '#84BB41', 'icon' => 'award', 'sort_order' => 75, 'is_active' => true]
        );

        $calendarType = CalendarEventType::query()->firstOrCreate(
            ['slug' => 'cfm-booking-session'],
            ['calendar_category_id' => $category->id, 'name' => 'CFM Booking Session', 'color' => '#84BB41', 'sort_order' => 75, 'is_active' => true]
        );

        $schedule = AvailabilitySchedule::query()->updateOrCreate(
            ['user_id' => $cfm->id, 'name' => 'Default CFM Availability'],
            [
                'timezone' => 'America/Vancouver',
                'is_default' => true,
                'is_active' => true,
                'working_hours' => [
                    'Monday' => ['09:00-12:00', '14:00-17:00'],
                    'Tuesday' => ['10:00-16:00'],
                    'Thursday' => ['18:00-21:00'],
                    'Friday' => ['09:00-13:00'],
                    'Saturday' => ['10:00-14:00'],
                ],
            ]
        );

        $rules = [
            [1, '09:00', '12:00', 10],
            [1, '14:00', '17:00', 20],
            [2, '10:00', '16:00', 30],
            [4, '18:00', '21:00', 40],
            [5, '09:00', '13:00', 50],
            [6, '10:00', '14:00', 60],
        ];

        foreach ($rules as [$weekday, $startsAt, $endsAt, $sortOrder]) {
            AvailabilityRule::query()->updateOrCreate(
                ['availability_schedule_id' => $schedule->id, 'weekday' => $weekday, 'starts_at' => $startsAt],
                ['ends_at' => $endsAt, 'is_available' => true, 'sort_order' => $sortOrder]
            );
        }

        $eventTypes = [
            ['Initial Mentor Call', 'initial-mentor-call', 30, 'mentor_session', false, 'Start the mentor relationship, clarify expectations, and confirm the apprentice first-week action plan.'],
            ['Field Apprenticeship Session', 'field-apprenticeship-session', 60, 'field_apprenticeship', true, 'Structured FAP working session connected to apprentice checklist progress.'],
            ['Prospect Call Support', 'prospect-call-support', 45, 'prospect_support', true, 'Prepare for or receive support on a prospect conversation.'],
            ['Practice Presentation', 'practice-presentation', 45, 'mentor_session', false, 'Practice the Greatway story, scripts, and presentation flow before field activity.'],
            ['Licensing Study Session', 'licensing-study-session', 60, 'licensing', false, 'Focused study support for licensing readiness and exam preparation.'],
            ['Rank Advancement Coaching', 'rank-advancement-coaching', 45, 'rank_coaching', true, 'Review rank requirements, activity, and next actions for advancement.'],
            ['Weekly Progress Review', 'weekly-progress-review', 30, 'mentor_session', false, 'Weekly accountability review for progress, blockers, and next commitments.'],
            ['Final Apprenticeship Review', 'final-apprenticeship-review', 60, 'field_apprenticeship', true, 'Final FAP review before mentor completion recommendation.'],
        ];

        $createdTypes = collect($eventTypes)->mapWithKeys(function (array $seed) use ($cfm, $category): array {
            [$title, $slug, $duration, $eventCategory, $approvalRequired, $description] = $seed;

            $type = BookingEventType::query()->updateOrCreate(
                ['owner_id' => $cfm->id, 'slug' => $slug],
                [
                    'calendar_category_id' => $category->id,
                    'title' => $title,
                    'description' => $description,
                    'duration_minutes' => $duration,
                    'event_category' => $eventCategory,
                    'location_type' => 'zoom',
                    'meeting_link' => 'https://zoom.us/j/gwtracks-cfm',
                    'approval_required' => $approvalRequired,
                    'is_active' => true,
                    'visibility' => 'assigned_apprentices',
                    'color' => '#84BB41',
                    'buffer_before_minutes' => 10,
                    'buffer_after_minutes' => 10,
                    'minimum_notice_minutes' => 720,
                    'maximum_booking_days_ahead' => 30,
                    'daily_booking_limit' => 6,
                    'weekly_booking_limit' => 20,
                    'allowed_attendee_type' => 'assigned_apprentices',
                    'custom_questions_enabled' => true,
                    'confirmation_message' => 'Your mentor session is confirmed. Please bring your latest progress notes and questions.',
                    'cancellation_policy' => 'Trainees may reschedule up to 12 hours before the session. CFMs may reschedule anytime with a reason.',
                ]
            );

            foreach ($this->defaultQuestions() as $question) {
                BookingQuestion::query()->updateOrCreate(
                    ['booking_event_type_id' => $type->id, 'question' => $question['question']],
                    $question
                );
            }

            return [$slug => $type];
        });

        foreach ($createdTypes as $slug => $type) {
            BookingLink::query()->updateOrCreate(
                ['owner_id' => $cfm->id, 'booking_event_type_id' => $type->id, 'link_type' => 'event_type'],
                [
                    'availability_schedule_id' => $schedule->id,
                    'name' => $type->title.' Link',
                    'slug' => $slug,
                    'token' => Str::slug($cfm->name).'-'.$slug,
                    'visibility' => 'private',
                    'is_active' => true,
                    'is_one_time' => false,
                    'expires_at' => null,
                    'max_uses' => null,
                ]
            );
        }

        BookingLink::query()->updateOrCreate(
            ['owner_id' => $cfm->id, 'apprentice_id' => $trainee->id, 'link_type' => 'apprentice'],
            [
                'booking_event_type_id' => $createdTypes['field-apprenticeship-session']->id,
                'availability_schedule_id' => $schedule->id,
                'name' => 'Apprentice Booking Link - '.$trainee->name,
                'slug' => 'apprentice-'.$trainee->id,
                'token' => 'apprentice-'.$trainee->id.'-'.$cfm->id,
                'visibility' => 'invite_only',
                'is_active' => true,
                'is_one_time' => false,
                'expires_at' => now()->addDays(45),
                'max_uses' => null,
            ]
        );

        $this->bookingScenario(
            $createdTypes['weekly-progress-review'],
            $schedule,
            $calendarType,
            $cfm,
            $trainee,
            'confirmed',
            now()->addDays(3)->setTime(10, 0)
        );

        $this->bookingScenario(
            $createdTypes['field-apprenticeship-session'],
            $schedule,
            $calendarType,
            $cfm,
            $trainee,
            'pending_approval',
            now()->addDays(5)->setTime(14, 0)
        );
    }

    private function defaultQuestions(): array
    {
        return [
            ['question' => 'What topic would you like help with?', 'type' => 'long_answer', 'options' => null, 'is_required' => true, 'sort_order' => 10, 'is_active' => true],
            ['question' => 'Is this related to a prospect?', 'type' => 'multiple_choice', 'options' => ['Yes', 'No'], 'is_required' => false, 'sort_order' => 20, 'is_active' => true],
            ['question' => 'What is your biggest challenge right now?', 'type' => 'long_answer', 'options' => null, 'is_required' => false, 'sort_order' => 30, 'is_active' => true],
            ['question' => 'Would you like your CFM to join a prospect call?', 'type' => 'multiple_choice', 'options' => ['Yes', 'No', 'Not sure yet'], 'is_required' => false, 'sort_order' => 40, 'is_active' => true],
        ];
    }

    private function bookingScenario(BookingEventType $type, AvailabilitySchedule $schedule, CalendarEventType $calendarType, User $cfm, User $trainee, string $status, mixed $startsAt): void
    {
        $endsAt = $startsAt->copy()->addMinutes($type->duration_minutes);
        $calendarEvent = null;

        if ($status === 'confirmed') {
            $calendarEvent = CalendarEvent::query()->updateOrCreate(
                ['title' => $type->title.' - '.$trainee->name, 'organizer_id' => $cfm->id, 'starts_at' => $startsAt],
                [
                    'calendar_event_type_id' => $calendarType->id,
                    'calendar_category_id' => $calendarType->calendar_category_id,
                    'description' => 'Auto-created from a confirmed CFM booking session.',
                    'ends_at' => $endsAt,
                    'timezone' => 'America/Vancouver',
                    'is_all_day' => false,
                    'is_recurring' => false,
                    'location' => null,
                    'meeting_link' => $type->meeting_link,
                    'visibility' => 'shared_team',
                    'status' => 'scheduled',
                    'color' => $type->color,
                    'related_apprentice_id' => $trainee->id,
                    'notes' => 'Generated from BookingSchedulingSeeder.',
                ]
            );

            foreach ([$cfm, $trainee] as $attendee) {
                CalendarEventAttendee::query()->updateOrCreate(
                    ['calendar_event_id' => $calendarEvent->id, 'user_id' => $attendee->id],
                    ['attendee_type' => 'user', 'rsvp_status' => 'accepted', 'responded_at' => now()]
                );
            }

            CalendarEventReminder::query()->updateOrCreate(
                ['calendar_event_id' => $calendarEvent->id, 'user_id' => $trainee->id, 'minutes_before' => 30],
                ['channel' => 'both']
            );
        }

        $booking = Booking::query()->updateOrCreate(
            ['booking_event_type_id' => $type->id, 'cfm_id' => $cfm->id, 'trainee_id' => $trainee->id, 'starts_at' => $startsAt],
            [
                'public_id' => (string) Str::ulid(),
                'availability_schedule_id' => $schedule->id,
                'calendar_event_id' => $calendarEvent?->id,
                'status' => $status,
                'ends_at' => $endsAt,
                'timezone' => 'America/Vancouver',
                'location_type' => $type->location_type,
                'meeting_link' => $type->meeting_link,
                'reason' => 'Seeded mentor scheduling scenario.',
                'topics' => 'FAP progress, prospect support, and next field activity.',
                'confirmed_at' => $status === 'confirmed' ? now() : null,
            ]
        );

        foreach ([[$cfm, 'cfm'], [$trainee, 'trainee']] as [$user, $typeLabel]) {
            BookingAttendee::query()->updateOrCreate(
                ['booking_id' => $booking->id, 'user_id' => $user->id],
                ['name' => $user->name, 'email' => $user->email, 'attendee_type' => $typeLabel, 'rsvp_status' => $status === 'confirmed' ? 'accepted' : 'pending']
            );
        }

        BookingActivityLog::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'action' => 'seeded'],
            ['user_id' => $cfm->id, 'payload' => ['status' => $status]]
        );
    }

    private function member(string $email, string $name, string $role, ?int $teamId, ?int $rankId, ?int $sponsorId = null): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'team_id' => $teamId,
                'rank_id' => $rankId,
                'sponsor_id' => $sponsorId,
                'joined_at' => now()->subDays(20),
                'is_active' => true,
                'is_online' => false,
            ]
        );

        $user->assignRole($role);

        return $user;
    }
}
