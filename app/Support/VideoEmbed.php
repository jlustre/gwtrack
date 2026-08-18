<?php

namespace App\Support;

use App\Support\ResourceUrl;

final class VideoEmbed
{
    /**
     * @return array{provider: string, embed_url: ?string, thumbnail_url: ?string, video_id: ?string}
     */
    public static function parse(?string $url): array
    {
        $resolved = self::resolveUrl($url);

        if ($resolved === null) {
            return [
                'provider' => 'unknown',
                'embed_url' => null,
                'thumbnail_url' => null,
                'video_id' => null,
            ];
        }

        if ($youtubeId = self::youtubeId($resolved)) {
            return [
                'provider' => 'youtube',
                'embed_url' => 'https://www.youtube-nocookie.com/embed/'.$youtubeId,
                'thumbnail_url' => 'https://img.youtube.com/vi/'.$youtubeId.'/hqdefault.jpg',
                'video_id' => $youtubeId,
            ];
        }

        if ($vimeoId = self::vimeoId($resolved)) {
            return [
                'provider' => 'vimeo',
                'embed_url' => 'https://player.vimeo.com/video/'.$vimeoId,
                'thumbnail_url' => null,
                'video_id' => $vimeoId,
            ];
        }

        if (self::isDirectVideoFile($resolved)) {
            return [
                'provider' => 'file',
                'embed_url' => $resolved,
                'thumbnail_url' => null,
                'video_id' => null,
            ];
        }

        return [
            'provider' => 'external',
            'embed_url' => $resolved,
            'thumbnail_url' => null,
            'video_id' => null,
        ];
    }

    public static function embedUrl(?string $url): ?string
    {
        return self::parse($url)['embed_url'];
    }

    public static function thumbnailUrl(?string $url): ?string
    {
        return self::parse($url)['thumbnail_url'];
    }

    private static function resolveUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        if (str_starts_with($url, 'http')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        return ResourceUrl::resolve($url);
    }

    private static function youtubeId(string $url): ?string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        if (str_contains($host, 'youtu.be')) {
            return ltrim($parts['path'] ?? '', '/') ?: null;
        }

        if (! str_contains($host, 'youtube.com')) {
            return null;
        }

        if (str_starts_with($parts['path'] ?? '', '/embed/')) {
            return trim(str_replace('/embed/', '', $parts['path']), '/');
        }

        parse_str($parts['query'] ?? '', $query);

        return $query['v'] ?? null;
    }

    private static function vimeoId(string $url): ?string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! str_contains(strtolower($parts['host'] ?? ''), 'vimeo.com')) {
            return null;
        }

        if (preg_match('/\/(\d+)/', $parts['path'] ?? '', $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function isDirectVideoFile(string $url): bool
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        return str_ends_with($path, '.mp4')
            || str_ends_with($path, '.webm')
            || str_ends_with($path, '.mov');
    }
}
