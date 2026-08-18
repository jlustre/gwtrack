<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Livewire\Support\SupportTicketWizard;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SupportModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_associate_can_submit_support_ticket_via_wizard(): void
    {
        $this->seed([RolePermissionSeeder::class, SupportModuleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('associate');

        Livewire::actingAs($user)
            ->test(SupportTicketWizard::class)
            ->set('track', 'standard')
            ->set('type', 'bug')
            ->set('module', 'dashboard')
            ->set('category', 'not_loading')
            ->call('nextStep')
            ->set('user_intent_action', 'view')
            ->set('user_reported_outcome', 'error_shown')
            ->set('subject', 'Dashboard blank')
            ->set('description', 'When I open the dashboard the widgets never load for me.')
            ->call('nextStep')
            ->set('urgency', 'high')
            ->set('impact', 'self')
            ->set('frequency', 'always')
            ->set('device', 'desktop')
            ->set('browser', 'chrome')
            ->call('nextStep')
            ->call('nextStep')
            ->call('submit')
            ->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'subject' => 'Dashboard blank',
            'type' => 'bug',
            'module' => 'dashboard',
        ]);

        $ticket = SupportTicket::first();
        $this->assertStringStartsWith('GW-', $ticket->ticket_number);
    }

    public function test_support_index_requires_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('support.index'))
            ->assertForbidden();
    }

    public function test_documentation_guide_renders_heading_anchors_and_cross_links(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('associate');

        $this->actingAs($user)
            ->get(route('support.documentation', 'fna-management'))
            ->assertOk()
            ->assertSee('id="13-client-portal-invites"', false)
            ->assertSee('href="/support/documentation/prospect-sales-funnel#22-fna-integration"', false);
    }
}
