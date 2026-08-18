<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FnaIndex extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'created_from')]
    public string $createdFrom = '';

    #[Url(as: 'created_to')]
    public string $createdTo = '';

    #[Url(as: 'dime')]
    public string $dimeCompleted = '';

    #[Url(as: 'gap_min')]
    public string $gapMin = '';

    #[Url(as: 'gap_max')]
    public string $gapMax = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCreatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedCreatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedDimeCompleted(): void
    {
        $this->resetPage();
    }

    public function updatedGapMin(): void
    {
        $this->resetPage();
    }

    public function updatedGapMax(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'search', 'createdFrom', 'createdTo', 'dimeCompleted', 'gapMin', 'gapMax']);
        $this->resetPage();
    }

    public function render(): View
    {
        $statuses = $this->statusFilter !== ''
            ? array_filter(explode(',', $this->statusFilter))
            : [];

        $records = FnaRecord::query()
            ->where('owner_user_id', auth()->id())
            ->with(['prospect:id,first_name,last_name,preferred_name', 'cfm:id,name'])
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('client_name', 'like', $term)
                        ->orWhere('reference_code', 'like', $term);
                });
            })
            ->when($this->createdFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->createdFrom))
            ->when($this->createdTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->createdTo))
            ->when($this->dimeCompleted === '1', fn ($query) => $query->where('dime_completed', true))
            ->when($this->dimeCompleted === '0', fn ($query) => $query->where('dime_completed', false))
            ->when($this->gapMin !== '', fn ($query) => $query->where('protection_gap', '>=', (float) $this->gapMin))
            ->when($this->gapMax !== '', fn ($query) => $query->where('protection_gap', '<=', (float) $this->gapMax))
            ->latest()
            ->paginate(15);

        return view('livewire.fna.fna-index', [
            'records' => $records,
            'statusOptions' => config('fna.statuses', []),
        ]);
    }
}
