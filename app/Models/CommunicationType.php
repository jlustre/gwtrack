<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationType extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function communications(): HasMany
    {
        return $this->hasMany(ProspectCommunication::class);
    }
}
