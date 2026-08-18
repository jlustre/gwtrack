<div>
    <div class="mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gw-primary">AI Relationship Intelligence</h3>
                <p class="mt-1 text-xs text-slate-500">{{ $intelligence['forecast']['rationale'] }}</p>
            </div>
            <div class="flex gap-2">
                <span class="rounded-full bg-gw-accent-muted px-3 py-1 text-xs font-bold text-gw-accent-dark">Lead {{ $intelligence['score']['lead_score'] }}</span>
                @if ($intelligence['score']['recruiting_probability'] > 0)
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700">Recruiting {{ $intelligence['score']['recruiting_probability'] }}%</span>
                @endif
            </div>
        </div>

        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            <div class="rounded-lg bg-slate-50 p-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Next Best Action</p>
                <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $intelligence['next_best_action']['label'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $intelligence['next_best_action']['reason'] }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Meeting Summary</p>
                <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $intelligence['meeting_summary']['summary'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $intelligence['meeting_summary']['completed'] }} completed, {{ $intelligence['meeting_summary']['upcoming'] }} upcoming</p>
            </div>
        </div>

        <details class="mt-3 rounded-lg bg-slate-50 p-3">
            <summary class="cursor-pointer text-xs font-bold uppercase tracking-wide text-slate-500">Draft Assistance</summary>
            <div class="mt-3 space-y-2 text-xs text-slate-600">
                <p><span class="font-semibold text-gw-primary">SMS:</span> {{ $intelligence['drafts']['sms'] }}</p>
                <p><span class="font-semibold text-gw-primary">Email:</span> {{ $intelligence['drafts']['email_subject'] }}</p>
                <p class="whitespace-pre-line">{{ $intelligence['drafts']['email_body'] }}</p>
            </div>
        </details>
    </div>

    @if ($recommendations->isNotEmpty())
        <div class="rounded-lg border border-gw-accent/40 bg-gw-accent-soft p-4 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gw-primary">AI Coach Suggestions</h3>
                <a href="{{ route('team.prospects.ai-coach') }}" class="text-xs font-semibold text-gw-accent-dark hover:text-gw-primary">View all</a>
            </div>
            <ul class="mt-3 space-y-2">
                @foreach ($recommendations as $item)
                    <li class="rounded-lg border border-slate-200 bg-white p-3 text-sm">
                        <p class="font-semibold text-gw-primary">{{ $item['message'] }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="$dispatch('open-log-activity-modal', { prospectId: '{{ $prospect->id }}', activityType: 'phone_call' })"
                                class="rounded border border-gw-accent bg-gw-accent-muted px-2 py-1 text-[10px] font-semibold text-gw-primary"
                            >
                                Log Call
                            </button>
                            <a href="{{ route('team.prospects.appointments') }}" class="rounded border border-slate-200 px-2 py-1 text-[10px] font-semibold text-slate-600">
                                Schedule
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
