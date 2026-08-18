<?php

namespace App\Livewire\Communication;

use App\Services\Communication\LeadershipDeskService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Leadership Desk')]
class LeadershipDesk extends Component
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

    public function render(LeadershipDeskService $desk): View
    {
        $user = auth()->user();

        return view('livewire.communication.leadership-desk', [
            'featured' => $desk->featuredMessage($user)->first(),
            'messages' => $desk->feedFor($user, ['search' => $this->search ?: null]),
            'canCreate' => $user->can('create', \App\Models\MessageCenterAnnouncement::class),
        ])->layout('layouts.app');
    }
}
