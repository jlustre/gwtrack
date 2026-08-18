<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Services\Fna\FnaCompletenessService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CfmFnaReviewQueue extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('review trainee fna records'), 403);

        $user = auth()->user();

        $apprenticeIds = MentorAssignment::query()
            ->where('mentor_id', $user->id)
            ->where('status', 'active')
            ->pluck('apprentice_id');

        $query = FnaRecord::query()
            ->where(function ($q) use ($user, $apprenticeIds): void {
                $q->where('cfm_user_id', $user->id)
                    ->orWhereIn('owner_user_id', $apprenticeIds);
            })
            ->with(['owner:id,name', 'prospect:id,first_name,last_name,preferred_name']);

        $query = match ($this->statusFilter) {
            'approved' => $query->where('status', 'approved_by_cfm'),
            'revision' => $query->where('status', 'revision_requested'),
            'all' => $query->whereIn('status', [
                'submitted_to_cfm', 'under_cfm_review', 'revision_requested', 'approved_by_cfm',
            ]),
            default => $query->whereIn('status', ['submitted_to_cfm', 'under_cfm_review']),
        };

        $records = $query->latest('submitted_at')->paginate(15);

        $completeness = app(FnaCompletenessService::class);

        return view('livewire.fna.cfm-fna-review-queue', [
            'records' => $records,
            'completeness' => $completeness,
        ]);
    }
}
