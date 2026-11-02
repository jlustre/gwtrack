<?php

namespace Tests\Feature;

use App\Livewire\Calendar\CalendarPage;
use App\Models\CalendarEvent;
use App\Models\CalendarCategory;
use App\Models\User;
use App\Models\UserCalendarPreference;
use App\Services\CalendarEventDisplayService;
use Carbon\CarbonImmutable;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_open_calendar_views_with_seeded_events(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();

        foreach (['calendar.index', 'calendar.month', 'calendar.week', 'calendar.day', 'calendar.agenda', 'events.index'] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('GWTracks Calendar')
                ->assertSeeText('Calendar & Events');
        }
    }

    public function test_month_view_day_cells_open_day_schedule_popup(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('calendar.month'))
            ->assertOk()
            ->assertSee('data-calendar-day-cell', false)
            ->assertSee('cursor-pointer', false)
            ->assertSee('hover:border-gw-accent', false)
            ->assertSee('openDayPopup(', false)
            ->assertSee('Day Schedule', false)
            ->assertSee('Add event', false)
            ->assertSee('openCreateForDay()', false);

        $html = $response->getContent();
        $this->assertTrue(str_contains($html, 'data-calendar-main-panel'), 'Calendar main panel marker missing');
        $this->assertSame(1, substr_count($html, '<main'), 'Nested main tags break the app layout');
    }

    public function test_calendar_event_detail_is_visible_to_invited_attendee(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $event = CalendarEvent::where('title', 'Monday Agency Leadership Huddle')->firstOrFail();

        $this->actingAs($user)
            ->get(route('calendar.events.show', $event))
            ->assertOk()
            ->assertSee('Monday Agency Leadership Huddle')
            ->assertSee('Attendees');
    }

    public function test_private_calendar_event_is_hidden_from_unrelated_member(): void
    {
        $this->seedCalendar();

        $unrelated = User::factory()->create();
        $unrelated->assignRole('member');

        $event = CalendarEvent::where('title', 'Prospect Appointment: Career Overview')->firstOrFail();

        $this->actingAs($unrelated)
            ->get(route('calendar.events.show', $event))
            ->assertForbidden();
    }

    public function test_calendar_export_downloads_visible_events(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('calendar.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('"Title","Type","Category"', false);
    }

    public function test_member_can_create_calendar_event_from_modal_form(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $attendee = User::where('email', 'calendar-leader@gwtracks.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Add Calendar Event')
            ->assertSee('Repeat Schedule')
            ->assertSee('No typing needed')
            ->assertSee('Save Schedule');

        $response = $this->actingAs($user)
            ->post(route('calendar.store'), [
                'title' => 'New Client Strategy Session',
                'description' => 'Review prospect goals and prepare next steps.',
                'starts_at' => now()->addDays(2)->setTime(14, 0)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->setTime(15, 0)->format('Y-m-d H:i:s'),
                'timezone' => 'PST',
                'visibility' => 'shared_team',
                'status' => 'scheduled',
                'location' => 'Virtual',
                'is_recurring' => '1',
                'recurrence_frequency' => 'weekly',
                'recurrence_interval' => 2,
                'recurrence_weekdays' => ['MO', 'WE'],
                'recurrence_end_type' => 'after',
                'recurrence_ends_after_occurrences' => 8,
                'attendee_user_ids' => [$attendee->id],
                'reminder_minutes' => [15, 60],
                'reminder_channel' => 'in_app',
                'notes' => 'Created from modal test.',
                'return_to' => route('calendar.index'),
            ]);

        $response->assertRedirect(route('calendar.index'));

        $event = CalendarEvent::where('title', 'New Client Strategy Session')->firstOrFail();

        $this->assertSame($user->id, $event->organizer_id);
        $this->assertTrue($event->is_recurring);
        $this->assertSame('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE;COUNT=8', $event->recurrence_rule);
        $this->assertDatabaseHas('calendar_event_recurrences', [
            'calendar_event_id' => $event->id,
            'frequency' => 'weekly',
            'interval' => 2,
            'ends_after_occurrences' => 8,
        ]);
        $this->assertDatabaseHas('calendar_event_attendees', [
            'calendar_event_id' => $event->id,
            'user_id' => $attendee->id,
            'rsvp_status' => 'pending',
        ]);
        $this->assertDatabaseHas('calendar_event_reminders', [
            'calendar_event_id' => $event->id,
            'minutes_before' => 60,
            'channel' => 'in_app',
        ]);
    }

    public function test_my_calendars_checkboxes_filter_visible_events(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $training = CalendarCategory::where('slug', 'training')->firstOrFail();
        $personal = CalendarCategory::where('slug', 'personal')->whereNull('user_id')->firstOrFail();

        $this->assertSame(
            [$personal->id],
            UserCalendarPreference::where('user_id', $user->id)->firstOrFail()->visible_calendar_categories
        );

        Livewire::actingAs($user)
            ->test(CalendarPage::class, ['viewMode' => 'month', 'date' => now()->toDateString()])
            ->set('selectedCalendarIds', [$training->id])
            ->assertSee('New Associate Fast Start', false)
            ->assertDontSee('Licensing Milestone Review');

        $this->assertSame(
            [$training->id],
            UserCalendarPreference::where('user_id', $user->id)->firstOrFail()->visible_calendar_categories
        );
    }

    public function test_new_user_gets_default_calendar_preferences_on_first_calendar_visit(): void
    {
        $this->seedCalendar();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->assertDatabaseMissing('user_calendar_preferences', ['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk();

        $preference = UserCalendarPreference::where('user_id', $user->id)->firstOrFail();
        $personalId = CalendarCategory::query()
            ->where('slug', 'personal')
            ->whereNull('user_id')
            ->value('id');

        $this->assertSame([(int) $personalId], $preference->visible_calendar_categories);
        $this->assertSame('month', $preference->default_view);
    }

    public function test_admin_can_customize_and_delete_calendar_category(): void
    {
        $this->seedCalendar();

        $admin = User::where('email', 'calendar-owner@gwtracks.com')->firstOrFail();
        $category = CalendarCategory::where('slug', 'team')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('calendar.categories.update', $category), [
                'name' => 'Agency Calendar',
                'color' => '#123ABC',
                'return_to' => route('calendar.index'),
            ])
            ->assertRedirect(route('calendar.index'));

        $this->assertDatabaseHas('calendar_categories', [
            'id' => $category->id,
            'name' => 'Agency Calendar',
            'color' => '#123ABC',
        ]);

        $this->actingAs($admin)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('#123ABC', false);

        $this->actingAs($admin)
            ->delete(route('calendar.categories.destroy', $category), [
                'return_to' => route('calendar.index'),
            ])
            ->assertRedirect(route('calendar.index'));

        $this->assertSoftDeleted('calendar_categories', ['id' => $category->id]);
    }

    public function test_member_can_create_personal_calendar_category(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();

        $this->actingAs($user)
            ->post(route('calendar.categories.store'), [
                'name' => 'Client Follow-ups',
                'color' => '#AABBCC',
                'return_to' => route('calendar.index'),
            ])
            ->assertRedirect(route('calendar.index'));

        $category = CalendarCategory::where('name', 'Client Follow-ups')->firstOrFail();

        $this->assertSame($user->id, $category->user_id);
        $this->assertFalse($category->is_public);
        $this->assertContains($category->id, UserCalendarPreference::where('user_id', $user->id)->firstOrFail()->visible_calendar_categories);

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Client Follow-ups');
    }

    public function test_private_calendar_category_is_hidden_from_other_users(): void
    {
        $this->seedCalendar();

        $owner = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $other = User::where('email', 'calendar-leader@gwtracks.com')->firstOrFail();

        $category = CalendarCategory::create([
            'user_id' => $owner->id,
            'name' => 'Secret Pipeline',
            'slug' => 'u'.$owner->id.'-secret-pipeline',
            'color' => '#112233',
            'icon' => 'calendar',
            'sort_order' => 910,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->actingAs($other)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertDontSee('Secret Pipeline');

        $this->assertFalse(
            CalendarCategory::query()->visibleTo($other)->whereKey($category->id)->exists()
        );
    }

    public function test_public_calendar_category_is_visible_to_other_users(): void
    {
        $this->seedCalendar();

        $owner = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $other = User::where('email', 'calendar-leader@gwtracks.com')->firstOrFail();

        CalendarCategory::create([
            'user_id' => $owner->id,
            'name' => 'Shared Coaching',
            'slug' => 'u'.$owner->id.'-shared-coaching',
            'color' => '#445566',
            'icon' => 'calendar',
            'sort_order' => 920,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->actingAs($other)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Shared Coaching')
            ->assertSee('Public');
    }

    public function test_member_can_update_and_delete_own_calendar_category(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();

        $category = CalendarCategory::create([
            'user_id' => $user->id,
            'name' => 'My Tasks',
            'slug' => 'u'.$user->id.'-my-tasks',
            'color' => '#778899',
            'icon' => 'calendar',
            'sort_order' => 930,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('calendar.categories.update', $category), [
                'name' => 'My Priorities',
                'color' => '#99AABB',
                'is_public' => '1',
                'return_to' => route('calendar.index'),
            ])
            ->assertRedirect(route('calendar.index'));

        $category->refresh();
        $this->assertSame('My Priorities', $category->name);
        $this->assertTrue($category->is_public);

        $this->actingAs($user)
            ->delete(route('calendar.categories.destroy', $category), [
                'return_to' => route('calendar.index'),
            ])
            ->assertRedirect(route('calendar.index'));

        $this->assertSoftDeleted('calendar_categories', ['id' => $category->id]);
    }

    public function test_member_cannot_manage_another_users_calendar_category(): void
    {
        $this->seedCalendar();

        $owner = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $other = User::where('email', 'calendar-leader@gwtracks.com')->firstOrFail();

        $category = CalendarCategory::create([
            'user_id' => $owner->id,
            'name' => 'Owner Only',
            'slug' => 'u'.$owner->id.'-owner-only',
            'color' => '#AABBCC',
            'icon' => 'calendar',
            'sort_order' => 940,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->actingAs($other)
            ->patch(route('calendar.categories.update', $category), [
                'name' => 'Hijacked',
                'color' => '#000000',
                'return_to' => route('calendar.index'),
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('calendar.categories.destroy', $category), [
                'return_to' => route('calendar.index'),
            ])
            ->assertForbidden();
    }

    public function test_multi_day_event_spans_across_calendar_days(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $friday = CarbonImmutable::parse(now()->next('Friday')->toDateString());
        $category = CalendarCategory::where('slug', 'personal')->whereNull('user_id')->firstOrFail();

        CalendarEvent::create([
            'organizer_id' => $user->id,
            'calendar_category_id' => $category->id,
            'title' => 'GW Convention',
            'starts_at' => $friday->startOfDay(),
            'ends_at' => $friday->addDays(2)->endOfDay(),
            'timezone' => 'PST',
            'is_all_day' => true,
            'visibility' => 'private',
            'status' => 'scheduled',
            'color' => '#84BB41',
        ]);

        $this->actingAs($user)
            ->get(route('calendar.week', ['date' => $friday->toDateString()]))
            ->assertOk()
            ->assertSee('GW Convention', false);

        $event = CalendarEvent::where('title', 'GW Convention')->firstOrFail();
        $rangeStart = $friday->startOfMonth()->startOfWeek();
        $rangeEnd = $friday->endOfMonth()->endOfWeek();
        $segments = app(CalendarEventDisplayService::class)->segmentsByDate(
            collect([$event]),
            $rangeStart,
            $rangeEnd,
        );

        $saturday = $friday->addDay()->toDateString();
        $sunday = $friday->addDays(2)->toDateString();

        $this->assertSame('start', $segments[$friday->toDateString()]->first()['span_position']);
        $this->assertSame('middle', $segments[$saturday]->first()['span_position']);
        $this->assertSame('end', $segments[$sunday]->first()['span_position']);
    }

    public function test_multi_day_events_share_the_same_row_across_week_columns(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $friday = CarbonImmutable::parse(now()->next('Friday')->toDateString());
        $category = CalendarCategory::where('slug', 'training')->firstOrFail();

        CalendarEvent::create([
            'organizer_id' => $user->id,
            'calendar_category_id' => $category->id,
            'title' => 'GW Convention',
            'starts_at' => $friday->startOfDay(),
            'ends_at' => $friday->addDays(2)->endOfDay(),
            'timezone' => 'PST',
            'is_all_day' => true,
            'visibility' => 'private',
            'status' => 'scheduled',
            'color' => '#84BB41',
        ]);

        CalendarEvent::create([
            'organizer_id' => $user->id,
            'calendar_category_id' => $category->id,
            'title' => 'Friday Standup',
            'starts_at' => $friday->setTime(9, 0),
            'ends_at' => $friday->setTime(9, 30),
            'timezone' => 'PST',
            'is_all_day' => false,
            'visibility' => 'private',
            'status' => 'scheduled',
            'color' => '#84BB41',
        ]);

        $event = CalendarEvent::where('title', 'GW Convention')->firstOrFail();
        $segments = app(CalendarEventDisplayService::class)->segmentsByDate(
            collect([$event]),
            $friday->startOfWeek(),
            $friday->endOfWeek(),
        );

        $rows = app(\App\Services\CalendarSpanLayoutService::class)->spanningRows(
            collect([$friday, $friday->addDay(), $friday->addDays(2)]),
            [],
            [],
            $segments,
        );

        $this->assertCount(1, $rows);
        $this->assertSame('start', $rows[0][$friday->toDateString()]['payload']['span_position']);
        $this->assertSame('middle', $rows[0][$friday->copy()->addDay()->toDateString()]['payload']['span_position']);
        $this->assertSame('end', $rows[0][$friday->copy()->addDays(2)->toDateString()]['payload']['span_position']);
    }

    public function test_calendar_page_updates_view_and_date_without_full_reload(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $today = now()->toDateString();

        Livewire::actingAs($user)
            ->test(CalendarPage::class, ['viewMode' => 'month', 'date' => $today])
            ->assertSet('viewMode', 'month')
            ->call('setView', 'week')
            ->assertSet('viewMode', 'week')
            ->call('goToday')
            ->assertSet('date', $today)
            ->call('goNext')
            ->assertNotSet('date', $today)
            ->call('setView', 'day')
            ->assertSet('viewMode', 'day')
            ->assertSee('GWTracks Calendar');
    }

    public function test_calendar_page_toggles_selected_calendars_inline(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $training = CalendarCategory::where('slug', 'training')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->test(CalendarPage::class, ['viewMode' => 'month', 'date' => now()->toDateString()]);

        $wasSelected = in_array($training->id, $component->get('selectedCalendarIds'), true);

        $component->call('toggleCalendar', $training->id);

        $visible = UserCalendarPreference::where('user_id', $user->id)->firstOrFail()->visible_calendar_categories;

        $this->assertSame(! $wasSelected, in_array($training->id, $visible, true));
        $this->assertSame(! $wasSelected, in_array($training->id, $component->get('selectedCalendarIds'), true));

        $ids = collect($component->get('selectedCalendarIds'))
            ->map(fn ($id): int => (int) $id)
            ->values();

        $flippedIds = $ids->contains($training->id)
            ? $ids->reject(fn (int $id): bool => $id === $training->id)->values()->all()
            : $ids->push($training->id)->all();

        $component->set('selectedCalendarIds', $flippedIds);

        $this->assertSame($wasSelected, in_array($training->id, $component->get('selectedCalendarIds'), true));
        $this->assertSame(
            $wasSelected,
            in_array($training->id, UserCalendarPreference::where('user_id', $user->id)->firstOrFail()->visible_calendar_categories, true),
        );
    }

    public function test_calendar_page_creates_event_inline_without_redirect(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $attendee = User::where('email', 'calendar-leader@gwtracks.com')->firstOrFail();
        $starts = now()->addDays(3)->setTime(14, 0)->format('Y-m-d\TH:i');
        $ends = now()->addDays(3)->setTime(15, 0)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(CalendarPage::class, ['viewMode' => 'month', 'date' => now()->toDateString()])
            ->call('openCreateModal')
            ->assertSet('createOpen', true)
            ->set('eventForm.title', 'Inline Strategy Session')
            ->set('eventForm.starts_at', $starts)
            ->set('eventForm.ends_at', $ends)
            ->set('eventForm.timezone', 'PST')
            ->set('eventForm.visibility', 'shared_team')
            ->set('eventForm.status', 'scheduled')
            ->set('eventForm.is_recurring', true)
            ->set('eventForm.recurrence_frequency', 'weekly')
            ->set('eventForm.recurrence_interval', 2)
            ->set('eventForm.recurrence_weekdays', ['MO', 'WE'])
            ->set('eventForm.recurrence_end_type', 'after')
            ->set('eventForm.recurrence_ends_after_occurrences', 8)
            ->set('eventForm.attendee_user_ids', [$attendee->id])
            ->set('eventForm.reminder_minutes', [15, 60])
            ->set('eventForm.reminder_channel', 'in_app')
            ->set('eventForm.notes', 'Created from Livewire modal.')
            ->call('createEvent')
            ->assertSet('createOpen', false)
            ->assertSet('statusMessage', 'Calendar event created.')
            ->assertHasNoErrors();

        $event = CalendarEvent::where('title', 'Inline Strategy Session')->firstOrFail();
        $this->assertSame($user->id, $event->organizer_id);
        $this->assertTrue($event->is_recurring);
        $this->assertSame('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE;COUNT=8', $event->recurrence_rule);
    }

    public function test_calendar_page_opens_event_detail_modal_inline(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $event = CalendarEvent::where('title', 'Monday Agency Leadership Huddle')->firstOrFail();

        Livewire::actingAs($user)
            ->test(CalendarPage::class, ['viewMode' => 'month', 'date' => now()->toDateString()])
            ->assertSet('viewingEventId', null)
            ->call('openEventDetail', $event->id)
            ->assertSet('viewingEventId', $event->id)
            ->assertSee('Monday Agency Leadership Huddle')
            ->assertSee('Calendar Event')
            ->assertSee('Open full page')
            ->call('closeEventDetail')
            ->assertSet('viewingEventId', null);
    }

    public function test_calendar_page_manages_personal_categories_inline(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->test(CalendarPage::class, ['viewMode' => 'month', 'date' => now()->toDateString()])
            ->call('openAddCategoryModal')
            ->set('newCategoryName', 'Inline Personal Calendar')
            ->set('newCategoryColor', '#112233')
            ->set('newCategoryIsPublic', false)
            ->call('storeCategory')
            ->assertSet('addCategoryOpen', false)
            ->assertSet('statusMessage', 'Personal calendar created.');

        $category = CalendarCategory::where('name', 'Inline Personal Calendar')->where('user_id', $user->id)->firstOrFail();

        $component
            ->call('startEditCategory', $category->id)
            ->set('editCategoryName', 'Inline Personal Calendar Updated')
            ->set('editCategoryColor', '#445566')
            ->call('updateCategory')
            ->assertSet('editingCategoryId', null)
            ->assertSet('statusMessage', 'Calendar updated.');

        $this->assertDatabaseHas('calendar_categories', [
            'id' => $category->id,
            'name' => 'Inline Personal Calendar Updated',
            'color' => '#445566',
        ]);

        $component
            ->call('deleteCategory', $category->id)
            ->assertSet('statusMessage', 'Calendar deleted.');

        $this->assertSoftDeleted('calendar_categories', ['id' => $category->id]);
    }

    private function seedCalendar(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            CalendarModuleSeeder::class,
        ]);
    }
}
