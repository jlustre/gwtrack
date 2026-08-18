<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;

class DocumentLinkExtractor
{
    /**
     * @return array<int, array{url: string, title: string}>
     */
    public static function fromHtml(string $html): array
    {
        $html = trim($html);

        if ($html === '') {
            return [];
        }

        $links = [];

        libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $document->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        $xpath = new DOMXPath($document);

        /** @var \DOMElement $node */
        foreach ($xpath->query('//a[@href]') as $node) {
            $href = trim((string) $node->getAttribute('href'));

            if (! self::isExternalLink($href)) {
                continue;
            }

            $url = self::normalizeUrl($href);
            $title = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');

            if ($title === '') {
                $title = self::titleFromUrl($url);
            }

            $links[] = [
                'url' => $url,
                'title' => $title,
            ];
        }

        libxml_clear_errors();

        return self::dedupe($links);
    }

    public static function isExternalLink(string $href): bool
    {
        if ($href === '' || str_starts_with($href, '#')) {
            return false;
        }

        if (preg_match('#^(mailto:|tel:|javascript:)#i', $href)) {
            return false;
        }

        return str_starts_with(strtolower($href), 'http://')
            || str_starts_with(strtolower($href), 'https://');
    }

    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (str_ends_with($url, '/')) {
            $url = rtrim($url, '/');
        }

        return $url;
    }

    public static function titleFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? $url;
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if ($path !== '' && $path !== '/') {
            return str($path)->trim('/')->replace(['-', '_'], ' ')->title()->toString();
        }

        return str($host)->replace('www.', '')->headline()->toString();
    }

    /**
     * @param  array<int, array{url: string, title: string}>  $links
     * @return array<int, array{url: string, title: string}>
     */
    private static function dedupe(array $links): array
    {
        $unique = [];

        foreach ($links as $link) {
            $unique[self::normalizeUrl($link['url'])] = $link;
        }

        return array_values($unique);
    }
}
