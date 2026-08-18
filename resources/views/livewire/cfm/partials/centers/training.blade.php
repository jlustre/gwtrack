@php($center = $sectionCenter)

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Training Academy</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $center['member_profile_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">View profile</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="space-y-2 lg:col-span-2">
            <x-tracker-stat-card
                label="Lesson completion"
                :value="($center['stats']['lesson_completion_percent'] ?? 0).'%'"
                theme="gold"
            />
            <div class="h-2 w-full rounded-full bg-slate-200">
                <div class="h-2 rounded-full bg-gw-accent" style="width: {{ min(100, (int) ($center['stats']['lesson_completion_percent'] ?? 0)) }}%"></div>
            </div>
        </div>
        @foreach (collect($center['stats']['cards'] ?? [])->take(2) as $index => $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$index === 0 ? 'emerald' : 'cyan'"
            />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Assigned training</h3>
            </div>
            @if (count($center['assignments']) === 0)
                <p class="p-5 text-sm text-slate-500">No active training assignments.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['assignments'] as $assignment)
                        <li wire:key="assignment-{{ $assignment['id'] }}" class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gw-primary">{{ $assignment['title'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $assignment['category'] }} · Assigned by {{ $assignment['assigned_by'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase',
                                        'bg-emerald-100 text-emerald-800' => $assignment['status'] === 'completed',
                                        'bg-red-100 text-red-800' => $assignment['is_overdue'],
                                        'bg-sky-100 text-sky-800' => ! $assignment['is_overdue'] && $assignment['status'] !== 'completed',
                                    ])>{{ $assignment['is_overdue'] ? 'Overdue' : ucfirst(str_replace('_', ' ', $assignment['status'])) }}</span>
                                    <p class="mt-2 text-xs text-slate-500">Due {{ $assignment['due_at'] }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Course progress</h3>
            </div>
            @if (count($center['modules']) === 0)
                <p class="p-5 text-sm text-slate-500">No published courses with progress yet.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['modules'] as $module)
                        <li wire:key="module-{{ $loop->index }}" class="px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gw-primary">{{ $module['title'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $module['category'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gw-accent-dark">{{ $module['progress'] }}%</p>
                                    <p class="text-xs text-slate-500">{{ $module['completed'] }}/{{ $module['total'] }} lessons</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Recent activity</h3>
            </div>
            @if (count($center['recent_progress']) === 0)
                <p class="p-5 text-sm text-slate-500">No recent lesson activity recorded.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['recent_progress'] as $row)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <p class="font-medium text-gw-primary">{{ $row['lesson'] }}</p>
                                <p class="text-xs text-slate-500">{{ $row['module'] }}</p>
                            </div>
                            <span class="text-xs text-slate-500">{{ $row['updated_at'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Certifications</h3>
            </div>
            @if (count($center['certifications']) === 0)
                <p class="p-5 text-sm text-slate-500">No certifications issued yet.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['certifications'] as $cert)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <span class="font-medium text-gw-primary">{{ $cert['name'] }}</span>
                            <span class="text-xs text-slate-500">{{ $cert['issued_at'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
