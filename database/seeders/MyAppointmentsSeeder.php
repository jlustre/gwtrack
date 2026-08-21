<?php

namespace Database\Seeders;

use App\Models\AvailabilitySchedule;
use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\BookingAttendee;
use App\Models\BookingEventType;
use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MyAppointmentsSeeder extends Seeder
{
    /**
     * Seed a rich set of mentor appointments for My Appointments testing.
     *
     * Login:
     *   booking-trainee@gwtrack.com / password  (trainee view)
     *   booking-cfm@gwtrack.com / password      (CFM view)
     *
     * Run:
     *   php artisan db:seed --class=MyAppointmentsSeeder
     */
    public function run(): void
    {
        $this->call([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            CalendarModuleSeeder::class,
            BookingSchedulingSeeder::class,
        ]);

        $cfm = User::query()->where('email', 'booking-cfm@gwtrack.com')->firstOrFail();
        $trainee = User::query()->where('email', 'booking-trainee@gwtrack.com')->firstOrFail();
        $guest = User::query()->where('email', 'calendar-member@gwtrack.com')->first()
            ?? User::query()->whereKeyNot([$cfm->id, $trainee->id])->first();

        $schedule = AvailabilitySchedule::query()
            ->where('user_id', $cfm->id)
            ->where('is_default', true)
            ->firstOrFail();

        $eventTypes = BookingEventType::query()
            ->where('owner_id', $cfm->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        if ($eventTypes->isEmpty()) {
            $this->command?->error('No booking event types found after BookingSchedulingSeeder.');

            return;
        }

        $category = CalendarCategory::query()->firstOrCreate(
            ['slug' => 'cfm-mentor-sessions'],
            ['name' => 'CFM Mentor Sessions', 'color' => '#84BB41', 'icon' => 'award', 'sort_order' => 75, 'is_active' => true]
        );

        $calendarType = CalendarEventType::query()->firstOrCreate(
            ['slug' => 'cfm-booking-session'],
            ['calendar_category_id' => $category->id, 'name' => 'CFM Booking Session', 'color' => '#84BB41', 'sort_order' => 75, 'is_active' => true]
        );

        $scenarios = [
            ['confirmed', now()->subDays(21)->setTime(9, 0), 'Past confirmed weekly review', 'Reviewed FAP checklist and next prospect list.'],
            ['completed', now()->subDays(18)->setTime(11, 30), 'Completed licensing study block', 'Finished chapter review and practice questions.'],
            ['completed', now()->subDays(14)->setTime(15, 0), 'Completed practice presentation', 'Presented Greatway story; coaching notes attached.'],
            ['no_show', now()->subDays(12)->setTime(10, 0), 'Trainee no-show', 'Trainee did not join Zoom; follow-up required.'],
            ['cancelled', now()->subDays(10)->setTime(16, 0), 'Cancelled prospect support', 'Prospect rescheduled; session cancelled by trainee.'],
            ['declined', now()->subDays(8)->setTime(13, 0), 'Declined rank coaching', 'CFM unavailable; trainee asked to rebook.'],
            ['confirmed', now()->subDays(5)->setTime(9, 30), 'Past confirmed mentor call', 'Aligned on week goals and activity targets.'],
            ['completed', now()->subDays(2)->setTime(14, 0), 'Just completed FAP session', 'Walked through field activity and objections.'],
            ['pending_approval', now()->addDays(1)->setTime(9, 0), 'Pending initial mentor call', 'First-week expectations and onboarding plan.'],
            ['confirmed', now()->addDays(2)->setTime(10, 30), 'Confirmed prospect call support', 'Join trainee on a warm prospect conversation.'],
            ['confirmed', now()->addDays(4)->setTime(18, 0), 'Evening progress review', 'Weekly accountability and blocker review.'],
            ['pending_approval', now()->addDays(6)->setTime(11, 0), 'Pending licensing study', 'Exam prep focus: life & A&S sections.'],
            ['confirmed', now()->addDays(7)->setTime(15, 30), 'Confirmed practice presentation', 'Dry-run before Saturday field activity.'],
            ['pending_approval', now()->addDays(9)->setTime(9, 30), 'Pending rank coaching', 'Review Associate activity gap and next actions.'],
            ['confirmed', now()->addDays(11)->setTime(13, 0), 'Confirmed FAP working session', 'Checklist items 4–6 and joint prospecting.'],
            ['confirmed', now()->addDays(13)->setTime(10, 0), 'Confirmed weekly progress review', 'Pipeline health and weekly commitments.'],
            ['pending_approval', now()->addDays(15)->setTime(16, 30), 'Pending final apprenticeship review', 'Prep for FAP completion recommendation.'],
            ['confirmed', now()->addDays(18)->setTime(11, 0), 'Confirmed prospect support follow-up', 'Debrief last call and next ask strategy.'],
            ['confirmed', now()->addDays(21)->setTime(14, 0), 'Confirmed mentor strategy block', '30-day activity plan and calendar blocking.'],
            ['pending_approval', now()->addDays(24)->setTime(9, 0), 'Pending morning mentor call', 'Quick sync before training weekend.'],
            ['confirmed', now()->addDays(27)->setTime(17, 0), 'Confirmed late-day coaching', 'Objection handling and closing language.'],
            ['confirmed', now()->addDays(30)->setTime(10, 0), 'Confirmed monthly checkpoint', 'Month-end scorecard and next-month goals.'],
        ];

        $created = 0;

        foreach ($scenarios as $index => [$status, $startsAt, $reason, $topics]) {
            $type = $eventTypes[$index % $eventTypes->count()];
            $this->seedAppointment(
                type: $type,
                schedule: $schedule,
                calendarType: $calendarType,
                cfm: $cfm,
                trainee: $trainee,
                status: $status,
                startsAt: $startsAt,
                reason: $reason,
                topics: $topics,
                guest: $index % 5 === 0 ? $guest : null,
            );
            $created++;
        }

        $this->command?->info("My Appointments seeder ready ({$created} scenario appointments).");
        $this->command?->info('Login as booking-trainee@gwtrack.com or booking-cfm@gwtrack.com (password: password)');
        $this->command?->info('Open: '.url('/bookings/my-appointments'));
    }

    private function seedAppointment(
        BookingEventType $type,
        AvailabilitySchedule $schedule,
        CalendarEventType $calendarType,
        User $cfm,
        User $trainee,
        string $status,
        mixed $startsAt,
        string $reason,
        string $topics,
        ?User $guest = null,
    ): void {
        $endsAt = $startsAt->copy()->addMinutes($type->duration_minutes);
        $calendarEvent = null;

        if (in_array($status, ['confirmed', 'completed'], true)) {
            $calendarEvent = CalendarEvent::query()->updateOrCreate(
                [
                    'title' => '[My Appointments] '.$type->title.' - '.$trainee->name,
                    'organizer_id' => $cfm->id,
                    'starts_at' => $startsAt,
                ],
                [
                    'calendar_event_type_id' => $calendarType->id,
                    'calendar_category_id' => $calendarType->calendar_category_id,
                    'description' => $topics,
                    'ends_at' => $endsAt,
                    'timezone' => 'America/Vancouver',
                    'is_all_day' => false,
                    'is_recurring' => false,
                    'location' => $type->location_type === 'zoom' ? 'Zoom' : null,
                    'meeting_link' => $type->meeting_link,
                    'visibility' => 'shared_team',
                    'status' => $status === 'completed' ? 'completed' : 'scheduled',
                    'color' => $type->color ?? '#84BB41',
                    'related_apprentice_id' => $trainee->id,
                    'notes' => 'Generated from MyAppointmentsSeeder.',
                ]
            );

            foreach ([$cfm, $trainee] as $attendee) {
                CalendarEventAttendee::query()->updateOrCreate(
                    ['calendar_event_id' => $calendarEvent->id, 'user_id' => $attendee->id],
                    ['attendee_type' => 'user', 'rsvp_status' => 'accepted', 'responded_at' => now()]
                );
            }
        }

        $booking = Booking::query()->updateOrCreate(
            [
                'booking_event_type_id' => $type->id,
                'cfm_id' => $cfm->id,
                'trainee_id' => $trainee->id,
                'starts_at' => $startsAt,
            ],
            [
                'public_id' => (string) Str::ulid(),
                'availability_schedule_id' => $schedule->id,
                'calendar_event_id' => $calendarEvent?->id,
                'status' => $status,
                'ends_at' => $endsAt,
                'timezone' => 'America/Vancouver',
                'location_type' => $type->location_type ?: 'zoom',
                'location_details' => $type->location_type === 'zoom' ? 'Zoom mentor room' : 'Office meeting room',
                'meeting_link' => $type->meeting_link ?: 'https://zoom.us/j/gwtrack-cfm',
                'reason' => $reason,
                'topics' => $topics,
                'confirmed_at' => in_array($status, ['confirmed', 'completed'], true) ? $startsAt->copy()->subDay() : null,
                'completed_at' => $status === 'completed' ? $endsAt : null,
                'cancelled_at' => $status === 'cancelled' ? $startsAt->copy()->subHours(6) : null,
                'declined_at' => $status === 'declined' ? $startsAt->copy()->subDay() : null,
                'no_show_at' => $status === 'no_show' ? $startsAt->copy()->addMinutes(15) : null,
            ]
        );

        foreach ([[$cfm, 'cfm'], [$trainee, 'trainee']] as [$user, $typeLabel]) {
            BookingAttendee::query()->updateOrCreate(
                ['booking_id' => $booking->id, 'user_id' => $user->id],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'attendee_type' => $typeLabel,
                    'rsvp_status' => in_array($status, ['confirmed', 'completed'], true) ? 'accepted' : 'pending',
                    'responded_at' => in_array($status, ['confirmed', 'completed'], true) ? now() : null,
                ]
            );
        }

        if ($guest) {
            BookingAttendee::query()->updateOrCreate(
                ['booking_id' => $booking->id, 'user_id' => $guest->id],
                [
                    'name' => $guest->name,
                    'email' => $guest->email,
                    'attendee_type' => 'guest',
                    'rsvp_status' => 'accepted',
                    'responded_at' => now(),
                ]
            );
        }

        BookingActivityLog::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'action' => 'my_appointments_seeded'],
            ['user_id' => $cfm->id, 'payload' => ['status' => $status, 'reason' => $reason]]
        );
    }
}
