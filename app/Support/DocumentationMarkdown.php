<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class DocumentationMarkdown
{
    public static function toHtml(string $markdown): string
    {
        $html = Str::markdown($markdown);

        $html = preg_replace_callback(
            '/<h([1-6])>(.*?)<\/h\1>/s',
            function (array $matches): string {
                $level = $matches[1];
                $inner = $matches[2];
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $id = Str::slug($text);

                return sprintf('<h%s id="%s">%s</h%s>', $level, e($id), $inner, $level);
            },
            $html,
        );

        return $html ?? '';
    }
}
