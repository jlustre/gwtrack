<div>
    @if (session('review_status') === 'approved')
        <p class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Certification approved and issued.</p>
    @elseif (session('review_status') === 'rejected')
        <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">Certification request rejected.</p>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-3">
            @forelse ($pending as $record)
                <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="font-semibold text-gw-primary">{{ $record->user?->name }}</h2>
                            <p class="mt-1 text-sm text-gw-primary">{{ $record->certification?->name }}</p>
                            @if ($record->certification?->module)
                                <p class="mt-1 text-xs text-slate-500">{{ $record->certification->module->title }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-500">Requested {{ $record->updated_at?->format('M j, Y g:i A') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="approve({{ $record->id }})" class="inline-flex rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800">
                                Approve
                            </button>
                            <button type="button" wire:click="reject({{ $record->id }})" class="inline-flex rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-600">No pending certification requests.</p>
            @endforelse
        </div>
    </div>
</div>
