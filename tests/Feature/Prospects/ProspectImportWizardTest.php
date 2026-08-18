<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectImportWizard;
use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectImportWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        Storage::fake('local');

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_import_page_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('certified-field-mentor');

        $this->actingAs($user)
            ->get(route('team.prospects.import'))
            ->assertForbidden();
    }

    public function test_import_page_renders_for_authorized_user(): void
    {
        $this->actingAs($this->owner)
            ->get(route('team.prospects.import'))
            ->assertOk()
            ->assertSee('Import Prospects')
            ->assertSee('Upload');
    }

    public function test_wizard_uploads_csv_and_advances_to_mapping_step(): void
    {
        $csv = UploadedFile::fake()->createWithContent(
            'prospects.csv',
            "first_name,last_name,email,phone\nAlex,Import,alex@example.com,5550001111\n"
        );

        Livewire::actingAs($this->owner)
            ->test(ProspectImportWizard::class)
            ->set('csvFile', $csv)
            ->call('uploadCsv')
            ->assertSet('step', 2)
            ->assertSet('totalRows', 1)
            ->assertSet('columnMap.first_name', 'first_name');
    }

    public function test_wizard_detects_duplicates_and_completes_import(): void
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        Prospect::create([
            'owner_id' => $this->owner->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Existing',
            'last_name' => 'Lead',
            'email' => 'dup@example.com',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $csv = UploadedFile::fake()->createWithContent(
            'prospects.csv',
            "first_name,last_name,email\nNew,Lead,new@example.com\nDup,Lead,dup@example.com\n"
        );

        Livewire::actingAs($this->owner)
            ->test(ProspectImportWizard::class)
            ->set('csvFile', $csv)
            ->call('uploadCsv')
            ->call('proceedToDuplicates')
            ->assertSet('step', 3)
            ->assertCount('duplicates', 1)
            ->call('proceedToConfirm')
            ->assertSet('step', 4)
            ->call('confirmImport')
            ->assertRedirect(route('team.prospects'));

        $this->assertDatabaseHas('prospects', [
            'owner_id' => $this->owner->id,
            'email' => 'new@example.com',
        ]);
        $this->assertDatabaseMissing('prospects', [
            'owner_id' => $this->owner->id,
            'email' => 'dup@example.com',
            'first_name' => 'Dup',
        ]);
        $this->assertDatabaseHas('prospect_imports', [
            'user_id' => $this->owner->id,
            'status' => 'completed',
            'imported_rows' => 1,
            'duplicate_rows' => 1,
        ]);
    }

    public function test_import_template_download_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('certified-field-mentor');

        $this->actingAs($user)
            ->get(route('team.prospects.import-template'))
            ->assertForbidden();
    }

    public function test_import_template_download_returns_blank_csv_headers(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('team.prospects.import-template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload('prospect-import-template.csv');

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBFfirst_name,last_name,email,phone,city,source,funnel_type", $content);
    }
}
