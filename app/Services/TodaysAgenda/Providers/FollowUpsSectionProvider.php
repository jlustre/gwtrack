<?php

namespace App\Services\TodaysAgenda\Providers;

use App\Contracts\TodaysAgendaSectionProvider;
use App\Models\User;
use App\Services\TodaysAgenda\Support\AgendaSectionBuilder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class FollowUpsSectionProvider implements TodaysAgendaSectionProvider
{
    use AgendaSectionBuilder;

    private const ITEM_LIMIT = 50;

    public function key(): string
    {
        return 'follow_ups';
    }

    public function label(): string
    {
        return 'Follow-Ups';
    }

    public function icon(): string
    {
        return 'message-circle';
    }

    public function sort(): int
    {
        return 60;
    }

    public function isEnabledFor(User $user): bool
    {
        return $user->can('manage prospects') && Route::has('team.prospects.follow-ups');
    }

    public function sectionFor(User $user, CarbonInterface $dayStart, CarbonInterface $dayEnd): array
    {
        $rows = DB::table('prospect_followups')
            ->join('prospects', 'prospects.id', '=', 'prospect_followups.prospect_id')
            ->where('prospect_followups.assigned_user_id', $user->id)
            ->whereIn('prospect_followups.status', ['pending', 'overdue'])
            ->whereNull('prospect_followups.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->orderBy('prospect_followups.due_at')
            ->limit(self::ITEM_LIMIT)
            ->get([
                'prospects.id as prospect_id',
                'prospects.first_name',
                'prospects.last_name',
                'prospects.preferred_name',
                'prospect_followups.id as followup_id',
                'prospect_followups.followup_type',
                'prospect_followups.due_at',
                'prospect_followups.status',
            ]);

        $items = $rows
            ->map(function (object $row): array {
                $name = filled($row->preferred_name)
                    ? $row->preferred_name
                    : trim($row->first_name.' '.$row->last_name);

                return [
                    'id' => 'follow-up-'.$row->followup_id,
                    'title' => filled($name) ? $name : 'Unnamed prospect',
                    'subtitle' => filled($row->followup_type) ? str($row->followup_type)->headline()->toString() : 'Follow-up',
                    'meta' => ucfirst((string) $row->status).' · due '.\Carbon\Carbon::parse($row->due_at)->format('M j, Y'),
                    'url' => route('team.prospects.records.show', $row->prospect_id),
                    'badge' => $row->status === 'overdue' ? 'Overdue' : 'Open',
                    'highlight' => $row->status === 'overdue',
                    'sort_at' => \Carbon\Carbon::parse($row->due_at)->toIso8601String(),
                ];
            })
            ->all();

        return $this->buildSection(
            $this->key(),
            $this->label(),
            $this->icon(),
            $items,
            'No open prospect follow-ups.',
            ['label' => 'View follow-ups', 'url' => route('team.prospects.follow-ups')],
        );
    }
}
