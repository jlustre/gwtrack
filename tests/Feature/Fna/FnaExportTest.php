<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaExportPreview;
use App\Models\FnaRecord;
use App\Models\User;
use App\Services\Fna\FnaExportService;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FnaExportTest extends TestCase
{
    use RefreshDatabase;

    protected function createOwnedFna(User $owner): FnaRecord
    {
        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Export Client']);

        $fna->update([
            'client_email' => 'client@example.com',
            'client_phone' => '555-0100',
        ]);

        $fna->incomeDetail()->update(['annual_income' => 95000, 'monthly_income' => 7916]);
        $fna->debtDetail()->update(['total_debt' => 12000, 'credit_card_debt' => 12000]);
        $fna->update([
            'dime_completed' => true,
            'protection_gap' => 250000,
            'main_needs_identified' => 'Income protection',
        ]);

        $fna->dimeAnalysis()->update([
            'total_dime_need' => 350000,
            'estimated_protection_gap' => 250000,
        ]);

        return $fna->fresh();
    }

    public function test_export_preview_loads_for_owner(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $fna = $this->createOwnedFna($owner);

        $this->actingAs($owner)
            ->get(route('team.fna.export', $fna))
            ->assertOk()
            ->assertSee('Financial Needs Analysis')
            ->assertSee($fna->reference_code)
            ->assertSee('Download PDF');

        Livewire::actingAs($owner)
            ->test(FnaExportPreview::class, ['fna' => $fna])
            ->assertSet('exportData.reference_code', $fna->reference_code)
            ->assertSee('Export Client');
    }

    public function test_pdf_download_returns_pdf_response(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $fna = $this->createOwnedFna($owner);

        $response = $this->actingAs($owner)
            ->get(route('team.fna.export.download', $fna));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'FNA-'.$fna->reference_code.'.pdf',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_financial_details_masked_when_viewer_lacks_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Role::findByName('associate')->revokePermissionTo('view fna financial details');

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $fna = $this->createOwnedFna($owner);

        $data = app(FnaExportService::class)->buildExportData($fna, $owner);

        $this->assertFalse($data['can_view_financial']);
        $this->assertSame('[Restricted]', $data['client']['email']);
        $this->assertTrue($data['income']['restricted']);

        $html = app(FnaExportService::class)->renderHtml($fna, $owner);

        $this->assertStringContainsString('[Restricted]', $html);
        $this->assertStringNotContainsString('$95,000', $html);

        $this->actingAs($owner)
            ->get(route('team.fna.export', $fna))
            ->assertOk()
            ->assertSee('Financial details masked');
    }
}
