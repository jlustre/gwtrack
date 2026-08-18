<?php

namespace App\Models;

use App\Services\ProfileLocationService;
use App\Services\ProfilePhotoService;
use App\Support\LocationOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'phone',
        'city',
        'country_id',
        'state_province_id',
        'timezone_id',
        'best_contact_time',
        'license_number',
        'is_licensed_life_insurance',
        'insurance_licenses',
        'gw_associate_id',
        'gw_invite_link',
        'is_gw_active_associate',
        'recruited_at',
        'bio',
        'profile_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'country_id' => 'integer',
            'state_province_id' => 'integer',
            'timezone_id' => 'integer',
            'recruited_at' => 'date',
            'is_gw_active_associate' => 'boolean',
            'is_licensed_life_insurance' => 'boolean',
            'insurance_licenses' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function countryRecord(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function stateProvince(): BelongsTo
    {
        return $this->belongsTo(StateProvince::class, 'state_province_id');
    }

    public function timezoneRecord(): BelongsTo
    {
        return $this->belongsTo(Timezone::class, 'timezone_id');
    }

    protected function country(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->countryRecord?->name);
    }

    protected function province(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->stateProvince?->name);
    }

    protected function timezone(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->timezoneRecord?->code ?? $this->timezoneRecord?->name);
    }

    protected function phpTimezone(): Attribute
    {
        return Attribute::get(fn (): string => LocationOptions::toPhpTimezone($this->timezone));
    }

    /**
     * @return list<string>
     */
    public function insuranceLicenseLabels(): array
    {
        return LocationOptions::labelsForJurisdictionKeys($this->insurance_licenses ?? []);
    }

    public function hasInsuranceLicenses(): bool
    {
        return $this->isLicensedLifeInsurance() && ($this->insurance_licenses ?? []) !== [];
    }

    public function isLicensedLifeInsurance(): bool
    {
        return (bool) $this->is_licensed_life_insurance;
    }

    public function fill(array $attributes)
    {
        $attributes = app(ProfileLocationService::class)->mapAttributesForStorage(
            $attributes,
            $this->country_id,
        );

        return parent::fill($attributes);
    }

    protected static function booted(): void
    {
        static::deleting(function (Profile $profile): void {
            app(ProfilePhotoService::class)->deleteStoredFile($profile->profile_photo_path);
        });
    }
}
