@if (count($portal['rankTiers']) > 0)
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gw-primary">CFM Rank Structure</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($portal['rankTiers'] as $tier)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-2 flex items-center gap-2">
                        @if ($tier->icon)
                            <span class="text-xl" aria-hidden="true">{{ $tier->icon }}</span>
                        @endif
                        <h4 class="font-semibold text-gw-primary">{{ $tier->title }}</h4>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-600">{{ $tier->criteria }}</p>
                    @if ($tier->next_step)
                        <p class="mt-2 text-xs text-gw-accent-dark">Next: {{ $tier->next_step }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($portal['advancementGuideline'])
            <p class="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-600">{{ $portal['advancementGuideline'] }}</p>
        @endif
    </div>
@endif
