<?php

namespace App\Support;

class ResourceUrl
{
    public static function isValid(?string $value): bool
    {
        if (blank($value)) {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return true;
        }

        return (bool) preg_match('#^(?:/[a-zA-Z0-9_\-./%]+|[a-zA-Z0-9_\-./%]+)$#', $value);
    }

    public static function resolve(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return url('/'.ltrim($value, '/'));
    }
}
