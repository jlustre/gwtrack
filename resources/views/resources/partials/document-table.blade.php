@php
    $showFeaturedBadge = $showFeaturedBadge ?? false;
    $favoriteResourceIds = $favoriteResourceIds ?? [];
    $filters = $filters ?? [];
@endphp

<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Document</th>
                <th class="px-4 py-3">Category</th>
                <th class="hidden sm:table-cell px-4 py-3">Format</th>
                <th class="hidden lg:table-cell px-4 py-3">Description</th>
                <th class="hidden md:table-cell px-4 py-3">Updated</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($documents as $document)
                @php
                    $categoryKey = $document->category ?: 'general';
                    $categoryMeta = $categories[$categoryKey] ?? $categories['general'];
                    $format = $document->resolvedFormat();
                    $actionUrl = $document->resolvedAccessUrl();
                    $formUrl = $document->formUrl();
                @endphp
                <tr class="transition hover:bg-gw-accent-soft/40">
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($showFeaturedBadge && $document->is_featured)
                                <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-gw-accent-dark">
                                    Featured
                                </span>
                            @endif
                        </div>
                        @if ($document->isInteractiveForm() && $formUrl)
                            <a
                                href="{{ $formUrl }}"
                                class="font-semibold text-gw-primary transition hover:text-gw-accent"
                            >
                                {{ $document->title }}
                            </a>
                        @else
                            <button
                                type="button"
                                @click="openPreview({{ $document->id }})"
                                class="text-left font-semibold text-gw-primary transition hover:text-gw-accent"
                            >
                                {{ $document->title }}
                            </button>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide {{ $categoryMeta['accent'] }}">
                            {{ $categoryMeta['label'] }}
                        </span>
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3">
                        <span class="inline-flex rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-bold text-gw-primary">
                            {{ $format }}
                        </span>
                    </td>
                    <td class="hidden lg:table-cell px-4 py-3 text-slate-600">
                        <span class="line-clamp-2">{{ $document->description ?: 'No description provided.' }}</span>
                    </td>
                    <td class="hidden md:table-cell px-4 py-3 text-slate-500">
                        {{ $document->updated_at?->format('M j, Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            @if ($document->isInteractiveForm() && $formUrl)
                                <a
                                    href="{{ $formUrl }}"
                                    class="inline-flex items-center rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#13345f]"
                                >
                                    Fill Form
                                </a>
                            @else
                                @php($pdfUrl = $document->inlinePreviewUrl())
                                @php($canEditDocument = auth()->user()?->canUpdateDocument($document) ?? false)
                                @php($isFavorited = in_array($document->id, $favoriteResourceIds, true))

                                @include('resources.partials.portal-resource-favorite-button', [
                                    'formAction' => route('resources.documents.favorite', $document),
                                    'queryParams' => [
                                        'search' => $filters['search'] ?? '',
                                        'category' => $filters['category'] ?? '',
                                    ],
                                    'isFavorited' => $isFavorited,
                                ])

                                @if ($pdfUrl)
                                    <a
                                        href="{{ $pdfUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="View PDF"
                                        aria-label="View PDF for {{ $document->title }}"
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
                                @elseif ($document->canPreview())
                                    <button
                                        type="button"
                                        @click="openPreview({{ $document->id }})"
                                        title="Preview"
                                        aria-label="Preview {{ $document->title }}"
                                        class="group gw-icon-btn"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <span class="sr-only">Preview</span>
                                        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Preview</span>
                                    </button>
                                @endif

                                @if ($canEditDocument)
                                    <a
                                        href="{{ route('admin.management.edit', ['resources', $document->id]) }}"
                                        title="Edit"
                                        aria-label="Edit {{ $document->title }}"
                                        class="group gw-icon-btn"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                        </svg>
                                        <span class="sr-only">Edit</span>
                                        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Edit</span>
                                    </a>
                                @endif

                                @if ($document->shouldOfferListDownload())
                                    <a
                                        href="{{ $actionUrl }}"
                                        @if (! $document->hasDownloadableFile()) target="_blank" rel="noopener noreferrer" @endif
                                        title="{{ $document->hasDownloadableFile() ? 'Download' : 'Open' }}"
                                        aria-label="{{ $document->hasDownloadableFile() ? 'Download' : 'Open' }} {{ $document->title }}"
                                        class="group gw-icon-btn-primary"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M12 3v12" />
                                            <path d="m7 10 5 5 5-5" />
                                            <path d="M5 21h14" />
                                        </svg>
                                        <span class="sr-only">{{ $document->hasDownloadableFile() ? 'Download' : 'Open' }}</span>
                                        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">{{ $document->hasDownloadableFile() ? 'Download' : 'Open' }}</span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                        {{ $emptyMessage ?? 'No documents to display.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
