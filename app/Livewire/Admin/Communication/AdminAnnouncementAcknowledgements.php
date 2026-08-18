<?php

namespace App\Livewire\Admin\Communication;

use App\Models\MessageCenterAnnouncement;
use App\Services\Communication\AnnouncementAcknowledgementService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminAnnouncementAcknowledgements extends Component
{
    public ?int $selectedAnnouncementId = null;

    /** @var array{audience_total: int, acknowledged_count: int, pending_count: int, pending_users: list<array{id: int, name: string}>}|null */
    public ?array $detail = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view communication analytics'), 403);
    }

    public function showDetail(int $announcementId, AnnouncementAcknowledgementService $acknowledgements): void
    {
        $announcement = MessageCenterAnnouncement::query()->findOrFail($announcementId);
        $this->selectedAnnouncementId = $announcement->id;
        $this->detail = $acknowledgements->acknowledgementDetail($announcement);
    }

    public function closeDetail(): void
    {
        $this->selectedAnnouncementId = null;
        $this->detail = null;
    }

    public function render(AnnouncementAcknowledgementService $acknowledgements): View
    {
        return view('livewire.admin.communication.admin-announcement-acknowledgements', [
            'report' => $acknowledgements->acknowledgementReport(),
        ]);
    }
}
