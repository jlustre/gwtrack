<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Models\SupportWishlistItem;
use App\Services\Support\SupportTicketService;
use App\Services\Support\SupportWishlistService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class MySupportHub extends Component
{
    use WithPagination;

    public string $tab = 'tickets';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view own support tickets'), 403);
    }

    public function vote(int $itemId, SupportWishlistService $wishlist): void
    {
        abort_unless(auth()->user()?->can('vote on wishlist items'), 403);

        $item = SupportWishlistItem::findOrFail($itemId);
        $voted = $wishlist->toggleVote($item, auth()->user());

        session()->flash('support_status', $voted ? 'Vote added.' : 'Vote removed.');
    }

    public function render(
        SupportTicketService $tickets,
        SupportWishlistService $wishlist,
    ): View {
        return view('livewire.support.my-support-hub', [
            'tickets' => $tickets->paginateForUser(auth()->user()),
            'wishlistItems' => SupportWishlistItem::query()
                ->withCount('votes')
                ->latest()
                ->paginate(10, ['*'], 'wishlistPage'),
        ]);
    }
}
