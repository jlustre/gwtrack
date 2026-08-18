@if (! empty($profile['pendingAssignmentRows']))
    <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gw-primary">Pending Assignments</h3>
                <p class="mt-1 text-xs text-slate-600">Confirm new trainees assigned by agency leadership.</p>
            </div>
            <span class="rounded-full bg-gw-accent/20 px-3 py-1 text-xs font-semibold text-gw-accent-dark">{{ count($profile['pendingAssignmentRows']) }} awaiting</span>
        </div>

        <div class="space-y-3">
            @foreach ($profile['pendingAssignmentRows'] as $row)
                <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-gw-primary">{{ $row['name'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $row['rank'] }} · Assigned by {{ $row['assignedBy'] }} · {{ $row['startedAt'] }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <a
                            href="{{ route('cfm.portal.assignments.review', $row['id']) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Review
                        </a>

                        <form method="POST" action="{{ route('cfm.portal.assignments.confirm', $row['id']) }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary transition hover:bg-gw-accent-hover sm:w-auto">
                                Confirm Assignment
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
