<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactReferral extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'referred_at' => 'datetime',
            'accepted_at' => 'datetime',
            'converted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function referrerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'referrer_contact_id');
    }

    public function referrerProspect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'referrer_prospect_id');
    }

    public function referrerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referredContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'referred_contact_id');
    }

    public function referredProspect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'referred_prospect_id');
    }
}
