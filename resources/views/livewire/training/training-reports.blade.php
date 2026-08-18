<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-4">
        <h2 class="text-lg font-semibold text-gw-primary">Academy Analytics</h2>
        <p class="text-sm text-slate-600">Preview metrics, export PDF summaries, or email reports to yourself.</p>
    </div>

    <div class="flex flex-wrap items-end gap-4 border-b border-slate-100 bg-slate-50/80 p-4">
        <div>
            <label for="training-report-period" class="text-xs font-semibold uppercase text-slate-500">Report period</label>
            <select id="training-report-period" wire:model.live="periodType" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="weekly">Last week</option>
                <option value="monthly">Last month</option>
                <option value="quarterly">Last quarter</option>
                <option value="annual">Last year</option>
            </select>
        </div>

        @if (count($availableScopes) > 1)
            <div>
                <label for="training-report-scope" class="text-xs font-semibold uppercase text-slate-500">Audience</label>
                <select id="training-report-scope" wire:model.live="scope" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach ($availableScopes as $scopeOption)
                        <option value="{{ $scopeOption }}">{{ $scopeLabels[$scopeOption] ?? str($scopeOption)->title() }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <a
            href="{{ route('training.reports.download', ['period' => $periodType, 'scope' => $scope]) }}"
            wire:key="training-download-{{ $periodType }}-{{ $scope }}"
            class="inline-flex items-center rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover"
        >
            Download PDF
        </a>

        <form method="POST" action="{{ route('training.reports.email') }}" class="inline" wire:key="training-email-{{ $periodType }}-{{ $scope }}">
            @csrf
            <input type="hidden" name="period" value="{{ $periodType }}">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <button type="submit" class="rounded-lg border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-accent-dark hover:bg-gw-accent-muted">
                Email report
            </button>
        </form>
    </div>

    <div class="border-b border-slate-100 px-4 py-3 text-xs text-slate-500">
        {{ $preview['scope_label'] }} · {{ $preview['period_label'] }}:
        {{ $preview['period_start']->format('M j, Y') }} – {{ $preview['period_end']->format('M j, Y') }}
    </div>

    <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Lessons completed', 'value' => $preview['summary']['lessons_completed'], 'theme' => 'navy'],
            ['label' => 'Courses completed', 'value' => $preview['summary']['courses_completed'], 'theme' => 'emerald'],
            ['label' => 'Assessments passed', 'value' => $preview['summary']['assessments_passed'], 'theme' => 'cyan'],
            ['label' => 'Certifications issued', 'value' => $preview['summary']['certifications_issued'], 'theme' => 'gold'],
            ['label' => 'Training hours', 'value' => $preview['summary']['training_hours'].'h', 'theme' => 'violet'],
            ['label' => 'Avg course progress', 'value' => $preview['summary']['avg_course_progress'].'%', 'theme' => 'slate'],
            ['label' => 'Overdue assignments', 'value' => $preview['summary']['assignments_overdue'], 'theme' => 'red'],
            ['label' => 'Active learners', 'value' => $preview['summary']['active_learners'], 'theme' => 'navy'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid gap-6 border-t border-slate-100 p-4 lg:grid-cols-2">
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Lesson completion trend</h3>
            @php $trendMax = max(1, collect($preview['monthly_trend'])->max('lessons_completed') ?? 1); @endphp
            <div class="mt-4 flex h-36 items-end gap-2">
                @foreach ($preview['monthly_trend'] as $month)
                    <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                        <div class="flex h-28 w-full items-end justify-center">
                            <div class="w-full max-w-8 rounded-t bg-gw-primary" style="height: {{ max(4, ($month['lessons_completed'] / $trendMax) * 100) }}%"></div>
                        </div>
                        <span class="text-[0.65rem] font-semibold text-slate-500">{{ $month['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Top completed courses</h3>
            <div class="mt-4 space-y-2">
                @forelse ($preview['top_courses'] as $course)
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                        <span class="font-medium text-gw-primary">{{ $course['title'] }}</span>
                        <span class="font-semibold text-gw-accent-dark">{{ $course['completions'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">No course completions in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if ($preview['course_rows'] !== [])
        <div class="border-t border-slate-100 p-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">My course progress</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Course</th>
                            <th class="px-3 py-2">Progress</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($preview['course_rows'] as $course)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gw-primary">{{ $course['title'] }}</td>
                                <td class="px-3 py-2">{{ $course['progress_percent'] }}%</td>
                                <td class="px-3 py-2">{{ $course['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($preview['member_rows'] !== [])
        <div class="border-t border-slate-100 p-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Learner activity ({{ $preview['summary']['members_in_scope'] }} members)</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Member</th>
                            <th class="px-3 py-2">Lessons</th>
                            <th class="px-3 py-2">Courses</th>
                            <th class="px-3 py-2">Avg progress</th>
                            <th class="px-3 py-2">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($preview['member_rows'] as $member)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gw-primary">{{ $member['name'] }}</td>
                                <td class="px-3 py-2">{{ $member['lessons_completed'] }}</td>
                                <td class="px-3 py-2">{{ $member['courses_completed'] }}</td>
                                <td class="px-3 py-2">{{ $member['avg_progress'] }}%</td>
                                <td class="px-3 py-2">{{ number_format($member['points']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
