@php
    $isUploadedPdf = \App\Models\PortalResource::isUploadedPdfAttributes(
        $record->file_path ?? null,
        $record->file_format ?? null,
        $record->content ?? null,
    );
@endphp

@if (($showView ?? true) && ! $isUploadedPdf)
    <a
        href="{{ route('admin.management.show', ['resources', $record->id]) }}"
        @if ($useInlineModal ?? false) x-on:click.prevent="viewOpen = true" @endif
        title="View"
        aria-label="View record"
        class="group gw-icon-btn"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
        <span class="sr-only">View</span>
        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">View</span>
    </a>
@endif
@if (filled($record->file_path) && ! str_starts_with($record->file_path, 'http') && strtoupper($record->file_format ?? 'PDF') === 'PDF')
    <a
        href="{{ route('admin.management.resources.view-pdf', $record->id, absolute: false) }}"
        target="_blank"
        rel="noopener noreferrer"
        title="View PDF"
        aria-label="View PDF"
        class="group gw-icon-btn-danger"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M10 13h4" />
            <path d="M10 17h4" />
            <path d="M10 9H8" />
        </svg>
        <span class="sr-only">View PDF</span>
        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">View PDF</span>
    </a>
@elseif (filled($record->url) && str_ends_with(strtolower(parse_url(\App\Support\ResourceUrl::resolve($record->url) ?? '', PHP_URL_PATH) ?? ''), '.pdf'))
    <a
        href="{{ \App\Support\ResourceUrl::resolve($record->url) }}"
        target="_blank"
        rel="noopener noreferrer"
        title="View PDF"
        aria-label="View PDF"
        class="group gw-icon-btn-danger"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M10 13h4" />
            <path d="M10 17h4" />
            <path d="M10 9H8" />
        </svg>
        <span class="sr-only">View PDF</span>
        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">View PDF</span>
    </a>
@endif
