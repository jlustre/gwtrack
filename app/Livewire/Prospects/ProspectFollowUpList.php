<?php

namespace App\Livewire\Prospects;

use App\Models\ProspectFollowUp;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProspectFollowUpList extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'priority')]
    public string $priorityFilter = '';

    #[Url(as: 'due_from')]
    public string $dueFrom = '';

    #[Url(as: 'due_to')]
    public string $dueTo = '';

    public function mount(): void
    {
        $this->authorize('viewAny', \App\Models\Prospect::class);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDueFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDueTo(): void
    {
        $this->resetPage();
    }

    public function markComplete(int $followUpId): void
    {
        $followUp = ProspectFollowUp::query()->with('prospect')->findOrFail($followUpId);
        $this->authorize('update', $followUp);

        $followUp->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function snooze(int $followUpId): void
    {
        $followUp = ProspectFollowUp::query()->with('prospect')->findOrFail($followUpId);
        $this->authorize('update', $followUp);

        $followUp->update([
            'due_at' => $followUp->due_at->copy()->addDay(),
            'status' => 'pending',
        ]);
    }

    public function render(): View
    {
        $followUps = ProspectFollowUp::query()
            ->with(['prospect:id,first_name,last_name,preferred_name,interest_level'])
            ->where('assigned_user_id', auth()->id())
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->statusFilter === '', fn ($query) => $query->whereIn('status', ['pending', 'overdue']))
            ->when($this->priorityFilter !== '', fn ($query) => $query->where('priority', $this->priorityFilter))
            ->when($this->dueFrom !== '', fn ($query) => $query->whereDate('due_at', '>=', $this->dueFrom))
            ->when($this->dueTo !== '', fn ($query) => $query->whereDate('due_at', '<=', $this->dueTo))
            ->orderBy('due_at')
            ->paginate(15);

        return view('livewire.prospects.prospect-follow-up-list', [
            'followUps' => $followUps,
        ]);
    }
}
