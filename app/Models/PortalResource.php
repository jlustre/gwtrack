<?php

namespace App\Models;

use App\Support\ResourceUrl;
use App\Support\VideoEmbed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PortalResource extends Model
{
    use SoftDeletes;

    protected $table = 'resources';

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'content',
        'type',
        'category',
        'sort_order',
        'url',
        'file_path',
        'file_format',
        'pdf_generated_at',
        'is_published',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_favorites', 'resource_id', 'user_id')
            ->withTimestamps();
    }

    public function isDocumentLibraryItem(): bool
    {
        return in_array($this->type, ['document', 'file'], true);
    }

    public function isVideoLibraryItem(): bool
    {
        return $this->type === 'video' && $this->is_published;
    }

    public function resolvedVideoSource(): ?string
    {
        if (filled($this->url)) {
            return $this->url;
        }

        return $this->publicFileUrl();
    }

    public function videoThumbnailUrl(): ?string
    {
        return VideoEmbed::thumbnailUrl($this->resolvedVideoSource());
    }

    public function videoEmbedUrl(): ?string
    {
        return VideoEmbed::embedUrl($this->resolvedVideoSource());
    }

    public function resolvedFormat(): string
    {
        if ($this->file_format) {
            return strtoupper($this->file_format);
        }

        $path = $this->file_path ?: $this->url;

        if (! $path) {
            return 'DOC';
        }

        $extension = pathinfo(parse_url($path, PHP_URL_PATH) ?? $path, PATHINFO_EXTENSION);

        return $extension !== '' ? strtoupper($extension) : 'LINK';
    }

    public function hasDownloadableFile(): bool
    {
        return filled($this->file_path) && ! str_starts_with($this->file_path, 'http');
    }

    public function publicFileUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Prefer an explicit non-storage URL alias (e.g. resources/welcome-packet.pdf)
     * over generated /storage/... file paths.
     */
    public function preferredPublicUrl(): ?string
    {
        if (! filled($this->url)) {
            return null;
        }

        $relative = ltrim($this->url, '/');

        if (str_starts_with($relative, 'storage/')) {
            return null;
        }

        return ResourceUrl::resolve($this->url);
    }

    public function resolvedAccessUrl(): ?string
    {
        if ($preferred = $this->preferredPublicUrl()) {
            return $preferred;
        }

        if ($this->hasDownloadableFile()) {
            return route('resources.documents.download', $this);
        }

        if (filled($this->url)) {
            return ResourceUrl::resolve($this->url);
        }

        return $this->publicFileUrl();
    }

    public function hasHtmlPreview(): bool
    {
        return filled(trim(strip_tags((string) $this->content)));
    }

    public static function isUploadedPdfAttributes(?string $filePath, ?string $fileFormat, ?string $content): bool
    {
        return filled($filePath)
            && ! str_starts_with((string) $filePath, 'http')
            && strtoupper($fileFormat ?? 'PDF') === 'PDF'
            && blank(trim(strip_tags((string) $content)));
    }

    public function isUploadedPdf(): bool
    {
        return self::isUploadedPdfAttributes($this->file_path, $this->file_format, $this->content);
    }

    public function isPdfOnlyDocument(): bool
    {
        return $this->hasPdfPreview() && ! $this->hasHtmlPreview();
    }

    public function shouldOfferListDownload(): bool
    {
        return filled($this->resolvedAccessUrl()) && ! $this->isPdfOnlyDocument();
    }

    public function hasPdfPreview(): bool
    {
        if ($this->hasDownloadableFile()) {
            return strtoupper($this->file_format ?? 'PDF') === 'PDF';
        }

        if (filled($this->url)) {
            $path = parse_url(ResourceUrl::resolve($this->url) ?? '', PHP_URL_PATH) ?? '';

            return str_ends_with(strtolower($path), '.pdf');
        }

        return false;
    }

    public function canPreview(): bool
    {
        return $this->hasHtmlPreview()
            || $this->hasPdfPreview()
            || filled($this->url)
            || filled($this->description);
    }

    public function previewMode(): string
    {
        if ($this->hasHtmlPreview()) {
            return 'html';
        }

        if ($this->hasPdfPreview()) {
            return 'pdf';
        }

        if (filled($this->url)) {
            return 'external';
        }

        return 'summary';
    }

    public function inlinePreviewUrl(): ?string
    {
        if ($preferred = $this->preferredPublicUrl()) {
            $path = parse_url($preferred, PHP_URL_PATH) ?? '';

            if (str_ends_with(strtolower($path), '.pdf')) {
                return $preferred;
            }
        }

        if ($this->hasDownloadableFile()) {
            return $this->publicFileUrl();
        }

        if ($this->hasPdfPreview() && filled($this->url)) {
            return ResourceUrl::resolve($this->url);
        }

        return null;
    }

    public function isInteractiveForm(): bool
    {
        return filled($this->url) && str_contains($this->url, 'resources/forms/');
    }

    public function formUrl(): ?string
    {
        if (! $this->isInteractiveForm()) {
            return null;
        }

        return ResourceUrl::resolve($this->url);
    }
}
