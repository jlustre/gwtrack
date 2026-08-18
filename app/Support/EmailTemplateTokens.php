<?php

namespace App\Support;

use App\Models\User;

class EmailTemplateTokens
{
    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * @return array{member_id: string, member_name: string, member_email: string, member_phone: string}
     */
    public static function forMember(User $member): array
    {
        return [
            'member_id' => (string) $member->id,
            'member_name' => $member->name,
            'member_email' => $member->email,
            'member_phone' => $member->profile?->phone ?? '',
        ];
    }

    public static function pathFromUrl(?string $url): string
    {
        if (! filled($url)) {
            return '/';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '/';

            if (isset($parsed['query']) && $parsed['query'] !== '') {
                $path .= '?'.$parsed['query'];
            }

            return $path === '' ? '/' : $path;
        }

        return str_starts_with($url, '/') ? $url : '/'.$url;
    }

    /**
     * @return array{base_url: string, path: string}
     */
    public static function globals(?string $path = '/'): array
    {
        return [
            'base_url' => self::baseUrl(),
            'path' => filled($path) ? $path : '/',
        ];
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return array<string, mixed>
     */
    public static function merge(array $tokens, ?string $path = null): array
    {
        if ($path === null) {
            foreach ([
                'confirmation_url',
                'verification_url',
                'registration_link',
                'profile_url',
                'dashboard_url',
                'cfm_portal_url',
                'first_contact_url',
            ] as $key) {
                if (! empty($tokens[$key])) {
                    $path = self::pathFromUrl((string) $tokens[$key]);
                    break;
                }
            }
        }

        return array_merge(self::globals($path), $tokens);
    }
}
