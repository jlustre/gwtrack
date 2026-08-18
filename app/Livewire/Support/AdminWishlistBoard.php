<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Models\SupportWishlistItem;
use App\Services\Support\SupportWishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AdminWishlistBoard extends Component
{
    public ?int $selectedItemId = null;

    public string $development_complexity = '';

    public ?int $estimated_effort_hours = null;

    public ?string $target_release_date = null;

    public string $status = 'submitted';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('manage enhancement wishlist'), 403);
    }

    public function openItem(int $itemId): void
    {
        $item = SupportWishlistItem::findOrFail($itemId);
        $this->selectedItemId = $item->id;
        $this->development_complexity = (string) ($item->development_complexity ?? '');
        $this->estimated_effort_hours = $item->estimated_effort_hours;
        $this->target_release_date = $item->target_release_date?->format('Y-m-d');
        $this->status = $item->status;
    }

    public function closeItem(): void
    {
        $this->selectedItemId = null;
    }

    public function moveItem(int $itemId, string $status, SupportWishlistService $wishlist): void
    {
        abort_unless(array_key_exists($status, config('support.wishlist_statuses', [])), 404);

        $item = SupportWishlistItem::findOrFail($itemId);
        $wishlist->updateStatus($item, $status);

        session()->flash('support_wishlist_status', 'Item moved to '.config('support.wishlist_statuses.'.$status));
    }

    public function saveItem(SupportWishlistService $wishlist): void
    {
        $this->validate([
            'development_complexity' => ['nullable', Rule::in(array_keys(config('support.development_complexities', [])))],
            'estimated_effort_hours' => ['nullable', 'integer', 'min:1', 'max:500'],
            'target_release_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(config('support.wishlist_statuses', [])))],
        ]);

        $item = SupportWishlistItem::findOrFail($this->selectedItemId);

        $wishlist->updateAdminFields($item, [
            'development_complexity' => $this->development_complexity ?: null,
            'estimated_effort_hours' => $this->estimated_effort_hours,
            'target_release_date' => $this->target_release_date,
            'status' => $this->status,
        ]);

        session()->flash('support_wishlist_status', 'Wishlist item updated.');
        $this->closeItem();
    }

    public function render(SupportWishlistService $wishlist): View
    {
        return view('livewire.support.admin-wishlist-board', [
            'columns' => $wishlist->boardColumns(),
            'statuses' => config('support.wishlist_statuses', []),
            'complexities' => config('support.development_complexities', []),
            'selectedItem' => $this->selectedItemId
                ? SupportWishlistItem::with(['user', 'votes', 'ticket'])->withCount('votes')->find($this->selectedItemId)
                : null,
        ]);
    }
}
