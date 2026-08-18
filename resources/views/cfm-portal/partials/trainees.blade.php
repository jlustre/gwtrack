<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gw-primary">My Trainees</h3>
        <span class="text-xs text-slate-500">{{ $profile['activeApprentices'] }} active · {{ $profile['completedApprentices'] }} completed</span>
    </div>

    @if (count($profile['apprentices']) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="pb-2 font-semibold">Name</th>
                        <th class="pb-2 font-semibold">Rank</th>
                        <th class="pb-2 font-semibold">Checklist</th>
                        <th class="pb-2 font-semibold">Status</th>
                        <th class="pb-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($profile['apprentices'] as $trainee)
                        <tr>
                            <td class="py-2.5 font-medium text-gw-primary">{{ $trainee['name'] }}</td>
                            <td class="py-2.5 text-slate-600">{{ $trainee['rank'] }}</td>
                            <td class="py-2.5">
                                @if (! empty($trainee['assignmentId']))
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-full rounded-full bg-gw-accent" style="width: {{ $trainee['checklistPercent'] ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-500">{{ $trainee['checklistPercent'] ?? 0 }}%</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-2.5">
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                    {{ ucfirst($trainee['status']) }}
                                </span>
                            </td>
                            <td class="space-x-2 py-2.5 text-right">
                                @if (! empty($trainee['id']))
                                    <a
                                        href="{{ route('team.member.profile', $trainee['id']) }}"
                                        class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:text-gw-accent-dark"
                                    >
                                        View profile
                                    </a>
                                @endif
                                @if (! empty($trainee['assignmentId']))
                                    <button
                                        type="button"
                                        class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:text-gw-accent-dark"
                                        @click="openTraineeChecklistModal(@js(route('cfm.portal.trainees.checklist', $trainee['assignmentId'])))"
                                    >
                                        View checklist
                                    </button>
                                    <a
                                        href="{{ route('cfm.portal.trainees.checklist', $trainee['assignmentId']) }}"
                                        class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:text-gw-accent-dark"
                                    >
                                        Track mentoring
                                    </a>
                                @endif
                                @if (! empty($trainee['needsFirstContact']) && ! empty($trainee['assignmentId']))
                                    <form method="POST" action="{{ route('cfm.portal.assignments.first-contact', $trainee['assignmentId']) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary transition hover:bg-gw-accent-hover">
                                            Send first email
                                        </button>
                                    </form>
                                @elseif (! empty($trainee['assignmentId']) && empty($trainee['needsFirstContact']))
                                    <span class="text-xs font-medium text-emerald-700">Intro sent</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-slate-500">No active trainees assigned yet. Confirm pending assignments above to activate trainees.</p>
    @endif

    @if (! empty($profile['apprenticeBreakdown']))
        <dl class="mt-5 grid grid-cols-2 gap-2 border-t border-slate-200 pt-4 text-xs">
            @foreach ($profile['apprenticeBreakdown'] as $key => $value)
                <div class="flex justify-between rounded-lg bg-slate-50 px-2 py-1.5">
                    <span class="text-slate-500">{{ str($key)->headline() }}</span>
                    <span class="font-medium text-gw-primary">{{ $value }}</span>
                </div>
            @endforeach
        </dl>
    @endif
</div>
