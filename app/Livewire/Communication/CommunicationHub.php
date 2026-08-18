<?php

namespace App\Livewire\Communication;

use App\Services\Communication\AnnouncementEngagementService;
use App\Services\Communication\CommunicationHubService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Communication Hub')]
class CommunicationHub extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $priority = '';

    public bool $unreadOnly = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => ''],
        'priority' => ['except' => ''],
        'unreadOnly' => ['except' => false],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canViewAnnouncements(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedPriority(): void
    {
        $this->resetPage();
    }

    public function updatedUnreadOnly(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryId = '';
        $this->priority = '';
        $this->unreadOnly = false;
        $this->resetPage();
    }

    public function render(
        CommunicationHubService $hub,
        AnnouncementEngagementService $engagement,
    ): View {
        $user = auth()->user();
        $readIds = $engagement->readIdsFor($user);
        $bookmarkIds = $engagement->bookmarkIdsFor($user);

        $filters = [
            'category_id' => $this->categoryId ?: null,
            'priority' => $this->priority ?: null,
            'unread_only' => $this->unreadOnly,
            'search' => $this->search ?: null,
        ];

        $showHero = ! filled($this->search) && ! filled($this->categoryId) && ! filled($this->priority) && ! $this->unreadOnly;

        $announcements = $hub->feedFor($user, $filters);
        $engagementSummaries = $engagement->engagementSummariesFor(
            $announcements->getCollection()->pluck('id')->all(),
        );

        return view('livewire.communication.communication-hub', [
            'announcements' => $announcements,
            'engagementSummaries' => $engagementSummaries,
            'featured' => $showHero ? $engagement->featuredFor($user) : collect(),
            'pinned' => $showHero ? $engagement->pinnedFor($user) : collect(),
            'readIds' => $readIds,
            'bookmarkIds' => $bookmarkIds,
            'unreadCount' => $engagement->unreadCountFor($user),
            'categories' => \App\Models\AnnouncementCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'priorities' => config('communication-hub.priorities', []),
            'canCreate' => $user->can('create', \App\Models\MessageCenterAnnouncement::class),
            'hasActiveFilters' => filled($this->search) || filled($this->categoryId) || filled($this->priority) || $this->unreadOnly,
        ])->layout('layouts.app');
    }
}
