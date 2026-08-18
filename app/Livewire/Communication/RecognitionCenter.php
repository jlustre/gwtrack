<?php

namespace App\Livewire\Communication;

use App\Models\User;
use App\Services\Communication\AnnouncementEngagementService;
use App\Services\Communication\RecognitionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Recognition Center')]
class RecognitionCenter extends Component
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

    public function render(
        RecognitionService $recognition,
        AnnouncementEngagementService $engagement,
    ): View {
        $user = auth()->user();
        $posts = $recognition->wallFor($user, ['search' => $this->search ?: null]);
        $summaries = $engagement->engagementSummariesFor($posts->getCollection()->pluck('id')->all());

        $postsWithContext = $posts->getCollection()->map(function ($announcement) use ($recognition, $summaries) {
            return [
                'announcement' => $announcement,
                'context' => $recognition->recognitionContext($announcement),
                'engagement' => $summaries[$announcement->id] ?? ['reactions' => 0, 'comments' => 0],
            ];
        });

        return view('livewire.communication.recognition-center', [
            'posts' => $posts,
            'postsWithContext' => $postsWithContext,
            'recentAwards' => $recognition->recentAwards(8),
            'canCreate' => $user->can('manage recognition posts') || $user->can('manage recognition'),
        ])->layout('layouts.app');
    }
}
