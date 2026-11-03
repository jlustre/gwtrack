@if ($activeAssignment)
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Mentorship lifecycle</p>
                <h2 class="mt-1 text-lg font-semibold text-gw-primary">Active Field Apprenticeship</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Started {{ $activeAssignment->started_at?->format('M j, Y') ?? 'date not recorded' }}.
                    Confirmed {{ $activeAssignment->confirmed_at?->format('M j, Y') ?? 'date not recorded' }}.
                </p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Active</span>
        </div>

        <form wire:submit="completeTraineeAssignment" class="mt-4 space-y-3">
            <label for="assignment-outcome-notes" class="block text-sm font-medium text-slate-700">Closing notes</label>
            <textarea
                id="assignment-outcome-notes"
                wire:model="assignmentOutcomeNotes"
                rows="2"
                maxlength="2000"
                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                placeholder="Summarize the apprentice's readiness and any follow-up."
            ></textarea>
            @error('assignment') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            @error('assignmentOutcomeNotes') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            <div class="flex flex-wrap gap-2">
                <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                    Complete FAP mentorship
                </button>
                <button
                    type="button"
                    wire:click="endTraineeAssignment"
                    wire:confirm="End this mentor assignment? The associate will be unassigned and the history retained."
                    wire:loading.attr="disabled"
                    class="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                >
                    End assignment
                </button>
            </div>
            <p class="text-xs text-slate-500">
                Completion requires every required FAP checklist item to be confirmed. Use End assignment when the mentorship is stopping before graduation; explain the reason in the closing notes.
            </p>
        </form>

        @if ($activeAssignment->events()->exists())
            <details class="mt-5 border-t border-slate-100 pt-4">
                <summary class="cursor-pointer text-sm font-semibold text-gw-primary">Assignment history</summary>
                <ul class="mt-3 space-y-2">
                    @foreach ($activeAssignment->events()->with('actor')->oldest()->get() as $event)
                        <li class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            <span class="font-semibold text-slate-800">{{ str($event->event_type)->replace('_', ' ')->title() }}</span>
                            · {{ $event->created_at->format('M j, Y g:i A') }}
                            · {{ $event->actor?->name ?? 'System' }}
                            @if ($event->notes) <p class="mt-1 whitespace-pre-line">{{ $event->notes }}</p> @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    </section>
@endif
