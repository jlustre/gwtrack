<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
    <h2 class="text-lg font-semibold text-gw-primary">Attachments</h2>

    @if ($feedbackMessage)
        <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ $feedbackMessage }}</div>
    @endif

    @if ($errorMessage)
        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $errorMessage }}</div>
    @endif

    @can('update', $fna)
        <form wire:submit="uploadAttachment" class="mt-4 space-y-3 rounded-lg border border-slate-100 bg-slate-50 p-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700">Upload file</label>
                <input type="file" wire:model="attachment" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gw-primary file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                @error('attachment')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">PDF, images, or Word documents up to 10 MB.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Category (optional)</label>
                <input type="text" wire:model="category" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">
            </div>
            <button type="submit" wire:loading.attr="disabled" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover disabled:opacity-50">
                Upload
            </button>
        </form>
    @endcan

    @if ($fna->attachments->isEmpty())
        <p class="mt-4 text-sm text-slate-600">No attachments yet.</p>
    @else
        <ul class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-100">
            @foreach ($fna->attachments as $attachment)
                <li class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-gw-primary">{{ $attachment->original_name }}</p>
                        <p class="text-xs text-slate-500">
                            {{ number_format($attachment->size_bytes / 1024, 1) }} KB
                            · {{ $attachment->uploadedBy?->name ?? 'Unknown' }}
                            · {{ $attachment->created_at?->format('M j, Y') }}
                            @if ($attachment->category)
                                · {{ $attachment->category }}
                            @endif
                        </p>
                    </div>
                    @can('update', $fna)
                        <button type="button" wire:click="deleteAttachment({{ $attachment->id }})" wire:confirm="Remove this attachment?"
                            class="shrink-0 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                            Remove
                        </button>
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif
</div>
