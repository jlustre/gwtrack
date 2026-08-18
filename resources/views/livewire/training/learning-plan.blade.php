<div class="space-y-6">
    @if (session('plan_status') === 'dismissed')
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">Recommendation dismissed.</div>
    @elseif (session('plan_status') === 'enrolled')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Learning path enrollment updated.</div>
    @endif

    <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                <h1 class="mt-2 text-3xl font-semibold">My Learning Plan</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                    Personalized next steps based on your role, learning paths, assignments, and program progress.
                </p>
            </div>
            @if ($plan['audience_path'])
                <div class="rounded-lg border border-gw-accent/40 bg-white/10 px-4 py-3 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Suggested path</p>
                    <p class="mt-1 font-semibold">{{ $plan['audience_path']->name }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Courses completed', 'value' => $plan['stats']['courses_completed'].' / '.$plan['stats']['courses_available'], 'theme' => 'emerald'],
            ['label' => 'Active assignments', 'value' => $plan['stats']['active_assignments'], 'theme' => 'cyan'],
            ['label' => 'Enrolled paths', 'value' => count($plan['enrolled_paths']), 'theme' => 'gold'],
            ['label' => 'Priority actions', 'value' => count($plan['priority_rows']), 'theme' => 'navy'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
            <h2 class="text-lg font-semibold text-gw-primary">Priority recommendations</h2>
            <div class="mt-4 space-y-3">
                @forelse ($plan['priority_rows'] as $row)
                    @php $recommendation = $row['recommendation']; @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <span class="rounded-full bg-gw-accent-soft px-2.5 py-0.5 text-[0.65rem] font-bold uppercase text-gw-accent-dark">{{ $row['label'] }}</span>
                                <p class="mt-2 text-sm font-medium text-gw-primary">{{ $recommendation->message }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($row['action_url'])
                                    <a href="{{ $row['action_url'] }}" class="inline-flex rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-gw-primary-hover">{{ $row['action_label'] }}</a>
                                @endif
                                <button type="button" wire:click="dismiss({{ $recommendation->id }})" class="inline-flex rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white">Dismiss</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">You are caught up. Explore the course catalog to keep growing.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            @if ($plan['audience_path'] && collect($plan['enrolled_paths'])->where('path.id', $plan['audience_path']->id)->isEmpty())
                <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
                    <h3 class="font-semibold text-gw-primary">Start your role-based path</h3>
                    <p class="mt-2 text-sm text-slate-700">{{ $plan['audience_path']->description }}</p>
                    <button type="button" wire:click="enrollPath('{{ $plan['audience_path']->code }}')" class="mt-4 inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                        Enroll in {{ $plan['audience_path']->name }}
                    </button>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Enrolled paths</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($plan['enrolled_paths'] as $pathRow)
                        <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('training.paths.show', $pathRow['path']) }}" class="font-semibold text-gw-primary hover:text-gw-accent">{{ $pathRow['path']->name }}</a>
                                    @if ($pathRow['next_module'])
                                        <p class="mt-1 text-xs text-slate-500">Next: {{ $pathRow['next_module']->title }} ({{ $pathRow['next_module_progress'] }}%)</p>
                                    @endif
                                </div>
                                <span class="rounded-full bg-gw-primary px-2 py-0.5 text-[0.65rem] font-bold text-gw-accent">{{ $pathRow['progress_percent'] }}%</span>
                            </div>
                            <div class="mt-2 h-1.5 rounded-full bg-slate-200">
                                <div class="h-1.5 rounded-full bg-gw-accent" style="width: {{ $pathRow['progress_percent'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-600">Enroll in a learning path to get a structured development plan.</p>
                        <a href="{{ route('training.paths.index') }}" class="mt-2 inline-flex text-sm font-semibold text-gw-primary underline">Browse learning paths</a>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if (count($plan['all_rows']) > count($plan['priority_rows']))
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">More suggestions</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach (array_slice($plan['all_rows'], count($plan['priority_rows'])) as $row)
                    @php $recommendation = $row['recommendation']; @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $row['label'] }}</p>
                        <p class="mt-1 text-sm text-gw-primary">{{ $recommendation->message }}</p>
                        @if ($row['action_url'])
                            <a href="{{ $row['action_url'] }}" class="mt-2 inline-flex text-xs font-semibold text-gw-accent underline">{{ $row['action_label'] }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
