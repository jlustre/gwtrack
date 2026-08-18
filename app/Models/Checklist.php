<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checklist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'checklist_type_id',
        'title',
        'description',
        'sort_order',
        'nth_day',
        'is_required',
        'requires_confirmation',
        'responsible_parties',
        'notified_parties',
        'country',
        'group_label',
        'phase_number',
        'phase_title',
        'phase_target',
        'section_title',
        'slug',
        'action_url',
        'action_label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'nth_day' => 'integer',
            'is_required' => 'boolean',
            'requires_confirmation' => 'boolean',
            'is_active' => 'boolean',
            'phase_number' => 'integer',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ChecklistType::class, 'checklist_type_id');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(ChecklistProgress::class);
    }

    public function scopeForTypeCode(Builder $query, string $code): Builder
    {
        return $query->whereHas('type', fn (Builder $typeQuery) => $typeQuery->where('code', $code));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeApplicableToCountry(Builder $query, ?string $country): Builder
    {
        return $query->where(function (Builder $query) use ($country): void {
            $query->whereNull('country');

            if (filled($country)) {
                $query->orWhere('country', $country);
            }
        });
    }
}
