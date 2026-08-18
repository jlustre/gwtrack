<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'sort_order',
        'max_complete_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'max_complete_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function instructions(): HasMany
    {
        return $this->hasMany(ChecklistInstruction::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'checklist_type_prerequisites',
            'checklist_type_id',
            'prerequisite_checklist_type_id',
        )->withTimestamps();
    }

    public function dependentTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'checklist_type_prerequisites',
            'prerequisite_checklist_type_id',
            'checklist_type_id',
        )->withTimestamps();
    }
}
