@php
    $fieldId = trim(($fieldIdPrefix ?? '').'_'.$field['name'], '_');
    $value = old($field['name'], $record ? data_get($record, $field['name']) : null);
    $hasStoredPdf = filled($record?->file_path) && ! str_starts_with((string) $record->file_path, 'http');
    $storedPdfName = $hasStoredPdf ? basename($record->file_path) : null;
@endphp

<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <label for="{{ $fieldId }}" class="block text-sm font-semibold text-gw-primary">{{ $field['label'] }}</label>
        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs font-semibold">
            <button
                type="button"
                @click="contentMode = 'compose'"
                :class="contentMode === 'compose' ? 'bg-white text-gw-primary shadow-sm' : 'text-slate-500 hover:text-gw-primary'"
                class="rounded-md px-3 py-1.5 transition"
            >
                Compose content
            </button>
            <button
                type="button"
                @click="contentMode = 'upload'"
                :class="contentMode === 'upload' ? 'bg-white text-gw-primary shadow-sm' : 'text-slate-500 hover:text-gw-primary'"
                class="rounded-md px-3 py-1.5 transition"
            >
                Upload PDF
            </button>
        </div>
    </div>

    <input type="hidden" name="content_source" :value="contentMode">

    <div x-show="contentMode === 'compose'" x-cloak class="mt-2">
        <p class="text-xs leading-5 text-slate-500">
            Compose the document body here, then use <span class="font-semibold">Generate PDF on save</span> to create a downloadable PDF for members.
        </p>
        <textarea
            id="{{ $fieldId }}"
            name="{{ $field['name'] }}"
            rows="{{ $field['rows'] ?? 14 }}"
            data-rich-text
            class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
        >{{ $value }}</textarea>
    </div>

    <div x-show="contentMode === 'upload'" x-cloak class="mt-2">
        <p class="text-xs leading-5 text-slate-500">
            Upload a PDF file to use as the member-facing document. This replaces any previously stored PDF for this record.
        </p>
        @if ($hasStoredPdf)
            <p class="mt-2 text-xs text-slate-600">
                Current file: <span class="font-semibold text-gw-primary">{{ $storedPdfName }}</span>
            </p>
        @endif
        <input
            id="{{ $fieldId }}_upload"
            name="pdf_file"
            type="file"
            accept="application/pdf,.pdf"
            class="mt-2 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-gw-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#13345f]"
        >
        @error('pdf_file')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @error($field['name'])
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
