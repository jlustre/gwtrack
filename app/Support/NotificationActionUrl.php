<?php

namespace App\Support;

class NotificationActionUrl
{
    public static function fromNotificationData(array $data): ?string
    {
        $routeName = data_get($data, 'action_route');
        $routeParams = data_get($data, 'action_route_params', []);

        if (is_string($routeName) && $routeName !== '') {
            try {
                return route($routeName, is_array($routeParams) ? $routeParams : [], absolute: false);
            } catch (\Throwable) {
                // Fall through to legacy URL handling.
            }
        }

        return self::normalize(data_get($data, 'action_url'));
    }

    public static function normalize(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['path'])) {
            return $url;
        }

        $path = $parts['path'];

        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?'.$parts['query'];
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $path .= '#'.$parts['fragment'];
        }

        return $path;
    }
}
