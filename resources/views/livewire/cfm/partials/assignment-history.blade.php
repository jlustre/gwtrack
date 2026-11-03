@if ($assignmentHistory->isNotEmpty())
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Mentorship lifecycle</p>
                <h2 class="mt-1 text-lg font-semibold text-gw-primary">Recent Assignment History</h2>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Latest {{ $assignmentHistory->count() }}</span>
        </div>
        <div class="space-y-3">
            @foreach ($assignmentHistory as $assignment)
                <details wire:key="assignment-history-{{ $assignment->id }}" class="rounded-lg border border-slate-200 px-4 py-3">
                    <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="font-semibold text-gw-primary">{{ $assignment->apprentice?->name ?? 'Former associate' }}</span>
                        <span class="text-slate-500">
                            {{ str($assignment->status)->replace('_', ' ')->title() }}
                            · {{ $assignment->completed_at?->format('M j, Y') ?? $assignment->updated_at->format('M j, Y') }}
                        </span>
                    </summary>
                    <ul class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                        @foreach ($assignment->events as $event)
                            <li class="text-xs text-slate-600">
                                <span class="font-semibold text-slate-800">{{ str($event->event_type)->replace('_', ' ')->title() }}</span>
                                · {{ $event->created_at->format('M j, Y g:i A') }}
                                · {{ $event->actor?->name ?? 'System' }}
                                @if ($event->notes) <p class="mt-1 whitespace-pre-line">{{ $event->notes }}</p> @endif
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endforeach
        </div>
    </section>
@endif
