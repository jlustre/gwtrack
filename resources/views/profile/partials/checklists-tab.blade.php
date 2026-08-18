@php
    $summaries = $memberTabs['checklistSummaries'] ?? [];
    $isOwnProfile = $isOwnProfile ?? true;
@endphp

<div
    x-data="{
        checklistModal: null,
        summaries: @js($summaries),
        get activeSummary() {
            return this.summaries.find((summary) => summary.code === this.checklistModal) ?? null;
        },
        openChecklist(code) {
            this.checklistModal = code;
        },
        closeChecklist() {
            this.checklistModal = null;
        },
    }"
    x-on:keydown.escape.window="if (checklistModal) closeChecklist()"
>
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gw-primary">Checklists</h2>
        <p class="mt-1 text-sm text-slate-600">
            Progress across every checklist type started for
            @if ($isOwnProfile)
                your
            @else
                {{ $user->name }}&rsquo;s
            @endif
            development journey.
        </p>
    </div>

    @if ($summaries === [])
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
            <p class="text-sm font-semibold text-gw-primary">No checklists started yet</p>
            <p class="mt-1 text-xs text-slate-600">
                Checklist types appear here once onboarding, licensing, FAP, or other trackers have been started.
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($summaries as $summary)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gw-primary">{{ $summary['name'] }}</h3>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] leading-4 text-slate-500">
                                <span class="whitespace-nowrap">{{ $summary['completed'] }} of {{ $summary['total'] }} complete</span>
                                @if ($summary['started_at'])
                                    <span class="whitespace-nowrap tabular-nums">Started {{ $summary['started_at'] }}</span>
                                @endif
                                @if (! empty($summary['due_at']))
                                    <span @class([
                                        'whitespace-nowrap tabular-nums',
                                        \App\Support\ChecklistDueDisplay::textClass((bool) ($summary['is_due_overdue'] ?? false)),
                                    ])>Due {{ $summary['due_at'] }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-gw-primary">{{ $summary['percent'] }}%</span>
                    </div>

                    <div class="mt-3">
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $summary['percent'] }}%"></div>
                        </div>
                    </div>

                    @if (filled($summary['description']))
                        <p class="mt-3 line-clamp-2 text-xs leading-5 text-slate-600">{{ $summary['description'] }}</p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-gw-primary bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-gw-primary-hover"
                            x-on:click="openChecklist(@js($summary['code']))"
                        >
                            View details
                        </button>

                        @if ($isOwnProfile && filled($summary['route']))
                            <a
                                href="{{ route($summary['route']) }}"
                                class="rounded-md border border-gw-accent/50 px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-soft"
                            >
                                Open tracker
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @include('profile.partials.checklist-detail-modal')
</div>
