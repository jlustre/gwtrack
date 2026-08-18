<?php

namespace App\Services;

use App\Models\Country;
use App\Models\StateProvince;
use App\Models\Timezone;
use Illuminate\Support\Facades\Cache;

class ProfileLocationService
{
    /**
     * @var array<string, int|null>
     */
    private array $countryCache = [];

    public function mapAttributesForStorage(array $attributes, ?int $existingCountryId = null): array
    {
        $hasCountry = array_key_exists('country', $attributes);
        $hasProvince = array_key_exists('province', $attributes);
        $hasTimezone = array_key_exists('timezone', $attributes);

        $countryName = $attributes['country'] ?? null;
        $provinceName = $attributes['province'] ?? null;
        $timezoneName = $attributes['timezone'] ?? null;

        unset($attributes['country'], $attributes['province'], $attributes['timezone']);

        if ($hasCountry) {
            $attributes['country_id'] = filled($countryName) ? $this->countryId($countryName) : null;
        }

        $countryId = $attributes['country_id'] ?? $existingCountryId;

        if ($hasProvince) {
            $attributes['state_province_id'] = filled($provinceName) && $countryId
                ? $this->stateProvinceId($countryId, $provinceName)
                : null;
        }

        if ($hasTimezone) {
            $attributes['timezone_id'] = filled($timezoneName)
                ? $this->timezoneId($timezoneName, $countryId)
                : null;
        }

        return $attributes;
    }

    public function countryId(?string $name): ?int
    {
        if (! filled($name)) {
            return null;
        }

        if (array_key_exists($name, $this->countryCache)) {
            return $this->countryCache[$name];
        }

        $id = Cache::rememberForever('profile-location.country.'.$name, fn () => Country::query()
            ->where('name', $name)
            ->value('id'));

        return $this->countryCache[$name] = $id ? (int) $id : null;
    }

    public function stateProvinceId(?int $countryId, ?string $name): ?int
    {
        if (! $countryId || ! filled($name)) {
            return null;
        }

        return StateProvince::query()
            ->where('country_id', $countryId)
            ->where('name', $name)
            ->value('id');
    }

    public function timezoneId(?string $nameOrCode, ?int $countryId = null): ?int
    {
        if (! filled($nameOrCode)) {
            return null;
        }

        $query = Timezone::query()
            ->where(function ($query) use ($nameOrCode): void {
                $query->where('name', $nameOrCode)
                    ->orWhere('code', $nameOrCode);
            });

        if ($countryId) {
            $query->where(function ($query) use ($countryId): void {
                $query->where('country_id', $countryId)
                    ->orWhereNull('country_id');
            });
        }

        return $query->value('id');
    }
}
