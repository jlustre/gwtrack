<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProspectSharePermission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_add_notes' => 'boolean',
            'can_add_communications' => 'boolean',
            'can_schedule_followups' => 'boolean',
            'can_schedule_appointments' => 'boolean',
            'can_edit_limited_fields' => 'boolean',
            'can_collaborate_fully' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ProspectShare::class);
    }
}
