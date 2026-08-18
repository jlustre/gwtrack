<div>
    @if ($show && count($talkingPoints))
        <div class="rounded-xl border border-gw-accent/40 bg-gw-accent-soft shadow-sm" x-data="{ open: true }">
            <button
                type="button"
                x-on:click="open = ! open"
                class="flex w-full items-center justify-between gap-3 px-6 py-4 text-left"
            >
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Client Meeting Preparation</h2>
                    <p class="mt-1 text-sm text-slate-600">Suggested talking points for your client review</p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gw-primary transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </button>

            <div x-show="open" x-cloak class="border-t border-gw-accent/30 px-6 pb-6">
                <ul class="mt-4 list-disc space-y-2 pl-5 text-sm text-slate-700">
                    @foreach ($talkingPoints as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>

                <p class="mt-4 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs leading-5 text-slate-600">
                    {{ $complianceNotice }}
                </p>
            </div>
        </div>
    @endif
</div>
