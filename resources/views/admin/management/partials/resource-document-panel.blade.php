@php
    $hasPdf = filled($record?->file_path) && ! str_starts_with($record->file_path, 'http');
    $pdfUrl = $hasPdf
        ? route('admin.management.resources.view-pdf', $record->id, absolute: false)
        : \App\Support\ResourceUrl::resolve($record->url ?? null);
@endphp

<div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gw-primary">PDF output</h2>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                Compose document content and generate a PDF, or upload a PDF directly on the form below. The stored file is used for member downloads and previews.
            </p>
            @if ($record)
                <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stored file</dt>
                        <dd class="mt-1 font-medium text-slate-800">{{ $record->file_path ?: 'Not generated yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last generated</dt>
                        <dd class="mt-1 font-medium text-slate-800">
                            {{ $record->pdf_generated_at ? \Carbon\Carbon::parse($record->pdf_generated_at)->format('M j, Y g:i A') : 'Never' }}
                        </dd>
                    </div>
                </dl>
            @endif
        </div>

        @if ($record)
            <div class="flex flex-wrap gap-2">
                @if ($pdfUrl)
                    <a
                        href="{{ $pdfUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Preview PDF
                    </a>
                @endif
                @if ($canUpdateRecord ?? true)
                <form method="POST" action="{{ route('admin.management.resources.generate-pdf', $record->id) }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-[#13345f]"
                    >
                        {{ $hasPdf ? 'Regenerate PDF' : 'Generate PDF' }}
                    </button>
                </form>
                @endif
            </div>
        @else
            <p class="text-sm text-slate-500">Create the record first, then generate a PDF from the edit screen.</p>
        @endif
    </div>
</div>
