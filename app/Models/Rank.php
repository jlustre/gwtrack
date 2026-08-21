<?php

namespace App\Models;

use App\Support\GreatwayRankCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'description',
        'is_active',
        'track',
        'income_metric',
        'income_requirement',
        'qualification',
        'nba_requirement',
        'mb_leg_requirement',
        'svp_leg_requirement',
        'svp_leg_alternate',
        'compensation_rate',
        'ppb_rate',
        'total_fyo_rate',
        'non_fyc_rate',
        'is_field_leader',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_field_leader' => 'boolean',
            'income_requirement' => 'integer',
            'nba_requirement' => 'integer',
            'mb_leg_requirement' => 'integer',
            'svp_leg_requirement' => 'integer',
            'svp_leg_alternate' => 'integer',
            'compensation_rate' => 'decimal:2',
            'ppb_rate' => 'decimal:2',
            'total_fyo_rate' => 'decimal:2',
            'non_fyc_rate' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(RankRequirement::class);
    }

    public function isProducer(): bool
    {
        return $this->track === 'producer';
    }

    public function isBuilder(): bool
    {
        return $this->track === 'builder';
    }

    public function isFieldLeader(): bool
    {
        return (bool) $this->is_field_leader;
    }

    public function isAtLeast(string $code): bool
    {
        $threshold = static::query()
            ->where('code', GreatwayRankCatalog::resolveCode($code) ?? $code)
            ->where('is_active', true)
            ->value('sort_order');

        if ($threshold === null) {
            return false;
        }

        return $this->sort_order >= (int) $threshold;
    }

    public static function entry(): ?self
    {
        return static::query()
            ->where('code', GreatwayRankCatalog::entryCode())
            ->where('is_active', true)
            ->first();
    }

    public static function entryId(): ?int
    {
        return static::query()
            ->where('code', GreatwayRankCatalog::entryCode())
            ->value('id');
    }

    public function trackLabel(): string
    {
        return (string) config('greatway-compensation.tracks.'.$this->track.'.label', ucfirst((string) $this->track));
    }
}
