<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportWishlistVote extends Model
{
    protected $fillable = [
        'wishlist_item_id',
        'user_id',
    ];

    public function wishlistItem(): BelongsTo
    {
        return $this->belongsTo(SupportWishlistItem::class, 'wishlist_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
