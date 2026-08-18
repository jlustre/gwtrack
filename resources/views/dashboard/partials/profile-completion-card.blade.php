@php
    $completion = $home['profile_completion'] ?? $profileCompletion ?? [];
    $percent = (int) ($completion['percent'] ?? 0);
    $fields = $completion['fields'] ?? [];
    $filledCount = collect($fields)->where('filled', true)->count();
    $totalCount = count($fields);
    $missingFields = collect($fields)->where('filled', false)->take(3)->pluck('label')->all();
@endphp

<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Profile Completion</p>
            <h2 class="mt-1 text-lg font-semibold text-gw-primary">{{ $percent }}% complete</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $filledCount }} of {{ $totalCount }} required profile fields are filled in.
            </p>

            @if ($percent < 100 && $missingFields !== [])
                <p class="mt-2 text-xs text-slate-500">
                    Still needed: {{ implode(', ', $missingFields) }}
                    @if ($totalCount - $filledCount > count($missingFields))
                        and more
                    @endif
                </p>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($percent < 100)
                <button
                    type="button"
                    x-on:click="profileCompletionOpen = true"
                    class="inline-flex items-center rounded-full border border-gw-accent/50 bg-gw-accent-soft px-4 py-2 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-muted"
                >
                    Complete Profile
                </button>
            @endif
            <a
                href="{{ route('profile.edit') }}"
                class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-gw-accent hover:bg-gw-accent-soft"
            >
                Edit Profile
            </a>
        </div>
    </div>

    <div class="mt-4">
        <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-600">
            <span>Progress</span>
            <span class="text-gw-primary">{{ $percent }}%</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
            <div class="h-2 rounded-full bg-gw-accent transition-all duration-300" style="width: {{ max(0, min(100, $percent)) }}%"></div>
        </div>
    </div>
</section>
