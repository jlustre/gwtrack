<?php

namespace Tests\Feature;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CfmMentorProfile;
use App\Models\User;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmCalendarSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cfm_can_update_calendar_sharing_preferences(): void
    {
        $this->seedCalendar();

        $cfm = User::where('email', 'calendar-cfm@gwtracks.com')->firstOrFail();
        CfmMentorProfile::firstOrCreate(['user_id' => $cfm->id], [
            'certification_status' => 'certified',
            'max_apprentices' => 6,
        ]);

        $this->actingAs($cfm)
            ->patch(route('cfm.portal.calendar-sharing.update'), [
                'share_calendar_with_apprentices' => '1',
                'share_calendar_with_agency_owner' => '1',
            ])
            ->assertRedirect(route('cfm.portal'));

        $cfm->cfmMentorProfile->refresh();
        $this->assertTrue($cfm->cfmMentorProfile->share_calendar_with_apprentices);
        $this->assertTrue($cfm->cfmMentorProfile->share_calendar_with_agency_owner);
    }

    public function test_trainee_can_see_shared_cfm_calendar_events(): void
    {
        $this->seedCalendar();

        $cfm = User::where('email', 'calendar-cfm@gwtracks.com')->firstOrFail();
        $trainee = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $category = CalendarCategory::where('slug', 'field-apprenticeship')->firstOrFail();

        $trainee->update(['mentor_id' => $cfm->id]);

        CfmMentorProfile::updateOrCreate(['user_id' => $cfm->id], [
            'certification_status' => 'certified',
            'max_apprentices' => 6,
            'share_calendar_with_apprentices' => true,
            'share_calendar_with_agency_owner' => false,
        ]);

        CalendarEvent::create([
            'calendar_category_id' => $category->id,
            'organizer_id' => $cfm->id,
            'title' => 'FAP Coaching Block',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'timezone' => 'PST',
            'visibility' => 'private',
            'status' => 'scheduled',
            'color' => '#9333EA',
        ]);

        CalendarEvent::create([
            'calendar_category_id' => $category->id,
            'organizer_id' => $cfm->id,
            'title' => 'Trainee Planning Session',
            'starts_at' => now()->addDays(2)->setTime(14, 0),
            'ends_at' => now()->addDays(2)->setTime(15, 0),
            'timezone' => 'PST',
            'visibility' => 'shared_team',
            'status' => 'scheduled',
            'color' => '#9333EA',
        ]);

        $this->actingAs($trainee)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Shared Mentor Calendars')
            ->assertSee('Calendar Certified Field Mentor')
            ->assertSee('Trainee Planning Session')
            ->assertDontSee('FAP Coaching Block');
    }

    public function test_agency_owner_can_see_shared_cfm_calendar_when_enabled(): void
    {
        $this->seedCalendar();

        $owner = User::where('email', 'calendar-owner@gwtracks.com')->firstOrFail();
        $cfm = User::where('email', 'calendar-cfm@gwtracks.com')->firstOrFail();
        $category = CalendarCategory::where('slug', 'team')->firstOrFail();

        CfmMentorProfile::updateOrCreate(['user_id' => $cfm->id], [
            'certification_status' => 'certified',
            'max_apprentices' => 6,
            'share_calendar_with_apprentices' => false,
            'share_calendar_with_agency_owner' => true,
        ]);

        CalendarEvent::create([
            'calendar_category_id' => $category->id,
            'organizer_id' => $cfm->id,
            'title' => 'CFM Leadership Sync',
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(9, 30),
            'timezone' => 'PST',
            'visibility' => 'shared_team',
            'status' => 'scheduled',
            'color' => '#2D3E50',
        ]);

        $this->actingAs($owner)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('CFM Leadership Sync');
    }

    public function test_trainee_cannot_see_cfm_calendar_when_sharing_disabled(): void
    {
        $this->seedCalendar();

        $cfm = User::where('email', 'calendar-cfm@gwtracks.com')->firstOrFail();
        $trainee = User::where('email', 'calendar-member@gwtracks.com')->firstOrFail();
        $category = CalendarCategory::where('slug', 'team')->firstOrFail();

        $trainee->update(['mentor_id' => $cfm->id]);

        CfmMentorProfile::updateOrCreate(['user_id' => $cfm->id], [
            'certification_status' => 'certified',
            'max_apprentices' => 6,
            'share_calendar_with_apprentices' => false,
            'share_calendar_with_agency_owner' => false,
        ]);

        CalendarEvent::create([
            'calendar_category_id' => $category->id,
            'organizer_id' => $cfm->id,
            'title' => 'Hidden Mentor Session',
            'starts_at' => now()->addDay()->setTime(13, 0),
            'ends_at' => now()->addDay()->setTime(14, 0),
            'timezone' => 'PST',
            'visibility' => 'shared_team',
            'status' => 'scheduled',
            'color' => '#2D3E50',
        ]);

        $this->actingAs($trainee)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertDontSee('Hidden Mentor Session')
            ->assertDontSee('Shared Mentor Calendars');
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
