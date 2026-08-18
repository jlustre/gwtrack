<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class FnaNotesTimeline extends Component
{
    public FnaRecord $fna;

    public function mount(FnaRecord $fna): void
    {
        $this->authorize('view', $fna);
        $this->fna = $fna;
    }

    #[On('fna-review-updated')]
    public function refreshTimeline(): void
    {
        $this->fna = $this->fna->fresh();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timelineEntries(): Collection
    {
        $this->fna->loadMissing([
            'activityLogs.user:id,name',
            'reviewComments.user:id,name',
            'statusHistories.changedBy:id,name',
        ]);

        $entries = collect();

        foreach ($this->fna->activityLogs as $log) {
            $entries->push([
                'type' => 'activity',
                'at' => $log->created_at,
                'title' => str($log->action)->headline()->toString(),
                'body' => $log->description,
                'actor' => $log->user?->name ?? 'System',
            ]);
        }

        foreach ($this->fna->reviewComments as $comment) {
            $entries->push([
                'type' => 'review',
                'at' => $comment->created_at,
                'title' => str($comment->comment_type)->headline()->toString(),
                'body' => $comment->body,
                'actor' => $comment->user?->name ?? 'Unknown',
            ]);
        }

        foreach ($this->fna->statusHistories as $history) {
            $from = $history->from_status
                ? (config('fna.statuses')[$history->from_status] ?? $history->from_status)
                : 'New';
            $to = config('fna.statuses')[$history->to_status] ?? $history->to_status;

            $entries->push([
                'type' => 'status',
                'at' => $history->created_at,
                'title' => 'Status changed',
                'body' => "{$from} → {$to}",
                'actor' => $history->changedBy?->name ?? 'System',
            ]);
        }

        return $entries
            ->filter(fn (array $entry) => $entry['at'] !== null)
            ->sortByDesc('at')
            ->values();
    }

    public function render(): View
    {
        return view('livewire.fna.fna-notes-timeline', [
            'entries' => $this->timelineEntries(),
        ]);
    }
}
