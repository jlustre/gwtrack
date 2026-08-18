<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;

class ProfileLocationQuery
{
    public static function memberCountrySelect(): string
    {
        return 'countries.name as member_country';
    }

    public static function joinCountry(Builder $query, string $profileTable = 'profiles'): Builder
    {
        return $query->leftJoin('countries', 'countries.id', '=', "{$profileTable}.country_id");
    }
}
