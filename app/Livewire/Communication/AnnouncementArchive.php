<?php

namespace App\Livewire\Communication;

use App\Models\AnnouncementCampaign;
use App\Models\AnnouncementCategory;
use App\Models\User;
use App\Services\Communication\AnnouncementAnalyticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Announcement Archive')]
class AnnouncementArchive extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $category_id = null;

    public ?string $priority = null;

    public ?int $author_id = null;

    public ?int $campaign_id = null;

    public ?int $year = null;

    public ?int $month = null;

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

    public function updatedAuthorId(): void
    {
        $this->resetPage();
    }

    public function updatedCampaignId(): void
    {
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category_id', 'priority', 'author_id', 'campaign_id', 'year', 'month']);
        $this->resetPage();
    }

    public function render(AnnouncementAnalyticsService $analytics): View
    {
        $filters = array_filter([
            'search' => $this->search,
            'category_id' => $this->category_id,
            'priority' => $this->priority,
            'author_id' => $this->author_id,
            'campaign_id' => $this->campaign_id,
            'year' => $this->year,
            'month' => $this->month,
        ], fn ($value) => filled($value));

        return view('livewire.communication.announcement-archive', [
            'announcements' => $analytics->archiveFor(auth()->user(), $filters),
            'categories' => AnnouncementCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'campaigns' => AnnouncementCampaign::query()->orderByDesc('starts_at')->limit(50)->get(),
            'authors' => User::query()
                ->whereIn('id', \App\Models\MessageCenterAnnouncement::query()->distinct()->pluck('created_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'priorities' => config('communication-hub.priorities', []),
            'years' => range((int) now()->year, (int) now()->subYears(5)->year),
        ])->layout('layouts.app');
    }
}
