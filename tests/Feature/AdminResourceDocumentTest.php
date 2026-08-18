<?php

namespace Tests\Feature;

use App\Models\PortalResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminResourceDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_resource_by_uploading_pdf_without_content(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pdf = UploadedFile::fake()->create('handbook.pdf', 120, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'resources'), [
                'title' => 'Uploaded Handbook',
                'description' => 'PDF-only document.',
                'type' => 'document',
                'category' => 'onboarding',
                'sort_order' => 12,
                'content' => null,
                'content_source' => 'upload',
                'url' => null,
                'is_published' => 1,
                'is_featured' => 0,
                'pdf_file' => $pdf,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'resource-pdf-uploaded');

        $resource = PortalResource::query()->where('title', 'Uploaded Handbook')->firstOrFail();

        $this->assertNull($resource->content);
        $this->assertNotNull($resource->file_path);
        $this->assertSame('storage/'.$resource->file_path, $resource->url);
        $this->assertSame('PDF', $resource->file_format);
        $this->assertNotNull($resource->pdf_generated_at);
        Storage::disk('public')->assertExists($resource->file_path);
    }

    public function test_admin_can_update_resource_by_uploading_pdf(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Replaceable Guide',
            'description' => 'Original summary.',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 3,
            'content' => '<p>Original body</p>',
            'is_published' => true,
        ]);

        $pdf = UploadedFile::fake()->create('replacement.pdf', 120, 'application/pdf');

        $this->actingAs($admin)
            ->patch(route('admin.management.update', ['resources', $resource->id]), [
                'title' => 'Replaceable Guide',
                'description' => 'Updated summary.',
                'type' => 'document',
                'category' => 'guides',
                'sort_order' => 3,
                'content' => null,
                'content_source' => 'upload',
                'url' => null,
                'is_published' => 1,
                'is_featured' => 0,
                'pdf_file' => $pdf,
            ])
            ->assertRedirect(route('admin.management.edit', ['resources', $resource->id]))
            ->assertSessionHas('status', 'resource-pdf-uploaded');

        $resource->refresh();

        $this->assertSame('Updated summary.', $resource->description);
        $this->assertNotNull($resource->file_path);
        $this->assertSame('PDF', $resource->file_format);
        Storage::disk('public')->assertExists($resource->file_path);
    }

    public function test_admin_can_create_resource_with_content_and_generate_pdf(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'resources'), [
                'title' => 'Associate Handbook',
                'description' => 'Core onboarding reference.',
                'type' => 'document',
                'category' => 'onboarding',
                'sort_order' => 10,
                'content' => '<p>Welcome to <strong>GWTrack</strong>.</p>',
                'url' => null,
                'is_published' => 1,
                'is_featured' => 0,
                'generate_pdf' => 1,
            ])
            ->assertRedirect();

        $resource = PortalResource::query()->where('title', 'Associate Handbook')->firstOrFail();

        $this->assertSame('<p>Welcome to <strong>GWTrack</strong>.</p>', $resource->content);
        $this->assertNotNull($resource->file_path);
        $this->assertSame('PDF', $resource->file_format);
        $this->assertNotNull($resource->pdf_generated_at);
        Storage::disk('public')->assertExists($resource->file_path);
    }

    public function test_generated_pdf_handles_emoji_in_document_content(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'resources'), [
                'title' => 'Emoji Packet',
                'description' => 'Welcome overview.',
                'type' => 'document',
                'category' => 'onboarding',
                'sort_order' => 11,
                'content' => '<h2>📘 Associate Welcome Packet</h2><p>🎉 Welcome to the team.</p>',
                'url' => null,
                'is_published' => 1,
                'is_featured' => 0,
                'generate_pdf' => 1,
            ])
            ->assertRedirect();

        $resource = PortalResource::query()->where('title', 'Emoji Packet')->firstOrFail();

        Storage::disk('public')->assertExists($resource->file_path);
        $this->assertSame('PDF', $resource->file_format);
    }

    public function test_admin_can_regenerate_resource_pdf(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Compliance Summary',
            'description' => 'Annual compliance overview.',
            'type' => 'document',
            'category' => 'compliance',
            'sort_order' => 5,
            'content' => '<p>Initial content.</p>',
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.management.resources.generate-pdf', $resource->id))
            ->assertRedirect(route('admin.management.edit', ['resources', $resource->id]))
            ->assertSessionHas('status', 'resource-pdf-generated');

        $resource->refresh();

        Storage::disk('public')->assertExists($resource->file_path);
        $this->assertSame('PDF', $resource->file_format);
    }

    public function test_admin_can_save_relative_external_url(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Welcome Packet',
            'type' => 'document',
            'category' => 'onboarding',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.management.update', ['resources', $resource->id]), [
                'title' => 'Welcome Packet',
                'description' => null,
                'type' => 'document',
                'category' => 'onboarding',
                'sort_order' => 1,
                'content' => null,
                'url' => 'resources/documents/welcome-packet.pdf',
                'is_published' => 1,
                'is_featured' => 0,
            ])
            ->assertRedirect(route('admin.management.edit', ['resources', $resource->id]));

        $resource->refresh();

        $this->assertSame('resources/documents/welcome-packet.pdf', $resource->url);
        $this->assertSame(
            url('/resources/documents/welcome-packet.pdf'),
            $resource->resolvedAccessUrl()
        );
    }

    public function test_admin_resources_index_can_filter_by_category(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        PortalResource::query()->create([
            'title' => 'Onboarding Packet',
            'type' => 'document',
            'category' => 'onboarding',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        PortalResource::query()->create([
            'title' => 'Call Script',
            'type' => 'document',
            'category' => 'scripts',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', ['resources', 'category' => 'scripts']))
            ->assertOk()
            ->assertSee('Call Script')
            ->assertDontSee('Onboarding Packet');
    }

    public function test_admin_resources_index_shows_view_pdf_action_when_pdf_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/handbook.pdf', '%PDF-1.4 sample');

        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Handbook',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => '<p>Handbook body</p>',
            'file_path' => 'resources/handbook.pdf',
            'file_format' => 'PDF',
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'resources'))
            ->assertOk()
            ->assertSee(route('admin.management.resources.view-pdf', $resource->id, absolute: false), false)
            ->assertSee('View PDF', false);
    }

    public function test_admin_can_view_resource_pdf_inline_even_when_unpublished(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/draft-guide.pdf', '%PDF-1.4 sample');

        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Draft Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'file_path' => 'resources/draft-guide.pdf',
            'file_format' => 'PDF',
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.management.resources.view-pdf', $resource->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_resources_edit_page_hides_external_url_for_uploaded_pdf(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Uploaded Only Guide',
            'description' => 'PDF upload only.',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => null,
            'file_path' => 'resources/documents/uploaded-only-guide-1.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.management.edit', ['resources', $resource->id]))
            ->assertOk()
            ->assertDontSee('External URL (optional)', false)
            ->assertSee('Upload PDF', false);
    }

    public function test_admin_resources_index_shows_view_pdf_for_uploaded_pdf(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/documents/uploaded-index-guide-1.pdf', '%PDF-1.4 sample');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        PortalResource::query()->create([
            'title' => 'Uploaded Index Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => null,
            'file_path' => 'resources/documents/uploaded-index-guide-1.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'resources'))
            ->assertOk()
            ->assertSee('aria-label="View PDF"', false)
            ->assertSee('Uploaded Index Guide', false);
    }

    public function test_admin_resources_edit_page_includes_document_editor(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Script Pack',
            'type' => 'document',
            'category' => 'scripts',
            'sort_order' => 1,
            'content' => '<p>Script body</p>',
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.management.edit', ['resources', $resource->id]))
            ->assertOk()
            ->assertSee('Document Content', false)
            ->assertSee('Compose content', false)
            ->assertSee('Upload PDF', false)
            ->assertSee('Generate PDF', false)
            ->assertSee('data-rich-text', false);
    }

    public function test_cfm_can_update_own_document(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $resource = PortalResource::query()->create([
            'created_by' => $cfm->id,
            'title' => 'Mentor Guide',
            'description' => 'Original summary.',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => '<p>Original body</p>',
            'is_published' => true,
            'is_featured' => false,
        ]);

        $this->actingAs($cfm)
            ->patch(route('admin.management.update', ['resources', $resource->id]), [
                'title' => 'Mentor Guide',
                'description' => 'Updated summary.',
                'type' => 'document',
                'category' => 'guides',
                'sort_order' => 1,
                'content' => '<p>Updated body</p>',
                'url' => null,
                'is_published' => 1,
                'is_featured' => 0,
            ])
            ->assertRedirect(route('admin.management.edit', ['resources', $resource->id]));

        $resource->refresh();

        $this->assertSame('Updated summary.', $resource->description);
    }

    public function test_cfm_cannot_update_document_they_do_not_own(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('admin');

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $resource = PortalResource::query()->create([
            'created_by' => $owner->id,
            'title' => 'Admin Guide',
            'description' => 'Original summary.',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => '<p>Original body</p>',
            'is_published' => true,
            'is_featured' => false,
        ]);

        $this->actingAs($cfm)
            ->patch(route('admin.management.update', ['resources', $resource->id]), [
                'title' => 'Admin Guide',
                'description' => 'Blocked update.',
                'type' => 'document',
                'category' => 'guides',
                'sort_order' => 1,
                'content' => '<p>Blocked body</p>',
                'url' => null,
                'is_published' => 1,
                'is_featured' => 0,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $resource->refresh();

        $this->assertSame('Original summary.', $resource->description);
    }

    public function test_cfm_cannot_delete_documents(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $resource = PortalResource::query()->create([
            'created_by' => $cfm->id,
            'title' => 'Owned Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($cfm)
            ->delete(route('admin.management.destroy', ['resources', $resource->id]))
            ->assertForbidden();

        $this->assertNull($resource->fresh()->deleted_at);
    }

    public function test_admin_can_delete_documents(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Delete Me',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.management.destroy', ['resources', $resource->id]))
            ->assertRedirect();

        $this->assertNotNull($resource->fresh()->deleted_at);
    }

    public function test_non_owner_edit_page_shows_read_only_warning(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('admin');

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $resource = PortalResource::query()->create([
            'created_by' => $owner->id,
            'title' => 'Locked Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => '<p>Locked body</p>',
            'is_published' => true,
        ]);

        $this->actingAs($cfm)
            ->get(route('admin.management.edit', ['resources', $resource->id]))
            ->assertOk()
            ->assertSee('Read-only access', false)
            ->assertDontSee('Save Changes', false);
    }

    public function test_admin_can_toggle_resource_document_favorite(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'Favorite Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.management.resources.favorite', $resource->id))
            ->assertRedirect(route('admin.management.resource.index', 'resources'))
            ->assertSessionHas('status', 'favorite-added');

        $this->assertDatabaseHas('resource_favorites', [
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.management.resources.favorite', $resource->id))
            ->assertRedirect(route('admin.management.resource.index', 'resources'))
            ->assertSessionHas('status', 'favorite-removed');

        $this->assertDatabaseMissing('resource_favorites', [
            'user_id' => $admin->id,
            'resource_id' => $resource->id,
        ]);
    }

    public function test_admin_resources_index_shows_my_favorites_table(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $favorited = PortalResource::query()->create([
            'title' => 'Starred Handbook',
            'type' => 'document',
            'category' => 'onboarding',
            'sort_order' => 1,
            'is_published' => true,
            'content' => '<p>Handbook content</p>',
        ]);

        PortalResource::query()->create([
            'title' => 'Other Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $admin->favoritePortalResources()->attach($favorited->id);

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'resources'))
            ->assertOk()
            ->assertSee('My Favorites', false)
            ->assertSee('Starred Handbook', false)
            ->assertSee('aria-label="Remove from My Favorites"', false)
            ->assertSee('aria-label="View record"', false);
    }

    public function test_cannot_favorite_non_document_resource(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resource = PortalResource::query()->create([
            'title' => 'External Link',
            'type' => 'link',
            'category' => 'general',
            'sort_order' => 1,
            'url' => 'https://example.com',
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.management.resources.favorite', $resource->id))
            ->assertNotFound();
    }

    public function test_favorites_are_scoped_per_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $resource = PortalResource::query()->create([
            'title' => 'Shared Doc',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $admin->favoritePortalResources()->attach($resource->id);

        $this->actingAs($cfm)
            ->get(route('admin.management.resource.index', 'resources'))
            ->assertOk()
            ->assertSee('My Favorites', false)
            ->assertSee('No favorites yet', false);

        $this->assertDatabaseMissing('resource_favorites', [
            'user_id' => $cfm->id,
            'resource_id' => $resource->id,
        ]);
    }
}
