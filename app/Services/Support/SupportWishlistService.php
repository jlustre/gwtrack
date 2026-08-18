<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\SupportTicket;
use App\Models\SupportWishlistItem;
use App\Models\SupportWishlistVote;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupportWishlistService
{
    public function __construct(
        private readonly SupportTicketPriorityService $priority,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, ?SupportTicket $ticket = null): SupportWishlistItem
    {
        return DB::transaction(function () use ($user, $data, $ticket): SupportWishlistItem {
            $item = SupportWishlistItem::create([
                'ticket_id' => $ticket?->id,
                'user_id' => $user->id,
                'title' => $data['title'],
                'module' => $data['module'],
                'problem_solved' => $data['problem_solved'],
                'suggested_description' => $data['suggested_description'],
                'example_link' => $data['example_link'] ?? null,
                'business_value' => array_values($data['business_value'] ?? []),
                'user_priority' => $data['user_priority'],
                'status' => 'submitted',
            ]);

            $this->priority->refreshWishlistScore($item);

            return $item->fresh(['user', 'votes']);
        });
    }

    public function toggleVote(SupportWishlistItem $item, User $user): bool
    {
        $existing = SupportWishlistVote::query()
            ->where('wishlist_item_id', $item->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->priority->refreshWishlistScore($item->fresh());

            return false;
        }

        SupportWishlistVote::create([
            'wishlist_item_id' => $item->id,
            'user_id' => $user->id,
        ]);

        $this->priority->refreshWishlistScore($item->fresh());

        return true;
    }

    public function updateStatus(SupportWishlistItem $item, string $status): SupportWishlistItem
    {
        $item->update([
            'status' => $status,
        ]);

        return $item->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAdminFields(SupportWishlistItem $item, array $data): SupportWishlistItem
    {
        $item->update([
            'development_complexity' => $data['development_complexity'] ?? $item->development_complexity,
            'estimated_effort_hours' => $data['estimated_effort_hours'] ?? $item->estimated_effort_hours,
            'target_release_date' => $data['target_release_date'] ?? $item->target_release_date,
            'status' => $data['status'] ?? $item->status,
        ]);

        $this->priority->refreshWishlistScore($item->fresh());

        return $item->fresh(['user', 'votes', 'ticket']);
    }

    public function convertTicketToWishlist(SupportTicket $ticket, User $actor): SupportWishlistItem
    {
        return $this->create($actor, [
            'title' => $ticket->subject,
            'module' => $ticket->module,
            'problem_solved' => 'Converted from support ticket '.$ticket->ticket_number,
            'suggested_description' => $ticket->description,
            'example_link' => $ticket->related_url,
            'business_value' => [],
            'user_priority' => match ($ticket->urgency) {
                'urgent', 'high' => 'high',
                'medium' => 'medium',
                default => 'low',
            },
        ], $ticket);
    }

    /**
     * @return array<string, Collection<int, SupportWishlistItem>>
     */
    public function boardColumns(): array
    {
        $columns = [];

        foreach (array_keys(config('support.wishlist_statuses', [])) as $status) {
            $columns[$status] = SupportWishlistItem::query()
                ->with(['user', 'votes'])
                ->withCount('votes')
                ->where('status', $status)
                ->orderByDesc('admin_priority_score')
                ->orderByDesc('created_at')
                ->get();
        }

        return $columns;
    }

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return SupportWishlistItem::query()
            ->withCount('votes')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
}
