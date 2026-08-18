<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FnaAttachment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
