<?php

namespace App\Livewire\Dashboard;

use App\Models\Prospect;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardProspectsModal extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public bool $show = false;

    public bool $showQuickAdd = false;

    public string $search = '';

    public ?string $statusMessage = null;

    public int $quickAddKey = 0;

    #[On('open-dashboard-prospects-modal')]
    public function open(): void
    {
        $this->authorize('viewAny', Prospect::class);

        $this->reset(['search', 'statusMessage']);
        $this->showQuickAdd = false;
        $this->resetPage();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->showQuickAdd = false;
    }

    public function toggleQuickAdd(): void
    {
        $this->authorize('create', Prospect::class);

        $this->showQuickAdd = ! $this->showQuickAdd;

        if ($this->showQuickAdd) {
            $this->quickAddKey++;
        }
    }

    #[On('close-dashboard-prospect-quick-add')]
    public function closeQuickAdd(): void
    {
        $this->showQuickAdd = false;
    }

    #[On('dashboard-prospect-created')]
    public function onProspectCreated(?string $prospectName = null): void
    {
        $this->showQuickAdd = false;
        $this->statusMessage = filled($prospectName)
            ? "{$prospectName} was added to your prospects."
            : 'Prospect added successfully.';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = auth()->user();
        $needle = trim($this->search);

        $prospects = Prospect::query()
            ->with(['stage:id,name', 'source:id,name'])
            ->where('owner_id', $user->id)
            ->whereNull('deleted_at')
            ->forSalesPipeline()
            ->when($needle !== '', function (Builder $query) use ($needle): void {
                $like = '%'.$needle.'%';

                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('preferred_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('city', 'like', $like);
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(12);

        return view('livewire.dashboard.dashboard-prospects-modal', [
            'prospects' => $prospects,
            'summaryUrl' => route('team.prospects.screen', 'prospects'),
        ]);
    }
}
