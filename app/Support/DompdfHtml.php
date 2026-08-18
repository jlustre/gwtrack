<?php

namespace App\Support;

class DompdfHtml
{
    private const TWEMOJI_BASE = 'https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72';

    /**
     * Prepare rich HTML for dompdf rendering (emoji, inline styles).
     */
    public static function prepare(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        return self::replaceEmojiWithImages($html);
    }

    public static function replaceEmojiWithImages(string $html): string
    {
        return preg_replace_callback(
            '/(\p{Extended_Pictographic}(?:\x{FE0F}|\x{200D}\p{Extended_Pictographic})*)/u',
            function (array $matches): string {
                $filename = self::emojiToTwemojiFilename($matches[1]);

                if ($filename === '') {
                    return $matches[1];
                }

                $url = self::TWEMOJI_BASE.'/'.$filename.'.png';

                return '<img src="'.$url.'" class="emoji-inline" alt="" '
                    .'style="width:1.05em;height:1.05em;vertical-align:-0.12em;margin-right:0.2em;" />';
            },
            $html,
        ) ?? $html;
    }

    public static function emojiToTwemojiFilename(string $emoji): string
    {
        $codes = [];
        $length = mb_strlen($emoji, 'UTF-8');

        for ($index = 0; $index < $length; $index++) {
            $char = mb_substr($emoji, $index, 1, 'UTF-8');
            $code = mb_ord($char, 'UTF-8');

            if ($code === 0xFE0F) {
                continue;
            }

            if ($code === 0x200D) {
                $codes[] = '200d';

                continue;
            }

            $codes[] = strtolower(dechex($code));
        }

        return implode('-', $codes);
    }
}
