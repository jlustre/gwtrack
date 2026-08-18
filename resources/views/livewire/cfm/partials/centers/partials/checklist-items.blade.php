<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Checklist items</h3>
    </div>

    @if (count($center['items']) === 0)
        <p class="p-6 text-sm text-slate-500">No checklist items are configured for this tracker.</p>
    @else
        <ul class="divide-y divide-slate-200">
            @foreach ($center['items'] as $item)
                <li wire:key="checklist-item-{{ $item['id'] }}" class="px-5 py-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide',
                                    'bg-emerald-100 text-emerald-800' => $item['is_completed'],
                                    'bg-amber-100 text-amber-800' => $item['is_pending'],
                                    'bg-red-100 text-red-800' => $item['is_rejected'],
                                    'bg-slate-100 text-slate-600' => ! $item['is_completed'] && ! $item['is_pending'] && ! $item['is_rejected'],
                                ])>{{ $item['status_label'] }}</span>
                                @if ($item['is_required'])
                                    <span class="text-[0.65rem] font-semibold uppercase text-gw-accent-dark">Required</span>
                                @endif
                            </div>
                            <p class="mt-2 font-semibold text-gw-primary">{{ $item['title'] }}</p>
                            @if ($item['description'])
                                <p class="mt-1 text-sm text-slate-600">{{ $item['description'] }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                @if ($item['expected_due_date'])
                                    <span>Due {{ $item['expected_due_date'] }}</span>
                                @endif
                                @if ($item['completed_at'])
                                    <span>Completed {{ $item['completed_at'] }}@if($item['completed_by']) by {{ $item['completed_by'] }}@endif</span>
                                @endif
                                @if ($item['submitted_at'])
                                    <span>Submitted {{ $item['submitted_at'] }}</span>
                                @endif
                            </div>
                            @if ($item['review_comments'])
                                <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800">Review note: {{ $item['review_comments'] }}</p>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
