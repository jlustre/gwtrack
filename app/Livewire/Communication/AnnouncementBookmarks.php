<?php

namespace App\Livewire\Communication;

use App\Services\Communication\AnnouncementEngagementService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Saved Announcements')]
class AnnouncementBookmarks extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canViewAnnouncements(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function removeBookmark(int $announcementId, AnnouncementEngagementService $engagement): void
    {
        $announcement = \App\Models\MessageCenterAnnouncement::query()->findOrFail($announcementId);
        $this->authorize('view', $announcement);

        if ($engagement->isBookmarked(auth()->user(), $announcement)) {
            $engagement->toggleBookmark(auth()->user(), $announcement);
        }
    }

    public function render(AnnouncementEngagementService $engagement): View
    {
        $user = auth()->user();
        $readIds = $engagement->readIdsFor($user);

        return view('livewire.communication.announcement-bookmarks', [
            'announcements' => $engagement->bookmarksFor($user, [
                'search' => $this->search ?: null,
            ]),
            'readIds' => $readIds,
            'priorities' => config('communication-hub.priorities', []),
        ])->layout('layouts.app');
    }
}
