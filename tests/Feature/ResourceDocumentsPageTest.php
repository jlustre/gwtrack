<?php

namespace Tests\Feature;

use App\Models\PortalResource;
use App\Models\User;
use Database\Seeders\RankSeeder;
use Database\Seeders\ResourceDocumentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceDocumentsPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedDocuments(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            ResourceDocumentSeeder::class,
        ]);
    }

    public function test_member_can_view_documents_library(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertSee('Documents', false)
            ->assertSee('GWTracks Welcome Orientation', false)
            ->assertSee('Associate Welcome Packet', false)
            ->assertSee('Featured documents', false)
            ->assertSee('Compliance', false);
    }

    public function test_welcome_orientation_guide_is_available_from_documents(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->assertDatabaseHas('resources', [
            'title' => 'GWTracks Welcome Orientation',
            'type' => 'document',
            'category' => 'guides',
            'url' => 'resources/welcome-orientation.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $orientation = PortalResource::query()
            ->where('title', 'GWTracks Welcome Orientation')
            ->firstOrFail();

        $this->assertNotNull($orientation->file_path);
        Storage::disk('public')->assertExists($orientation->file_path);
        $this->assertSame(url('/resources/welcome-orientation.pdf'), $orientation->inlinePreviewUrl());

        $this->actingAs($user)
            ->get(route('support.documentation', 'welcome-orientation'))
            ->assertOk()
            ->assertSee('GWTracks Welcome Orientation', false)
            ->assertSee('Portal purpose', false)
            ->assertSee('Download PDF', false)
            ->assertSee(route('support.documentation.pdf', 'welcome-orientation'), false);

        $this->actingAs($user)
            ->get(route('support.documentation.pdf.view', 'welcome-orientation'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('resources.welcome-orientation'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_application_user_guide_pdf_is_available(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $guide = PortalResource::query()
            ->where('title', 'GWTracks Application User Guide')
            ->firstOrFail();

        $this->assertSame('resources/application.pdf', $guide->url);
        $this->assertSame('PDF', $guide->file_format);
        $this->assertNotNull($guide->file_path);
        Storage::disk('public')->assertExists($guide->file_path);
        $this->assertSame(url('/resources/application.pdf'), $guide->inlinePreviewUrl());

        $this->actingAs($user)
            ->get(route('support.documentation', 'application'))
            ->assertOk()
            ->assertSee('GWTracks Application User Guide', false)
            ->assertSee('Download PDF', false)
            ->assertSee(route('support.documentation.pdf', 'application'), false);

        $this->actingAs($user)
            ->get(route('support.documentation.pdf.view', 'application'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('resources.application-guide'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_goals_and_performance_guide_pdf_is_available(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $guide = PortalResource::query()
            ->where('title', 'Goals & Performance User Guide')
            ->firstOrFail();

        $this->assertSame('resources/goals-and-performance.pdf', $guide->url);
        $this->assertSame('PDF', $guide->file_format);
        $this->assertNotNull($guide->file_path);
        Storage::disk('public')->assertExists($guide->file_path);
        $this->assertSame(url('/resources/goals-and-performance.pdf'), $guide->inlinePreviewUrl());

        $this->actingAs($user)
            ->get(route('support.documentation', 'goals-and-performance'))
            ->assertOk()
            ->assertSee('Goals & Performance User Guide')
            ->assertSee('Download PDF', false)
            ->assertSee(route('support.documentation.pdf', 'goals-and-performance'), false);

        $this->actingAs($user)
            ->get(route('support.documentation.pdf.view', 'goals-and-performance'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('resources.goals-guide'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_prospect_sales_funnel_guide_pdf_is_available(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $guide = PortalResource::query()
            ->where('title', 'Prospects & Sales Funnel User Guide')
            ->firstOrFail();

        $this->assertSame('resources/prospect-sales-funnel.pdf', $guide->url);
        $this->assertSame('PDF', $guide->file_format);
        $this->assertNotNull($guide->file_path);
        Storage::disk('public')->assertExists($guide->file_path);
        $this->assertSame(url('/resources/prospect-sales-funnel.pdf'), $guide->inlinePreviewUrl());

        $this->actingAs($user)
            ->get(route('support.documentation', 'prospect-sales-funnel'))
            ->assertOk()
            ->assertSee('Prospects & Sales Funnel User Guide')
            ->assertSee('Download PDF', false)
            ->assertSee(route('support.documentation.pdf', 'prospect-sales-funnel'), false);

        $this->actingAs($user)
            ->get(route('support.documentation.pdf.view', 'prospect-sales-funnel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('resources.prospect-sales-funnel-guide'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_fna_management_guide_pdf_is_available(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $guide = PortalResource::query()
            ->where('title', 'FNA Management User Guide')
            ->firstOrFail();

        $this->assertSame('resources/fna-management.pdf', $guide->url);
        $this->assertSame('PDF', $guide->file_format);
        $this->assertNotNull($guide->file_path);
        Storage::disk('public')->assertExists($guide->file_path);
        $this->assertSame(url('/resources/fna-management.pdf'), $guide->inlinePreviewUrl());

        $this->actingAs($user)
            ->get(route('support.documentation', 'fna-management'))
            ->assertOk()
            ->assertSee('FNA Management User Guide', false)
            ->assertSee('Download PDF', false)
            ->assertSee(route('support.documentation.pdf', 'fna-management'), false);

        $this->actingAs($user)
            ->get(route('support.documentation.pdf.view', 'fna-management'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('resources.fna-management-guide'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_training_academy_guide_pdf_is_available(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $guide = PortalResource::query()
            ->where('title', 'Training Academy User Guide')
            ->firstOrFail();

        $this->assertSame('resources/training-academy.pdf', $guide->url);
        $this->assertSame('PDF', $guide->file_format);
        $this->assertNotNull($guide->file_path);
        Storage::disk('public')->assertExists($guide->file_path);
        $this->assertSame(url('/resources/training-academy.pdf'), $guide->inlinePreviewUrl());

        $this->actingAs($user)
            ->get(route('support.documentation', 'training-academy'))
            ->assertOk()
            ->assertSee('Training Academy User Guide', false)
            ->assertSee('Download PDF', false)
            ->assertSee(route('support.documentation.pdf', 'training-academy'), false);

        $this->actingAs($user)
            ->get(route('support.documentation.pdf.view', 'training-academy'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('resources.training-academy-guide'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_code_of_conduct_pdf_is_available_at_stable_url(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()
            ->where('title', 'Code of Conduct Summary')
            ->firstOrFail();

        $this->assertSame('resources/code-of-conduct.pdf', $document->url);
        $this->assertSame('PDF', $document->file_format);
        $this->assertNotNull($document->file_path);
        Storage::disk('public')->assertExists($document->file_path);
        $this->assertSame(url('/resources/code-of-conduct.pdf'), $document->inlinePreviewUrl());
        $this->assertStringContainsString('Professional conduct', $document->content);

        $this->actingAs($user)
            ->get(route('resources.documents', [
                'category' => 'compliance',
                'search' => 'Code of Conduct',
            ]))
            ->assertOk()
            ->assertSee('href="'.url('/resources/code-of-conduct.pdf').'"', false)
            ->assertDontSee('example.com/resources/code-of-conduct.pdf', false);

        $this->actingAs($user)
            ->get(route('resources.code-of-conduct'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_fap_overview_pdf_is_available_at_stable_url(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()
            ->where('title', 'Field Apprenticeship Program Overview')
            ->firstOrFail();

        $this->assertSame('resources/fap-overview.pdf', $document->url);
        $this->assertSame('PDF', $document->file_format);
        $this->assertNotNull($document->file_path);
        Storage::disk('public')->assertExists($document->file_path);
        $this->assertSame(url('/resources/fap-overview.pdf'), $document->inlinePreviewUrl());
        $this->assertStringContainsString('Program phases', $document->content);
        $this->assertStringNotContainsString('example.com/resources/fap-overview.pdf', (string) $document->url);

        $this->actingAs($user)
            ->get(route('resources.documents', [
                'category' => 'onboarding',
                'search' => 'Field Apprenticeship',
            ]))
            ->assertOk()
            ->assertSee('href="'.url('/resources/fap-overview.pdf').'"', false)
            ->assertDontSee('example.com/resources/fap-overview.pdf', false);

        $this->actingAs($user)
            ->get(route('resources.fap-overview'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_associate_welcome_packet_pdf_is_available_at_stable_url(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $packet = PortalResource::query()
            ->where('title', 'Associate Welcome Packet')
            ->firstOrFail();

        $this->assertNotNull($packet->file_path);
        Storage::disk('public')->assertExists($packet->file_path);
        $this->assertSame('resources/welcome-packet.pdf', $packet->url);
        $this->assertSame(url('/resources/welcome-packet.pdf'), $packet->inlinePreviewUrl());
        $this->assertSame(url('/resources/welcome-packet.pdf'), $packet->resolvedAccessUrl());

        $this->actingAs($user)
            ->get(route('resources.documents', [
                'category' => 'onboarding',
                'search' => 'welcome',
            ]))
            ->assertOk()
            ->assertSee('href="'.url('/resources/welcome-packet.pdf').'"', false)
            ->assertDontSee('/storage/resources/documents/associate-welcome-packet-', false);

        $this->actingAs($user)
            ->get(route('resources.welcome-packet'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get('/resources/welcome-packet.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_documents_can_be_filtered_by_category(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.documents', ['category' => 'scripts']))
            ->assertOk()
            ->assertSee('Warm Market Introduction Script', false)
            ->assertDontSee('Associate Welcome Packet', false);
    }

    public function test_unpublished_documents_are_hidden(): void
    {
        $this->seedDocuments();

        PortalResource::query()->create([
            'title' => 'Hidden Internal Draft',
            'type' => 'document',
            'category' => 'general',
            'sort_order' => 99,
            'is_published' => false,
            'url' => 'https://example.com/hidden.pdf',
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertDontSee('Hidden Internal Draft', false);
    }

    public function test_authorized_roles_see_manage_documents_link(): void
    {
        $this->seedDocuments();

        foreach (['admin', 'agency-owner', 'certified-field-mentor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get(route('resources.documents'))
                ->assertOk()
                ->assertSee('New document', false)
                ->assertSee('Manage documents', false);
        }
    }

    public function test_member_and_trainer_do_not_see_manage_documents_link(): void
    {
        $this->seedDocuments();

        foreach (['member', 'trainer'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get(route('resources.documents'))
                ->assertOk()
                ->assertDontSee('New document', false)
                ->assertDontSee('Manage documents', false);
        }
    }

    public function test_admin_can_export_documents_to_seeder_from_documents_page(): void
    {
        $this->seedDocuments();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        PortalResource::query()->create([
            'title' => 'Seeder Export Test',
            'description' => 'Exported from feature test.',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 99,
            'content' => '<p>Export me</p>',
            'url' => null,
            'file_format' => 'PDF',
            'is_published' => true,
            'is_featured' => false,
        ]);

        $seederPath = database_path('seeders/ResourceDocumentSeeder.php');
        $originalSeeder = file_get_contents($seederPath);

        try {
            $this->actingAs($admin)
                ->post(route('resources.documents.update-seeder'))
                ->assertRedirect(route('resources.documents'))
                ->assertSessionHas('status', 'document-seeder-updated');

            $seeder = file_get_contents($seederPath);

            $this->assertStringContainsString('Seeder Export Test', $seeder);
            $this->assertStringContainsString('Export me', $seeder);
            $this->assertStringContainsString('PortalResource::query()->updateOrCreate', $seeder);
        } finally {
            file_put_contents($seederPath, $originalSeeder);
        }
    }

    public function test_cfm_cannot_update_document_seeder(): void
    {
        $this->seedDocuments();

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $this->actingAs($cfm)
            ->post(route('resources.documents.update-seeder'))
            ->assertForbidden();
    }

    public function test_super_admin_can_update_document_seeder(): void
    {
        $this->seedDocuments();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $seederPath = database_path('seeders/ResourceDocumentSeeder.php');
        $originalSeeder = file_get_contents($seederPath);

        try {
            $this->actingAs($superAdmin)
                ->post(route('resources.documents.update-seeder'))
                ->assertRedirect(route('resources.documents'))
                ->assertSessionHas('status', 'document-seeder-updated');
        } finally {
            file_put_contents($seederPath, $originalSeeder);
        }
    }

    public function test_admin_sees_update_document_seeder_button(): void
    {
        $this->seedDocuments();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertSee('Update document seeder', false);
    }

    public function test_member_does_not_see_update_document_seeder_button(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertDontSee('Update document seeder', false);
    }

    public function test_super_admin_sees_update_document_seeder_button(): void
    {
        $this->seedDocuments();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertSee('Update document seeder', false);
    }

    public function test_documents_page_defaults_to_table_view(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get(route('resources.documents'));

        $response->assertOk();
        $this->assertStringContainsString("viewMode: 'table'", $response->getContent());
        $this->assertStringContainsString('x-show="viewMode === \'table\'"', $response->getContent());
        $response->assertSee('Format', false);
        $response->assertSee('toggleCategorySidebar', false);
        $response->assertSee('Hide categories', false);
        $response->assertSee('Browse by category', false);
    }

    public function test_member_can_preview_html_document_content(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()->create([
            'title' => 'Preview Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => '<p>Welcome to <strong>GWTracks</strong>.</p>',
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('resources.documents.preview', $document))
            ->assertOk()
            ->assertJsonPath('has_rtf', true)
            ->assertJsonPath('has_pdf', false)
            ->assertJsonPath('default_view', 'rtf')
            ->assertJsonPath('title', 'Preview Guide');

        $this->actingAs($user)
            ->get(route('resources.documents', ['document' => $document->id]))
            ->assertOk()
            ->assertSee('View', false)
            ->assertSee('Close preview', false)
            ->assertSee('Rich Text', false)
            ->assertSee('PDF', false)
            ->assertDontSee('Document Preview', false);
    }

    public function test_preview_includes_both_rtf_and_pdf_when_available(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/combo-guide.pdf', '%PDF-1.4 sample');

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()->create([
            'title' => 'Combo Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'content' => '<p>Rich text body</p>',
            'file_path' => 'resources/combo-guide.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('resources.documents.preview', $document))
            ->assertOk()
            ->assertJsonPath('has_rtf', true)
            ->assertJsonPath('has_pdf', true)
            ->assertJsonPath('default_view', 'rtf')
            ->assertJsonPath('pdf_url', Storage::disk('public')->url('resources/combo-guide.pdf'));
    }

    public function test_member_can_view_pdf_inline_without_download(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/sample-guide.pdf', '%PDF-1.4 sample');

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()->create([
            'title' => 'Inline PDF Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'file_path' => 'resources/sample-guide.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('resources.documents.view', $document));

        $response->assertOk();
        $this->assertStringContainsString(
            'inline',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_member_can_download_published_file_document(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
        Storage::disk('public')->put('resources/sample-guide.pdf', 'sample contents');

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()->create([
            'title' => 'Downloadable Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'file_path' => 'resources/sample-guide.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->get(route('resources.documents.download', $document))
            ->assertOk()
            ->assertDownload('sample-guide.pdf');
    }

    public function test_pdf_only_document_list_shows_view_and_hides_download(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/uploaded-member-guide.pdf', '%PDF-1.4 sample');
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()->create([
            'title' => 'Uploaded Member Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'file_path' => 'resources/uploaded-member-guide.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertSee('aria-label="View PDF for Uploaded Member Guide"', false)
            ->assertDontSee('aria-label="Download Uploaded Member Guide"', false);
    }

    public function test_pdf_only_document_preview_defaults_to_pdf_with_download_option(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/uploaded-member-guide.pdf', '%PDF-1.4 sample');
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()->create([
            'title' => 'Uploaded Member Guide',
            'type' => 'document',
            'category' => 'guides',
            'sort_order' => 1,
            'file_path' => 'resources/uploaded-member-guide.pdf',
            'file_format' => 'PDF',
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('resources.documents.preview', $document))
            ->assertOk()
            ->assertJsonPath('default_view', 'pdf')
            ->assertJsonPath('has_pdf', true)
            ->assertJsonPath('has_rtf', false)
            ->assertJsonPath('pdf_url', Storage::disk('public')->url('resources/uploaded-member-guide.pdf'))
            ->assertJsonPath('download_url', route('resources.documents.download', $document));
    }

    public function test_super_admin_sees_edit_and_pdf_icons_in_document_tables(): void
    {
        $this->seedDocuments();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertSee('aria-label="View PDF for Associate Welcome Packet"', false)
            ->assertSee('aria-label="Edit Associate Welcome Packet"', false)
            ->assertSee('href="'.url('/resources/welcome-packet.pdf').'"', false);
    }

    public function test_member_can_toggle_document_favorite_from_library(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()
            ->where('title', 'Associate Welcome Packet')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('resources.documents.favorite', $document))
            ->assertRedirect(route('resources.documents'))
            ->assertSessionHas('status', 'favorite-added');

        $this->assertDatabaseHas('resource_favorites', [
            'user_id' => $user->id,
            'resource_id' => $document->id,
        ]);

        $this->actingAs($user)
            ->get(route('resources.documents'))
            ->assertOk()
            ->assertSee('My Favorites', false)
            ->assertSee('Associate Welcome Packet', false)
            ->assertSee('Add to My Favorites', false)
            ->assertSee('Remove from My Favorites', false);

        $this->actingAs($user)
            ->post(route('resources.documents.favorite', $document))
            ->assertRedirect(route('resources.documents'))
            ->assertSessionHas('status', 'favorite-removed');

        $this->assertDatabaseMissing('resource_favorites', [
            'user_id' => $user->id,
            'resource_id' => $document->id,
        ]);
    }

    public function test_member_cannot_favorite_unpublished_document(): void
    {
        $this->seedDocuments();

        $user = User::factory()->create();
        $user->assignRole('member');

        $document = PortalResource::query()->create([
            'title' => 'Hidden Draft',
            'type' => 'document',
            'category' => 'general',
            'sort_order' => 99,
            'is_published' => false,
        ]);

        $this->actingAs($user)
            ->post(route('resources.documents.favorite', $document))
            ->assertNotFound();
    }
}
