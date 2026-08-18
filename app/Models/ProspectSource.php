<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProspectSource extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }
}
