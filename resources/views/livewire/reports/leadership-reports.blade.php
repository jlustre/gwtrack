<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 bg-slate-50/80 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="leadership-period" class="text-xs font-semibold uppercase text-slate-500">Activity period</label>
                <select id="leadership-period" wire:model.live="period" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="monthly">Month to date</option>
                    <option value="quarterly">Quarter to date</option>
                    <option value="annual">Year to date</option>
                </select>
            </div>
            <div>
                <label for="leadership-team" class="text-xs font-semibold uppercase text-slate-500">Team</label>
                <select id="leadership-team" wire:model.live="teamId" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All visible teams</option>
                    @foreach ($report['filter_options']['teams'] as $team)
                        <option value="{{ $team['id'] }}">{{ $team['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="leadership-rank" class="text-xs font-semibold uppercase text-slate-500">Rank</label>
                <select id="leadership-rank" wire:model.live="rankId" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All visible ranks</option>
                    @foreach ($report['filter_options']['ranks'] as $rank)
                        <option value="{{ $rank['id'] }}">{{ $rank['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="leadership-role" class="text-xs font-semibold uppercase text-slate-500">Role</label>
                <select id="leadership-role" wire:model.live="role" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All visible roles</option>
                    @foreach ($report['filter_options']['roles'] as $roleOption)
                        <option value="{{ $roleOption }}">{{ str($roleOption)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="leadership-status" class="text-xs font-semibold uppercase text-slate-500">Status</label>
                <select id="leadership-status" wire:model.live="status" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        @can('export team data')
            <a href="{{ route('reports.leadership.download', ['period' => $period, 'team_id' => $teamId, 'rank_id' => $rankId, 'role' => $role, 'status' => $status]) }}" class="inline-flex items-center rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">
                Download CSV
            </a>
        @endcan
    </div>

    <div class="border-b border-slate-100 px-4 py-3 text-xs text-slate-500">
        {{ $report['period_label'] }} · {{ $report['period_start']->format('M j, Y') }} – {{ $report['period_end']->format('M j, Y') }}
        · {{ $report['summary']['members'] }} members in the current filtered scope
    </div>

    <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Members in scope', 'value' => $report['summary']['members'], 'theme' => 'navy'],
            ['label' => 'New members', 'value' => $report['summary']['new_members'], 'theme' => 'cyan'],
            ['label' => 'Onboarding progress', 'value' => $report['summary']['onboarding_average'].'%', 'theme' => 'gold'],
            ['label' => 'Licensing progress', 'value' => $report['summary']['licensing_average'].'%', 'theme' => 'emerald'],
            ['label' => 'Apprenticeship progress', 'value' => $report['summary']['apprenticeship_average'].'%', 'theme' => 'violet'],
            ['label' => 'Active apprenticeships', 'value' => $report['summary']['active_apprenticeships'], 'theme' => 'navy'],
            ['label' => 'Completed apprenticeships', 'value' => $report['summary']['completed_apprenticeships'], 'theme' => 'violet'],
            ['label' => 'Mentor assignments this period', 'value' => $report['summary']['new_mentor_assignments'], 'theme' => 'cyan'],
            ['label' => 'Lessons completed', 'value' => $report['summary']['lessons_completed'], 'theme' => 'emerald'],
            ['label' => 'Assessments passed', 'value' => $report['summary']['assessments_passed'], 'theme' => 'gold'],
            ['label' => 'Rank requirement progress', 'value' => $report['summary']['rank_progress_average'].'%', 'theme' => 'violet'],
        ] as $card)
            <x-tracker-stat-card :label="$card['label']" :value="$card['value']" :theme="$card['theme']" />
        @endforeach
    </div>

    <div class="border-t border-slate-100 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Member progress</h2>
                <p class="mt-1 text-xs text-slate-500">Percentages use checklist and rank requirements with recorded member progress. Training and mentor assignments reflect the selected period or current active status.</p>
            </div>
            <span class="text-xs text-slate-500">Showing {{ $report['pagination']['from'] }}–{{ $report['pagination']['to'] }} of {{ $report['pagination']['total'] }}</span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Member</th>
                        <th class="px-3 py-2">Rank / Team</th>
                        <th class="px-3 py-2">Role</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Onboarding</th>
                        <th class="px-3 py-2">Licensing</th>
                        <th class="px-3 py-2">Apprenticeship</th>
                        <th class="px-3 py-2">Active apprentices</th>
                        <th class="px-3 py-2">Lessons</th>
                        <th class="px-3 py-2">Assessments passed</th>
                        <th class="px-3 py-2">Rank progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['member_rows'] as $row)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2 font-medium text-gw-primary">{{ $row['name'] }}</td>
                            <td class="whitespace-nowrap px-3 py-2">{{ $row['rank'] }}<span class="block text-xs text-slate-500">{{ $row['team'] }}</span></td>
                            <td class="px-3 py-2">{{ $row['role'] }}</td>
                            <td class="px-3 py-2">{{ $row['status'] }}</td>
                            <td class="px-3 py-2">{{ $row['onboarding'] }}%</td>
                            <td class="px-3 py-2">{{ $row['licensing'] }}%</td>
                            <td class="px-3 py-2">{{ $row['apprenticeship'] }}%</td>
                            <td class="px-3 py-2">{{ $row['active_apprentices'] }}</td>
                            <td class="px-3 py-2">{{ $row['lessons_completed'] }}</td>
                            <td class="px-3 py-2">{{ $row['assessments_passed'] }}</td>
                            <td class="px-3 py-2">{{ $row['rank_progress'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-8 text-center text-slate-500">No members are currently visible in this reporting scope.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($report['pagination']['has_previous'] || $report['pagination']['has_more'])
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" wire:click="goToPreviousPage" @disabled(! $report['pagination']['has_previous']) class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Previous
                </button>
                <button type="button" wire:click="goToNextPage" @disabled(! $report['pagination']['has_more']) class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Next
                </button>
            </div>
        @endif
    </div>
</section>
