<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;

class ProfileLocationSql
{
    public static function joinMemberCountry(Builder $query, string $alias = 'profile_countries'): Builder
    {
        return $query->leftJoin("countries as {$alias}", "{$alias}.id", '=', 'profiles.country_id');
    }

    public static function memberCountrySelect(string $alias = 'profile_countries'): string
    {
        return "{$alias}.name as member_country";
    }
}
