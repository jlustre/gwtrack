<div>
    @if ($alerts->isNotEmpty())
        <div class="border-b border-red-300 bg-gradient-to-r from-red-700 to-[#991B1B] px-4 py-3 text-white lg:px-8">
            <div class="space-y-2">
                @foreach ($alerts as $alert)
                    <div
                        wire:key="communication-critical-{{ $alert['slug'] }}"
                        class="flex flex-col gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-red-700">
                                    {{ $alert['priority_label'] }}
                                </span>
                                <p class="text-sm font-semibold">{{ $alert['title'] }}</p>
                            </div>
                            @if ($alert['summary'])
                                <p class="mt-1 text-sm text-red-100">{{ $alert['summary'] }}</p>
                            @endif
                            <p class="mt-1 text-xs text-red-100/90">Acknowledgement required before this alert clears.</p>
                        </div>
                        <div class="flex shrink-0 items-center">
                            <a
                                href="{{ $alert['url'] }}"
                                class="inline-flex items-center rounded-md border border-gw-accent bg-gw-accent px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover"
                            >
                                Review & acknowledge
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
