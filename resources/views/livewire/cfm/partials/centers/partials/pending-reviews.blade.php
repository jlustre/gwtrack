<div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">Awaiting CFM review</h3>
            <p class="mt-1 text-xs text-slate-600">{{ count($center['pending_reviews']) }} item(s) submitted for confirmation.</p>
        </div>
    </div>

    <div class="space-y-3">
        @foreach ($center['pending_reviews'] as $review)
            <div wire:key="review-{{ $review['progress_id'] }}" class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="font-semibold text-gw-primary">{{ $review['title'] }}</p>
                        @if ($review['description'])
                            <p class="mt-1 text-sm text-slate-600">{{ $review['description'] }}</p>
                        @endif
                        <p class="mt-2 text-xs text-slate-500">Submitted {{ $review['submitted_at'] }}</p>
                    </div>
                    <div class="flex min-w-[14rem] flex-col gap-2">
                        <textarea
                            wire:model="reviewComments"
                            rows="2"
                            placeholder="Optional review notes…"
                            class="w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        ></textarea>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                wire:click="reviewChecklistItem({{ $review['progress_id'] }}, 'confirmed')"
                                class="flex-1 rounded-lg bg-gw-accent px-3 py-2 text-xs font-bold text-gw-primary hover:bg-gw-accent-hover"
                            >
                                Approve
                            </button>
                            <button
                                type="button"
                                wire:click="reviewChecklistItem({{ $review['progress_id'] }}, 'rejected')"
                                class="flex-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
