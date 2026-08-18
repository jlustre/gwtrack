<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaAttachmentsPanel;
use App\Models\FnaAttachment;
use App\Models\User;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FnaAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_and_list_attachments(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Attachment Client']);

        Livewire::actingAs($owner)
            ->test(FnaAttachmentsPanel::class, ['fna' => $fna])
            ->set('attachment', UploadedFile::fake()->create('statement.pdf', 120, 'application/pdf'))
            ->set('category', 'Financial Statement')
            ->call('uploadAttachment')
            ->assertSet('feedbackMessage', 'Attachment uploaded.')
            ->assertSee('statement.pdf')
            ->assertSee('Financial Statement');

        $this->assertDatabaseHas('fna_attachments', [
            'fna_record_id' => $fna->id,
            'uploaded_by_user_id' => $owner->id,
            'original_name' => 'statement.pdf',
            'category' => 'Financial Statement',
        ]);

        $attachment = FnaAttachment::query()->where('fna_record_id', $fna->id)->first();
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_owner_can_delete_attachment(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($owner, ['client_name' => 'Delete Attachment Client']);

        $path = UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf')->store('fna-attachments/'.$fna->id, 'local');

        $attachment = $fna->attachments()->create([
            'uploaded_by_user_id' => $owner->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 51200,
        ]);

        Livewire::actingAs($owner)
            ->test(FnaAttachmentsPanel::class, ['fna' => $fna])
            ->call('deleteAttachment', $attachment->id)
            ->assertSet('feedbackMessage', 'Attachment removed.');

        $this->assertSoftDeleted('fna_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($path);
    }
}
