<?php

namespace App\Livewire\Fna;

use App\Models\FnaAttachment;
use App\Models\FnaRecord;
use App\Services\Fna\FnaAttachmentService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class FnaAttachmentsPanel extends Component
{
    use WithFileUploads;

    public FnaRecord $fna;

    public $attachment;

    public ?string $category = null;

    public string $feedbackMessage = '';

    public string $errorMessage = '';

    public function mount(FnaRecord $fna): void
    {
        $this->authorize('view', $fna);
        $this->fna = $fna;
    }

    public function uploadAttachment(FnaAttachmentService $attachments): void
    {
        $this->authorize('update', $this->fna);

        $this->validate([
            'attachment' => 'required|file|max:10240',
            'category' => 'nullable|string|max:60',
        ]);

        try {
            $attachments->upload($this->fna, auth()->user(), $this->attachment, $this->category);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?? 'Upload failed.';
            $this->feedbackMessage = '';

            return;
        }

        $this->reset('attachment', 'category');
        $this->errorMessage = '';
        $this->feedbackMessage = 'Attachment uploaded.';
        $this->fna = $this->fna->fresh();
        $this->dispatch('fna-review-updated');
    }

    public function deleteAttachment(int $attachmentId, FnaAttachmentService $attachments): void
    {
        $this->authorize('update', $this->fna);

        $attachment = FnaAttachment::query()
            ->where('fna_record_id', $this->fna->id)
            ->findOrFail($attachmentId);

        $attachments->delete($attachment, auth()->user());

        $this->feedbackMessage = 'Attachment removed.';
        $this->errorMessage = '';
        $this->fna = $this->fna->fresh();
        $this->dispatch('fna-review-updated');
    }

    public function render(): View
    {
        $this->fna->loadMissing(['attachments.uploadedBy:id,name']);

        return view('livewire.fna.fna-attachments-panel');
    }
}
