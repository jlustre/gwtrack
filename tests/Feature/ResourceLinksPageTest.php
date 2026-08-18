<?php

namespace Tests\Feature;

use App\Models\PortalResource;
use App\Models\User;
use App\Services\DocumentLinkSyncService;
use Database\Seeders\RankSeeder;
use Database\Seeders\ResourceDocumentSeeder;
use Database\Seeders\ResourceLinkSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceLinksPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedLinksLibrary(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            ResourceDocumentSeeder::class,
            ResourceLinkSeeder::class,
        ]);
    }

    public function test_member_can_view_links_library(): void
    {
        $this->seedLinksLibrary();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.links'))
            ->assertOk()
            ->assertSee('Links', false)
            ->assertSee('Weekly Team Huddle', false)
            ->assertSee('Description', false)
            ->assertSee('Open', false);
    }

    public function test_links_page_defaults_to_table_view(): void
    {
        $this->seedLinksLibrary();

        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get(route('resources.links'));

        $response->assertOk();
        $this->assertStringContainsString("viewMode: 'table'", $response->getContent());
        $this->assertStringContainsString('x-show="viewMode === \'table\'"', $response->getContent());
    }

    public function test_document_pdf_links_are_synced_into_links_library(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        PortalResource::query()->create([
            'title' => 'Links Reference Packet',
            'type' => 'document',
            'category' => 'onboarding',
            'sort_order' => 1,
            'content' => '<p><a href="https://zoom.us/j/sync-test">Synced Training Room</a></p>',
            'is_published' => true,
        ]);

        app(DocumentLinkSyncService::class)->syncAll();

        $this->actingAs($user)
            ->get(route('resources.links'))
            ->assertOk()
            ->assertSee('Synced Training Room', false);
    }

    public function test_links_can_be_filtered_by_category(): void
    {
        $this->seedLinksLibrary();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.links', ['category' => 'training']))
            ->assertOk()
            ->assertSee('New Associate Fast Start', false)
            ->assertDontSee('Weekly Team Huddle', false);
    }

    public function test_legacy_zoom_links_route_redirects_to_links_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get('/resources/zoom-links')
            ->assertRedirect('/resources/links');
    }
}
