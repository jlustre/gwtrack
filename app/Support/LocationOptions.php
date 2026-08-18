<?php

namespace App\Support;

use App\Models\Country;
use App\Models\StateProvince;
use App\Models\Timezone;
use Illuminate\Support\Facades\Schema;

class LocationOptions
{
    public static function countries(): array
    {
        return [
            'Canada',
            'United States',
            'Philippines',
            'Mexico',
            'Puerto Rico',
        ];
    }

    public static function timezones(): array
    {
        return [
            'PST' => 'PST',
            'MST' => 'MST',
            'CST' => 'CST',
            'EST' => 'EST',
            'HST' => 'HST',
            'Halifax Time' => 'Halifax Time',
            'Canada Pacific Time' => 'Canada Pacific Time',
            'Canada Mountain Time' => 'Canada Mountain Time',
            'Canada Central Time' => 'Canada Central Time',
            'Canada Eastern Time' => 'Canada Eastern Time',
            'Philippines Time' => 'Philippines Time',
            'Mexico Pacific Time' => 'Mexico Pacific Time',
            'Mexico Mountain Time' => 'Mexico Mountain Time',
            'Mexico Central Time' => 'Mexico Central Time',
            'Mexico Eastern Time' => 'Mexico Eastern Time',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function phpTimezoneMap(): array
    {
        return [
            'PST' => 'America/Los_Angeles',
            'MST' => 'America/Denver',
            'CST' => 'America/Chicago',
            'EST' => 'America/New_York',
            'HST' => 'Pacific/Honolulu',
            'Halifax Time' => 'America/Halifax',
            'Canada Pacific Time' => 'America/Vancouver',
            'Canada Mountain Time' => 'America/Edmonton',
            'Canada Central Time' => 'America/Winnipeg',
            'Canada Eastern Time' => 'America/Toronto',
            'Philippines Time' => 'Asia/Manila',
            'Mexico Pacific Time' => 'America/Tijuana',
            'Mexico Mountain Time' => 'America/Chihuahua',
            'Mexico Central Time' => 'America/Mexico_City',
            'Mexico Eastern Time' => 'America/Cancun',
        ];
    }

    public static function toPhpTimezone(?string $codeOrName, ?string $fallback = null): string
    {
        if (! filled($codeOrName)) {
            return $fallback ?? config('app.timezone', 'UTC');
        }

        if (isset(self::phpTimezoneMap()[$codeOrName])) {
            return self::phpTimezoneMap()[$codeOrName];
        }

        try {
            new \DateTimeZone($codeOrName);

            return $codeOrName;
        } catch (\Exception) {
            // Fall through to fallback.
        }

        return $fallback ?? config('app.timezone', 'UTC');
    }

    public static function provincesByCountry(): array
    {
        return [
            'Canada' => [
                'Alberta' => 'Alberta',
                'British Columbia' => 'British Columbia',
                'Manitoba' => 'Manitoba',
                'New Brunswick' => 'New Brunswick',
                'Newfoundland and Labrador' => 'Newfoundland and Labrador',
                'Nova Scotia' => 'Nova Scotia',
                'Ontario' => 'Ontario',
                'Prince Edward Island' => 'Prince Edward Island',
                'Quebec' => 'Quebec',
                'Saskatchewan' => 'Saskatchewan',
                'Northwest Territories' => 'Northwest Territories',
                'Nunavut' => 'Nunavut',
                'Yukon' => 'Yukon',
            ],
            'United States' => [
                'Alabama' => 'Alabama',
                'Alaska' => 'Alaska',
                'Arizona' => 'Arizona',
                'Arkansas' => 'Arkansas',
                'California' => 'California',
                'Colorado' => 'Colorado',
                'Connecticut' => 'Connecticut',
                'Delaware' => 'Delaware',
                'Florida' => 'Florida',
                'Georgia' => 'Georgia',
                'Hawaii' => 'Hawaii',
                'Idaho' => 'Idaho',
                'Illinois' => 'Illinois',
                'Indiana' => 'Indiana',
                'Iowa' => 'Iowa',
                'Kansas' => 'Kansas',
                'Kentucky' => 'Kentucky',
                'Louisiana' => 'Louisiana',
                'Maine' => 'Maine',
                'Maryland' => 'Maryland',
                'Massachusetts' => 'Massachusetts',
                'Michigan' => 'Michigan',
                'Minnesota' => 'Minnesota',
                'Mississippi' => 'Mississippi',
                'Missouri' => 'Missouri',
                'Montana' => 'Montana',
                'Nebraska' => 'Nebraska',
                'Nevada' => 'Nevada',
                'New Hampshire' => 'New Hampshire',
                'New Jersey' => 'New Jersey',
                'New Mexico' => 'New Mexico',
                'New York' => 'New York',
                'North Carolina' => 'North Carolina',
                'North Dakota' => 'North Dakota',
                'Ohio' => 'Ohio',
                'Oklahoma' => 'Oklahoma',
                'Oregon' => 'Oregon',
                'Pennsylvania' => 'Pennsylvania',
                'Rhode Island' => 'Rhode Island',
                'South Carolina' => 'South Carolina',
                'South Dakota' => 'South Dakota',
                'Tennessee' => 'Tennessee',
                'Texas' => 'Texas',
                'Utah' => 'Utah',
                'Vermont' => 'Vermont',
                'Virginia' => 'Virginia',
                'Washington' => 'Washington',
                'West Virginia' => 'West Virginia',
                'Wisconsin' => 'Wisconsin',
                'Wyoming' => 'Wyoming',
                'District of Columbia' => 'District of Columbia',
            ],
            'Philippines' => [
                'National Capital Region' => 'National Capital Region',
                'Calabarzon' => 'Calabarzon',
                'Central Luzon' => 'Central Luzon',
                'Central Visayas' => 'Central Visayas',
                'Davao Region' => 'Davao Region',
                'Western Visayas' => 'Western Visayas',
                'Ilocos Region' => 'Ilocos Region',
                'Bicol Region' => 'Bicol Region',
                'Northern Mindanao' => 'Northern Mindanao',
                'Soccsksargen' => 'Soccsksargen',
            ],
            'Mexico' => [
                'Aguascalientes' => 'Aguascalientes',
                'Baja California' => 'Baja California',
                'Baja California Sur' => 'Baja California Sur',
                'Campeche' => 'Campeche',
                'Chiapas' => 'Chiapas',
                'Chihuahua' => 'Chihuahua',
                'Ciudad de Mexico' => 'Ciudad de Mexico',
                'Coahuila' => 'Coahuila',
                'Colima' => 'Colima',
                'Durango' => 'Durango',
                'Guanajuato' => 'Guanajuato',
                'Guerrero' => 'Guerrero',
                'Hidalgo' => 'Hidalgo',
                'Jalisco' => 'Jalisco',
                'Mexico State' => 'Mexico State',
                'Michoacan' => 'Michoacan',
                'Morelos' => 'Morelos',
                'Nayarit' => 'Nayarit',
                'Nuevo Leon' => 'Nuevo Leon',
                'Oaxaca' => 'Oaxaca',
                'Puebla' => 'Puebla',
                'Queretaro' => 'Queretaro',
                'Quintana Roo' => 'Quintana Roo',
                'San Luis Potosi' => 'San Luis Potosi',
                'Sinaloa' => 'Sinaloa',
                'Sonora' => 'Sonora',
                'Tabasco' => 'Tabasco',
                'Tamaulipas' => 'Tamaulipas',
                'Tlaxcala' => 'Tlaxcala',
                'Veracruz' => 'Veracruz',
                'Yucatan' => 'Yucatan',
                'Zacatecas' => 'Zacatecas',
            ],
            'Puerto Rico' => [
                'Puerto Rico' => 'Puerto Rico',
            ],
        ];
    }

    /**
     * Three-letter country codes for jurisdiction license display (e.g. BC:CAN, CA:USA).
     *
     * @return array<string, string>
     */
    public static function countryJurisdictionDisplayCodes(): array
    {
        return [
            'Canada' => 'CAN',
            'United States' => 'USA',
            'Philippines' => 'PHL',
            'Mexico' => 'MEX',
            'Puerto Rico' => 'PRI',
        ];
    }

    public static function countryJurisdictionDisplayCode(?string $country): string
    {
        if (! filled($country)) {
            return '';
        }

        return self::countryJurisdictionDisplayCodes()[$country]
            ?? strtoupper(substr(preg_replace('/\s+/', '', $country) ?? $country, 0, 3));
    }

    /**
     * @return array<string, string>
     */
    public static function countryDisplayCodes(): array
    {
        return [
            'Canada' => 'CA',
            'United States' => 'US',
            'Philippines' => 'PH',
            'Mexico' => 'MX',
            'Puerto Rico' => 'PR',
        ];
    }

    public static function countryDisplayCode(?string $country): string
    {
        if (! filled($country)) {
            return '';
        }

        return self::countryDisplayCodes()[$country] ?? strtoupper(substr($country, 0, 2));
    }

    public static function provinceDisplayCode(?string $country, ?string $province): string
    {
        if (! filled($province)) {
            return '';
        }

        if ($country === 'Puerto Rico') {
            return 'PR';
        }

        $map = match ($country) {
            'Canada' => self::canadaProvinceCodes(),
            'United States' => self::usStateCodes(),
            'Philippines' => self::philippinesRegionCodes(),
            'Mexico' => self::mexicoStateCodes(),
            default => [],
        };

        if (isset($map[$province])) {
            return $map[$province];
        }

        if (strlen($province) === 2) {
            return strtoupper($province);
        }

        return strtoupper(substr(preg_replace('/\s+/', '', $province) ?? $province, 0, 2));
    }

    /**
     * @return array<string, string>
     */
    private static function canadaProvinceCodes(): array
    {
        return [
            'Alberta' => 'AB',
            'British Columbia' => 'BC',
            'Manitoba' => 'MB',
            'New Brunswick' => 'NB',
            'Newfoundland and Labrador' => 'NL',
            'Nova Scotia' => 'NS',
            'Ontario' => 'ON',
            'Prince Edward Island' => 'PE',
            'Quebec' => 'QC',
            'Saskatchewan' => 'SK',
            'Northwest Territories' => 'NT',
            'Nunavut' => 'NU',
            'Yukon' => 'YT',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function usStateCodes(): array
    {
        return [
            'Alabama' => 'AL', 'Alaska' => 'AK', 'Arizona' => 'AZ', 'Arkansas' => 'AR',
            'California' => 'CA', 'Colorado' => 'CO', 'Connecticut' => 'CT', 'Delaware' => 'DE',
            'Florida' => 'FL', 'Georgia' => 'GA', 'Hawaii' => 'HI', 'Idaho' => 'ID',
            'Illinois' => 'IL', 'Indiana' => 'IN', 'Iowa' => 'IA', 'Kansas' => 'KS',
            'Kentucky' => 'KY', 'Louisiana' => 'LA', 'Maine' => 'ME', 'Maryland' => 'MD',
            'Massachusetts' => 'MA', 'Michigan' => 'MI', 'Minnesota' => 'MN', 'Mississippi' => 'MS',
            'Missouri' => 'MO', 'Montana' => 'MT', 'Nebraska' => 'NE', 'Nevada' => 'NV',
            'New Hampshire' => 'NH', 'New Jersey' => 'NJ', 'New Mexico' => 'NM', 'New York' => 'NY',
            'North Carolina' => 'NC', 'North Dakota' => 'ND', 'Ohio' => 'OH', 'Oklahoma' => 'OK',
            'Oregon' => 'OR', 'Pennsylvania' => 'PA', 'Rhode Island' => 'RI', 'South Carolina' => 'SC',
            'South Dakota' => 'SD', 'Tennessee' => 'TN', 'Texas' => 'TX', 'Utah' => 'UT',
            'Vermont' => 'VT', 'Virginia' => 'VA', 'Washington' => 'WA', 'West Virginia' => 'WV',
            'Wisconsin' => 'WI', 'Wyoming' => 'WY', 'District of Columbia' => 'DC',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function philippinesRegionCodes(): array
    {
        return [
            'National Capital Region' => 'NC',
            'Calabarzon' => 'CL',
            'Central Luzon' => 'LU',
            'Central Visayas' => 'CV',
            'Davao Region' => 'DV',
            'Western Visayas' => 'WV',
            'Ilocos Region' => 'IL',
            'Bicol Region' => 'BC',
            'Northern Mindanao' => 'NM',
            'Soccsksargen' => 'SG',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function mexicoStateCodes(): array
    {
        return [
            'Aguascalientes' => 'AG', 'Baja California' => 'BC', 'Baja California Sur' => 'BS',
            'Campeche' => 'CP', 'Chiapas' => 'CS', 'Chihuahua' => 'CH', 'Ciudad de Mexico' => 'DF',
            'Coahuila' => 'CO', 'Colima' => 'CL', 'Durango' => 'DG', 'Guanajuato' => 'GT',
            'Guerrero' => 'GR', 'Hidalgo' => 'HG', 'Jalisco' => 'JA', 'Mexico State' => 'EM',
            'Michoacan' => 'MI', 'Morelos' => 'MO', 'Nayarit' => 'NA', 'Nuevo Leon' => 'NL',
            'Oaxaca' => 'OA', 'Puebla' => 'PU', 'Queretaro' => 'QE', 'Quintana Roo' => 'QR',
            'San Luis Potosi' => 'SL', 'Sinaloa' => 'SI', 'Sonora' => 'SO', 'Tabasco' => 'TB',
            'Tamaulipas' => 'TM', 'Tlaxcala' => 'TL', 'Veracruz' => 'VE', 'Yucatan' => 'YU',
            'Zacatecas' => 'ZA',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function jurisdictionDisplayLabels(): array
    {
        $labels = [];

        foreach (self::provincesByCountry() as $country => $provinces) {
            foreach (array_keys($provinces) as $province) {
                $key = self::jurisdictionKey($country, $province);
                $labels[$key] = self::formatJurisdictionLabel($country, $province);
            }
        }

        return $labels;
    }

    public static function provincesFor(?string $country): array
    {
        if (! filled($country)) {
            return [];
        }

        return self::provincesByCountry()[$country] ?? [];
    }

    public static function isValidProvince(?string $country, ?string $province): bool
    {
        if (! filled($province)) {
            return true;
        }

        if (! filled($country)) {
            return false;
        }

        $provinces = self::provincesFor($country);

        return array_key_exists($province, $provinces) || in_array($province, $provinces, true);
    }

    public static function contactTimes(): array
    {
        return [
            'Morning (8am – 12pm)' => 'Morning (8am – 12pm)',
            'Afternoon (12pm – 5pm)' => 'Afternoon (12pm – 5pm)',
            'Evening (5pm – 9pm)' => 'Evening (5pm – 9pm)',
            'Weekdays only' => 'Weekdays only',
            'Weekends only' => 'Weekends only',
            'Anytime' => 'Anytime',
        ];
    }

    public static function forPortal(): array
    {
        if (Schema::hasTable('countries')) {
            $countries = Country::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']);

            $provincesByCountryId = StateProvince::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'country_id', 'name'])
                ->groupBy('country_id')
                ->map(fn ($group) => $group->mapWithKeys(fn (StateProvince $province): array => [
                    (string) $province->id => $province->name,
                ])->all())
                ->all();

            $timezones = Timezone::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            return [
                'countries' => $countries->pluck('name', 'id')->all(),
                'provincesByCountryId' => $provincesByCountryId,
                'provincesByCountry' => self::provincesByCountry(),
                'timezones' => $timezones->mapWithKeys(fn (Timezone $timezone): array => [
                    (string) $timezone->id => $timezone->name,
                ])->all(),
                'contactTimes' => self::contactTimes(),
                'jurisdictionDisplayLabels' => self::jurisdictionDisplayLabels(),
            ];
        }

        return [
            'countries' => collect(self::countries())->mapWithKeys(fn (string $name): array => [$name => $name])->all(),
            'provincesByCountryId' => [],
            'provincesByCountry' => self::provincesByCountry(),
            'timezones' => collect(self::timezones())->mapWithKeys(fn (string $label, string $code): array => [$code => $label])->all(),
            'contactTimes' => self::contactTimes(),
            'jurisdictionDisplayLabels' => self::jurisdictionDisplayLabels(),
        ];
    }

    public static function isValidStateProvinceId(?int $countryId, ?int $stateProvinceId): bool
    {
        if ($stateProvinceId === null) {
            return true;
        }

        if ($countryId === null) {
            return false;
        }

        return StateProvince::query()
            ->whereKey($stateProvinceId)
            ->where('country_id', $countryId)
            ->exists();
    }

    public static function resolveCountryId(?string $name): ?int
    {
        if (! filled($name)) {
            return null;
        }

        return Country::query()->where('name', $name)->value('id');
    }

    public static function resolveStateProvinceId(?string $countryName, ?string $provinceName): ?int
    {
        if (! filled($countryName) || ! filled($provinceName)) {
            return null;
        }

        $countryId = self::resolveCountryId($countryName);

        if ($countryId === null) {
            return null;
        }

        return StateProvince::query()
            ->where('country_id', $countryId)
            ->where('name', $provinceName)
            ->value('id');
    }

    public static function resolveTimezoneId(?string $codeOrName): ?int
    {
        if (! filled($codeOrName)) {
            return null;
        }

        return Timezone::query()
            ->where(function ($query) use ($codeOrName): void {
                $query->where('code', $codeOrName)
                    ->orWhere('name', $codeOrName);
            })
            ->value('id');
    }

    /**
     * @return array{country_id: int|null, state_province_id: int|null, timezone_id: int|null}
     */
    public static function profileLocationIds(
        string $country,
        ?string $province = null,
        ?string $timezone = null,
    ): array {
        return [
            'country_id' => self::resolveCountryReference($country),
            'state_province_id' => self::resolveStateProvinceReference($province, self::resolveCountryReference($country), $country),
            'timezone_id' => self::resolveTimezoneReference($timezone),
        ];
    }

    /**
     * Convert profile location form/seed attributes to FK columns for storage.
     *
     * Accepts country/province/timezone as lookup names, codes, or numeric IDs.
     * Removes legacy string columns from the returned array.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function profileAttributesForStorage(array $attributes): array
    {
        $hasCountry = array_key_exists('country', $attributes);
        $hasProvince = array_key_exists('province', $attributes);
        $hasTimezone = array_key_exists('timezone', $attributes);

        $country = $attributes['country'] ?? null;
        $province = $attributes['province'] ?? null;
        $timezone = $attributes['timezone'] ?? null;

        unset($attributes['country'], $attributes['province'], $attributes['timezone']);

        if (! $hasCountry && ! $hasProvince && ! $hasTimezone) {
            return $attributes;
        }

        $countryId = self::resolveCountryReference($country);
        $countryName = self::countryNameForReference($country, $countryId);

        if ($hasCountry) {
            $attributes['country_id'] = $countryId;
        }

        if ($hasProvince) {
            $attributes['state_province_id'] = self::resolveStateProvinceReference($province, $countryId, $countryName);
        }

        if ($hasTimezone) {
            $attributes['timezone_id'] = self::resolveTimezoneReference($timezone);
        }

        return $attributes;
    }

    public static function resolveCountryReference(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return Country::query()->whereKey($id)->exists() ? $id : null;
        }

        return self::resolveCountryId((string) $value);
    }

    public static function resolveStateProvinceReference(mixed $value, ?int $countryId = null, ?string $countryName = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;
            $query = StateProvince::query()->whereKey($id);

            if ($countryId !== null) {
                $query->where('country_id', $countryId);
            }

            return $query->exists() ? $id : null;
        }

        if ($countryName !== null) {
            return self::resolveStateProvinceId($countryName, (string) $value);
        }

        if ($countryId !== null) {
            return StateProvince::query()
                ->where('country_id', $countryId)
                ->where('name', $value)
                ->value('id');
        }

        return null;
    }

    public static function resolveTimezoneReference(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return Timezone::query()->whereKey($id)->exists() ? $id : null;
        }

        return self::resolveTimezoneId((string) $value);
    }

    private static function countryNameForReference(mixed $value, ?int $countryId): ?string
    {
        if (is_string($value) && $value !== '' && ! is_numeric($value)) {
            return $value;
        }

        if ($countryId === null) {
            return null;
        }

        return Country::query()->whereKey($countryId)->value('name');
    }

    public static function jurisdictionKey(string $country, string $province): string
    {
        return $country.'|'.$province;
    }

    public static function isValidJurisdictionKey(string $key): bool
    {
        $parts = explode('|', $key, 2);

        if (count($parts) !== 2) {
            return false;
        }

        return self::isValidProvince($parts[0], $parts[1]);
    }

    /**
     * @param  array<int, mixed>|null  $keys
     * @return list<string>
     */
    public static function normalizeLicensedJurisdictionKeys(?array $keys): array
    {
        if (! is_array($keys)) {
            return [];
        }

        $normalized = [];

        foreach ($keys as $key) {
            if (! is_string($key) || ! self::isValidJurisdictionKey($key)) {
                continue;
            }

            $normalized[] = $key;
        }

        sort($normalized);

        return array_values(array_unique($normalized));
    }

    /**
     * @param  array<int, mixed>|null  ...$keyLists
     * @return list<string>
     */
    public static function mergeLicensedJurisdictionKeys(?array ...$keyLists): array
    {
        $merged = [];

        foreach ($keyLists as $keys) {
            if (! is_array($keys)) {
                continue;
            }

            foreach ($keys as $key) {
                if (is_string($key) && $key !== '') {
                    $merged[] = $key;
                }
            }
        }

        return self::normalizeLicensedJurisdictionKeys($merged);
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function labelsForJurisdictionKeys(array $keys): array
    {
        return array_map(function (string $key): string {
            [$country, $province] = explode('|', $key, 2);

            return self::formatJurisdictionLabel($country, $province);
        }, $keys);
    }

    public static function formatJurisdictionLabel(string $country, string $province): string
    {
        if ($country === 'Puerto Rico') {
            return 'PR:PRI';
        }

        $countryCode = self::countryJurisdictionDisplayCode($country);
        $provinceCode = self::provinceDisplayCode($country, $province);

        if ($provinceCode === '' && $countryCode !== '') {
            return $countryCode;
        }

        if ($countryCode === '') {
            return $provinceCode;
        }

        return "{$provinceCode}:{$countryCode}";
    }

    /**
     * @param  list<string>|null  $licensedKeys
     */
    public static function cfmCoversJurisdiction(?array $licensedKeys, ?string $country, ?string $province): ?bool
    {
        if (! filled($country) || ! filled($province)) {
            return null;
        }

        if ($licensedKeys === null || $licensedKeys === []) {
            return false;
        }

        return in_array(self::jurisdictionKey($country, $province), $licensedKeys, true);
    }
}
