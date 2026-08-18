<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Events\Support\SupportTicketCreated;
use App\Events\Support\SupportTicketStatusChanged;
use App\Models\SupportTicket;
use App\Models\SupportTicketAssignment;
use App\Models\SupportTicketComment;
use App\Models\SupportTicketInternalNote;
use App\Models\SupportTicketStatus;
use App\Models\SupportTicketStatusHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportTicketService
{
    public function __construct(
        private readonly SupportTicketPriorityService $priority,
        private readonly SupportSlaService $sla,
    ) {}

    public function generateTicketNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "GW-{$year}-";

        $latest = SupportTicket::query()
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function defaultStatus(): SupportTicketStatus
    {
        return SupportTicketStatus::query()
            ->where('is_system_default', true)
            ->orderBy('sort_order')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTicket(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data): SupportTicket {
            $status = $this->defaultStatus();
            $priorityScore = $this->priority->calculateScore(
                (string) $data['urgency'],
                (string) $data['impact'],
                (string) ($data['frequency'] ?? 'unknown'),
            );

            $ticket = SupportTicket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'user_id' => $user->id,
                'type' => $data['type'],
                'module' => $data['module'],
                'category' => $data['category'],
                'user_intent_action' => $data['user_intent_action'] ?? null,
                'user_reported_outcome' => $data['user_reported_outcome'] ?? null,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'urgency' => $data['urgency'],
                'impact' => $data['impact'],
                'frequency' => $data['frequency'] ?? 'unknown',
                'device' => $data['device'] ?? 'unknown',
                'browser' => $data['browser'] ?? 'unknown',
                'related_url' => $data['related_url'] ?? null,
                'status_id' => $status->id,
                'priority_score' => $priorityScore,
                'sla_status' => 'on_track',
            ]);

            SupportTicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status_id' => null,
                'new_status_id' => $status->id,
                'changed_by' => $user->id,
            ]);

            $this->sla->checkTicket($ticket);

            event(new SupportTicketCreated($ticket->fresh(['user', 'status'])));

            return $ticket;
        });
    }

    public function addComment(SupportTicket $ticket, User $user, string $body, bool $isAgent = false): SupportTicketComment
    {
        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => trim($body),
        ]);

        if ($isAgent) {
            event(new \App\Events\Support\SupportTicketAgentReplied($ticket->fresh(['user']), $comment, $user));

            if ($ticket->status?->slug !== 'awaiting_user') {
                $awaiting = SupportTicketStatus::query()->where('slug', 'awaiting_user')->first();

                if ($awaiting) {
                    $this->updateStatus($ticket, $awaiting, $user);
                }
            }
        }

        return $comment;
    }

    public function addInternalNote(SupportTicket $ticket, User $user, string $body): SupportTicketInternalNote
    {
        return $ticket->internalNotes()->create([
            'user_id' => $user->id,
            'body' => trim($body),
        ]);
    }

    public function assignTicket(SupportTicket $ticket, User $assigner, User $assignee): SupportTicket
    {
        $ticket->update(['assigned_to' => $assignee->id]);

        SupportTicketAssignment::create([
            'ticket_id' => $ticket->id,
            'assigned_by' => $assigner->id,
            'assigned_to' => $assignee->id,
        ]);

        return $ticket->fresh(['assignee']);
    }

    public function updateStatus(SupportTicket $ticket, SupportTicketStatus $status, User $actor): SupportTicket
    {
        $oldStatusId = $ticket->status_id;

        if ($oldStatusId === $status->id) {
            return $ticket;
        }

        $updates = ['status_id' => $status->id];

        if ($status->slug === 'resolved') {
            $updates['resolved_at'] = now();
        }

        if (in_array($status->slug, config('support.closed_status_slugs', []), true)) {
            $updates['closed_at'] = now();
            $updates['closed_by'] = $actor->id;
        }

        $ticket->update($updates);

        SupportTicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status_id' => $oldStatusId,
            'new_status_id' => $status->id,
            'changed_by' => $actor->id,
        ]);

        $ticket = $ticket->fresh(['status', 'user']);

        event(new SupportTicketStatusChanged($ticket, $actor));

        return $ticket;
    }

    public function reopenTicket(SupportTicket $ticket, User $user): SupportTicket
    {
        $open = SupportTicketStatus::query()->where('slug', 'open')->firstOrFail();

        $ticket->update([
            'resolved_at' => null,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return $this->updateStatus($ticket, $open, $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminQuery(array $filters = []): Builder
    {
        $query = SupportTicket::query()
            ->with(['user', 'assignee', 'status']);

        if (! empty($filters['global_search'])) {
            $term = '%'.Str::lower((string) $filters['global_search']).'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder
                    ->whereRaw('LOWER(ticket_number) like ?', [$term])
                    ->orWhereRaw('LOWER(subject) like ?', [$term])
                    ->orWhereRaw('LOWER(description) like ?', [$term])
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->whereRaw('LOWER(name) like ?', [$term])
                        ->orWhereRaw('LOWER(email) like ?', [$term]));
            });
        }

        if (! empty($filters['filter_status'])) {
            $query->where('status_id', (int) $filters['filter_status']);
        }

        if (! empty($filters['filter_module'])) {
            $query->where('module', (string) $filters['filter_module']);
        }

        if (! empty($filters['filter_urgency'])) {
            $query->where('urgency', (string) $filters['filter_urgency']);
        }

        if (! empty($filters['toggle_sla_breach_only'])) {
            $query->where('sla_status', 'overdue');
        }

        return $this->priority->applyPriorityOrdering($query);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->adminQuery($filters)->paginate($perPage);
    }

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->with(['status', 'assignee'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
}
