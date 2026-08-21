<div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-sm">
    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Checklist Schedules</h2>
            <p class="mt-1 text-sm text-slate-600">Start checklist types for this member. Day 1 is the date you enter below — not their GWTrack join date.</p>
        </div>
    </div>

    @if (session('status') === 'checklist-type-started')
        <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            Checklist schedule started successfully.
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-5 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Checklist Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Day 1 / Started By</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($checklistTypePanel as $type)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gw-primary">{{ $type['name'] }}</td>
                        <td class="px-4 py-3">
                            @if ($type['started'])
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Started</span>
                            @elseif ($type['start_requirements_met'])
                                <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800">Ready to start</span>
                            @elseif ($type['requires_license'] ?? false)
                                <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800">License required</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Waiting on prerequisites</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($type['started'])
                                <div>{{ $type['started_at'] }}</div>
                                <div class="text-xs text-slate-500">by {{ $type['started_by'] ?? 'Unknown' }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($canStartChecklists && $type['can_start'])
                                <form method="POST" action="{{ route('team.member.checklist-type.start', [$member, $type['code']]) }}" class="inline-flex flex-col items-end gap-2 sm:flex-row sm:items-center">
                                    @csrf
                                    <input
                                        type="date"
                                        name="started_at"
                                        value="{{ old('started_at', now()->toDateString()) }}"
                                        required
                                        class="rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                    >
                                    <button class="inline-flex items-center justify-center rounded-md bg-gw-accent px-3 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                                        Start
                                    </button>
                                </form>
                            @elseif (! $type['started'] && ($type['requires_license'] ?? false))
                                <span class="text-xs text-slate-500">Add a life license on the profile first</span>
                            @elseif (! $type['started'] && ! $type['prerequisites_met'])
                                <span class="text-xs text-slate-500">Complete prerequisites first</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
