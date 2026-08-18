<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function stateProvinces(): HasMany
    {
        return $this->hasMany(StateProvince::class)->orderBy('sort_order');
    }

    public function timezones(): HasMany
    {
        return $this->hasMany(Timezone::class)->orderBy('sort_order');
    }
}
