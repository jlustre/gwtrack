<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssociateParticipationAgreement extends Model
{
    protected $fillable = [
        'user_id',
        'effective_date',
        'full_name',
        'email',
        'phone',
        'associate_id',
        'address',
        'city',
        'state_province',
        'country',
        'sponsor_name',
        'acknowledgment_accepted',
        'associate_signature',
        'associate_signed_at',
        'status',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'associate_signed_at' => 'date',
            'acknowledgment_accepted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
}
