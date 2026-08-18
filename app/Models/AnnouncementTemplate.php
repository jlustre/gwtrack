<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementTemplate extends Model
{
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'template_type',
        'prompt_hint',
        'title_template',
        'summary_template',
        'body_template',
        'default_priority',
        'default_audience_type',
        'metadata',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AnnouncementCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  array<string, string>  $replacements
     * @return array{title: string, summary: string|null, body: string}
     */
    public function render(array $replacements = []): array
    {
        $replace = fn (?string $value) => $value !== null ? strtr($value, $replacements) : null;

        return [
            'title' => $replace($this->title_template) ?? '',
            'summary' => $replace($this->summary_template),
            'body' => $replace($this->body_template) ?? '',
        ];
    }
}
