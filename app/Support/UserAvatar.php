<?php

namespace App\Support;

use App\Models\User;

class UserAvatar
{
    public static function initials(?string $name): string
    {
        if (! $name) {
            return 'EF';
        }

        return collect(explode(' ', $name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => str($part)->substr(0, 1)->upper())
            ->join('') ?: 'EF';
    }

    public static function urlForUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $path = $user->relationLoaded('profile')
            ? $user->profile?->profile_photo_path
            : $user->profile()->value('profile_photo_path');

        $version = $user->relationLoaded('profile')
            ? $user->profile?->updated_at?->getTimestamp()
            : $user->profile()->value('updated_at');

        if ($version && ! is_numeric($version)) {
            $version = strtotime((string) $version) ?: null;
        }

        return self::urlForPath($path, $version ? (int) $version : null);
    }

    public static function urlForPath(?string $path, ?int $cacheVersion = null): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = str_replace('\\', '/', ltrim($path, '/'));
        $url = asset('storage/'.$normalized);

        if ($cacheVersion) {
            $url .= (str_contains($url, '?') ? '&' : '?').'v='.$cacheVersion;
        }

        return $url;
    }
}
