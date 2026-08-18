<div class="space-y-6">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-emerald-50/40 shadow-sm">
        <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-400">Team Building</p>
                    <h1 class="mt-2 text-2xl font-semibold">Recruiting Pipeline</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        A dedicated workspace for associate recruiting — candidate pipeline, registration invitations, and your direct recruits' onboarding journey. Separate from insurance sales CRM.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $pipeline['urls']['add_candidate'] }}" class="rounded-lg border border-emerald-400 bg-emerald-400 px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-emerald-300">
                        + Add recruit candidate
                    </a>
                    <a href="{{ $pipeline['urls']['goals'] }}" class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
                        Recruiting goals
                    </a>
                    <a href="{{ $pipeline['urls']['sales_crm'] }}" class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
                        Sales CRM
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Active candidates', 'value' => $pipeline['stats']['active_candidates'], 'theme' => 'navy'],
                ['label' => 'Hot candidates', 'value' => $pipeline['stats']['hot_candidates'], 'theme' => 'gold'],
                ['label' => 'Follow-ups due', 'value' => $pipeline['stats']['followups_due'], 'theme' => 'amber'],
                ['label' => 'Pending invitations', 'value' => $pipeline['stats']['pending_invitations'], 'theme' => 'cyan'],
                ['label' => 'Registered this month', 'value' => $pipeline['stats']['registered_this_month'], 'theme' => 'emerald'],
                ['label' => 'Direct recruits', 'value' => $pipeline['stats']['direct_recruits'], 'theme' => 'violet'],
                ['label' => 'Presentations scheduled', 'value' => $pipeline['stats']['presentations_scheduled'], 'theme' => 'slate'],
                ['label' => 'Conversion rate', 'value' => $pipeline['stats']['conversion_rate'].'%', 'theme' => 'gold'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    @if (count($pipeline['hot_candidates']) > 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-amber-900">Hot candidates</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($pipeline['hot_candidates'] as $candidate)
                    <a href="{{ $candidate['profile_url'] }}" class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-semibold text-gw-primary hover:bg-amber-100">
                        {{ $candidate['name'] }}
                        <span class="ml-1 text-xs font-normal text-slate-500">· {{ $candidate['stage'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Recruiting kanban</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $pipeline['funnel']['description'] ?? 'Drag candidates through your recruiting funnel.' }}</p>
            </div>
            <a href="{{ $pipeline['urls']['add_candidate'] }}" class="text-sm font-semibold text-emerald-700 hover:underline">Add candidate</a>
        </div>
        <livewire:recruiting.recruiting-funnel-board />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Candidate queue</h2>
            <p class="mt-1 text-sm text-slate-500">Recruiting prospects still in your pipeline.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2">Candidate</th>
                            <th class="px-3 py-2">Pipeline</th>
                            <th class="px-3 py-2">Stage</th>
                            <th class="px-3 py-2">Interest</th>
                            <th class="px-3 py-2">Next follow-up</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pipeline['candidates'] as $candidate)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3 font-semibold">
                                    <a href="{{ $candidate['profile_url'] }}" class="text-gw-primary hover:underline">{{ $candidate['name'] }}</a>
                                </td>
                                <td class="px-3 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                        'bg-emerald-100 text-emerald-800' => ($candidate['funnel_type'] ?? 'recruiting') === 'recruiting',
                                        'bg-violet-100 text-violet-800' => ($candidate['funnel_type'] ?? '') === 'both',
                                    ])>{{ $candidate['pipeline'] ?? 'Recruiting' }}</span>
                                </td>
                                <td class="px-3 py-3 text-slate-600">{{ $candidate['stage'] }}</td>
                                <td class="px-3 py-3 capitalize text-slate-600">{{ $candidate['interest_level'] }}</td>
                                <td class="px-3 py-3 {{ $candidate['is_overdue'] ? 'font-semibold text-red-700' : 'text-slate-600' }}">
                                    {{ $candidate['next_follow_up_at'] ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-slate-500">No active recruiting candidates. Add your first recruit candidate to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Pending registration invitations</h2>
            <div class="mt-4 space-y-3">
                @forelse ($pipeline['pending_invitations'] as $invitation)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="font-semibold text-gw-primary">
                            @if ($invitation['prospect_url'])
                                <a href="{{ $invitation['prospect_url'] }}" class="hover:underline">{{ $invitation['prospect_name'] }}</a>
                            @else
                                {{ $invitation['prospect_name'] }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ $invitation['email'] ?? 'No email' }} · Sent {{ $invitation['created_at'] }} · Expires {{ $invitation['expires_at'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No open registration invitations.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Active recruit journey</h2>
                <p class="mt-1 text-sm text-slate-500">Direct recruits progressing through onboarding, licensing, and field apprenticeship.</p>
            </div>
            <a href="{{ $pipeline['urls']['directs'] }}" class="text-sm font-semibold text-emerald-700 hover:underline">View all directs</a>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Recruit</th>
                        <th class="px-3 py-2">Rank</th>
                        <th class="px-3 py-2">Joined</th>
                        <th class="px-3 py-2">Journey stage</th>
                        <th class="px-3 py-2 text-right">Onboarding</th>
                        <th class="px-3 py-2 text-right">Licensing</th>
                        <th class="px-3 py-2 text-right">FAP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pipeline['active_recruits'] as $recruit)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-3 font-semibold">
                                <a href="{{ $recruit['profile_url'] }}" class="text-gw-primary hover:underline">{{ $recruit['name'] }}</a>
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $recruit['rank'] ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $recruit['joined_at'] }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ $recruit['journey_stage_label'] }}</span>
                            </td>
                            <td class="px-3 py-3 text-right text-slate-600">{{ $recruit['onboarding_pct'] }}%</td>
                            <td class="px-3 py-3 text-right text-slate-600">{{ $recruit['licensing_pct'] }}%</td>
                            <td class="px-3 py-3 text-right text-slate-600">{{ $recruit['fap_pct'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">No direct recruits yet. Convert recruiting candidates to grow your team.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gw-primary">Funnel distribution</h2>
        <div class="mt-4 space-y-3">
            @php
                $maxCount = max($pipeline['funnel']['max_count'] ?? 1, 1);
            @endphp
            @forelse ($pipeline['funnel']['stages'] ?? [] as $stage)
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-medium text-gw-primary">{{ $stage['name'] }}</span>
                        <span class="text-slate-600">{{ $stage['count'] }} candidates</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400"
                            style="width: {{ $maxCount > 0 ? round(($stage['count'] / $maxCount) * 100) : 0 }}%"
                        ></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No funnel data yet.</p>
            @endforelse
        </div>
    </div>
</div>
