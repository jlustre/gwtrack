<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaNotesTimeline;
use App\Models\FnaReviewComment;
use App\Models\User;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FnaNotesTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_shows_activity_and_review_comments(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $cfm = User::factory()->create(['name' => 'Timeline CFM']);
        $cfm->assignRole('certified-field-mentor');

        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Timeline Client']);

        app(FnaRecordService::class)->logActivity($fna, $owner, 'submitted', 'FNA submitted to CFM for review.');

        FnaReviewComment::create([
            'fna_record_id' => $fna->id,
            'user_id' => $cfm->id,
            'comment_type' => 'coaching',
            'body' => 'Please expand the income section.',
            'is_internal' => false,
        ]);

        Livewire::actingAs($owner)
            ->test(FnaNotesTimeline::class, ['fna' => $fna])
            ->assertSee('FNA submitted to CFM for review.')
            ->assertSee('Please expand the income section.')
            ->assertSee('Timeline CFM');
    }
}
