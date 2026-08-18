@php
    $completion = $profileCompletion ?? ['percent' => 100, 'is_complete' => true, 'fields' => []];
    $fields = $completion['fields'] ?? [];
    $missingFields = collect($fields)->where('filled', false)->values();
    $completedCount = collect($fields)->where('filled', true)->count();
    $totalCount = count($fields);
@endphp

<section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Profile Tracking</p>
            <h2 class="mt-1 text-lg font-semibold text-gw-primary">Profile Completion</h2>
        </div>
        @if ($completion['is_complete'] ?? false)
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Complete</span>
        @else
            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $missingFields->count() }} missing</span>
        @endif
    </div>

    <p class="mt-3 text-sm leading-6 text-slate-600">
        Tracked profile details used for onboarding support and team visibility.
    </p>

    @if ($totalCount > 0)
        <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Completion</p>
                    <p class="mt-1 text-2xl font-bold text-gw-primary">{{ $completion['percent'] ?? 0 }}%</p>
                </div>
                <p class="text-right text-xs text-slate-500">
                    {{ $completedCount }} of {{ $totalCount }} fields complete
                </p>
            </div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white">
                <div
                    class="h-2.5 rounded-full bg-gw-accent transition-all duration-300"
                    style="width: {{ max(0, min(100, (int) ($completion['percent'] ?? 0))) }}%"
                ></div>
            </div>
        </div>

        @if ($missingFields->isNotEmpty())
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-900">
                <p class="font-semibold">Still needed</p>
                <p class="mt-1 text-xs leading-5 text-amber-800">
                    {{ $missingFields->pluck('label')->join(', ') }}
                </p>
            </div>
        @endif

        <div class="mt-5 space-y-2.5">
            @foreach ($fields as $field)
                <div class="flex items-start gap-2.5 text-sm">
                    @if ($field['filled'])
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </span>
                        <span class="text-slate-700">{{ $field['label'] }}</span>
                    @else
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-amber-300 bg-amber-50 text-amber-700" aria-hidden="true">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </span>
                        <span class="font-medium text-amber-900">{{ $field['label'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        @if (! ($completion['is_complete'] ?? true) && ($isOwnProfile ?? true))
            @if ($useLivewire ?? false)
                <button
                    type="button"
                    wire:click="setTab('profile')"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-md border border-gw-primary bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover"
                >
                    Complete profile details
                </button>
            @else
                <a
                    href="{{ route('profile.edit', ['tab' => 'profile']) }}"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-md border border-gw-primary bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover"
                >
                    Complete profile details
                </a>
            @endif
        @endif
    @else
        <p class="mt-4 text-sm text-slate-500">No profile fields are currently tracked.</p>
    @endif
</section>
