<div>
    @if ($alerts->isNotEmpty())
        <div class="border-b border-red-200 bg-red-50 px-4 py-3 lg:px-8">
            <div class="space-y-2">
                @foreach ($alerts as $alert)
                    <div
                        wire:key="critical-alert-{{ $alert['id'] }}"
                        class="flex flex-col gap-2 rounded-lg border border-red-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-white">
                                    {{ $alert['priority_label'] }}
                                </span>
                                <p class="text-sm font-semibold text-gw-primary">{{ $alert['title'] }}</p>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $alert['message'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if ($alert['action_url'])
                                <a
                                    href="{{ $alert['action_url'] }}"
                                    class="inline-flex items-center rounded-md border border-gw-accent bg-gw-accent px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover"
                                >
                                    View
                                </a>
                            @endif
                            <button
                                type="button"
                                wire:click="markRead('{{ $alert['id'] }}')"
                                class="text-xs font-medium text-slate-500 hover:text-gw-primary"
                            >
                                Mark read
                            </button>
                            <button
                                type="button"
                                wire:click="dismiss('{{ $alert['id'] }}')"
                                class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                aria-label="Dismiss alert"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 6 6 18M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
