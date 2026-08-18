<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <nav class="flex min-w-max gap-1 p-2">
        @foreach ($sections as $section)
            <button
                type="button"
                wire:click="setSection(@js($section['key']))"
                @class([
                    'rounded-lg px-3 py-2 text-sm font-semibold transition',
                    'bg-gw-primary text-gw-accent' => $activeSection === $section['key'],
                    'text-slate-600 hover:bg-slate-100' => $activeSection !== $section['key'],
                ])
            >
                {{ $section['label'] }}
                @if ($section['phase'] > 5 && $activeSection !== $section['key'])
                    <span class="ml-1 text-[0.6rem] font-normal uppercase text-slate-400">P{{ $section['phase'] }}</span>
                @endif
            </button>
        @endforeach
    </nav>
</div>
