<?php

namespace App\Models;

use App\Support\EmailTemplateTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'token_values',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'token_values' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>  $runtimeTokens
     * @return array<string, mixed>
     */
    public function resolveTokens(array $runtimeTokens): array
    {
        $resolved = EmailTemplateTokens::merge($runtimeTokens);

        foreach ($this->normalizedTokenValues() as $key => $value) {
            $resolved[$key] = $value;
        }

        return $resolved;
    }

    /**
     * @return array<string, string>
     */
    public function normalizedTokenValues(): array
    {
        $values = [];

        foreach ($this->token_values ?? [] as $key => $value) {
            if (! is_string($key) || ! filled($value)) {
                continue;
            }

            $values[$key] = trim((string) $value);
        }

        return $values;
    }

    public function renderSubject(array $runtimeTokens): string
    {
        return $this->render($this->subject, $this->resolveTokens($runtimeTokens));
    }

    public function renderBody(array $runtimeTokens): string
    {
        $body = $this->body;

        if (! $this->containsHtmlTags($body)) {
            return nl2br($this->render($body, $this->resolveTokens($runtimeTokens)));
        }

        return $this->render($body, $this->resolveTokens($runtimeTokens));
    }

    private function render(string $content, array $tokens): string
    {
        foreach ($tokens as $token => $value) {
            $escaped = e($value);

            $content = str_replace('{{ '.$token.' }}', $escaped, $content);
            $content = str_replace('{{'.$token.'}}', $escaped, $content);
        }

        return $content;
    }

    private function containsHtmlTags(string $content): bool
    {
        return $content !== strip_tags($content);
    }
}
