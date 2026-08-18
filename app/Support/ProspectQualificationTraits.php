<?php

namespace App\Support;

class ProspectQualificationTraits
{
    /**
     * @return list<string>
     */
    public static function allowedKeys(): array
    {
        return array_keys(config('prospects.qualification_traits', []));
    }

    /**
     * @param  list<string>|null  $traits
     * @return list<string>
     */
    public static function normalize(?array $traits): array
    {
        if ($traits === null || $traits === []) {
            return [];
        }

        return array_values(array_intersect($traits, self::allowedKeys()));
    }

    /**
     * @param  list<string>|null  $traits
     * @return list<string>
     */
    public static function labels(?array $traits): array
    {
        $definitions = config('prospects.qualification_traits', []);

        return collect(self::normalize($traits))
            ->map(fn (string $key): string => $definitions[$key] ?? $key)
            ->values()
            ->all();
    }
}
