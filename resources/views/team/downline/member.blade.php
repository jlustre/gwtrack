<x-app-layout>
    <section class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-[#05070B] shadow-sm">
            <div class="bg-gradient-to-br from-[#05070B] via-[#111827] to-[#2A2110] px-6 py-7 text-white">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex items-start gap-4">
                        <x-user-avatar :user="$member" size="lg" class="border-gw-accent bg-gw-accent-muted [&_span]:text-gw-primary" />
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Member Profile</p>
                            <h1 class="mt-2 text-2xl font-semibold">{{ $member->name }}</h1>
                            <p class="mt-2 text-sm text-slate-300">{{ $member->email }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('team.member.tree', $member) }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Genealogy</a>
                        <a href="{{ route('team.member.hierarchy', $member) }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Hierarchy</a>
                        <a href="{{ route('team.member.org-chart', $member) }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Org Branch</a>
                        <a href="{{ route('team.downlines') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Back To All Downlines</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                'Rank' => $member->rank?->code ?? \App\Support\GreatwayRankCatalog::entryCode(),
                'Direct Recruits' => $metrics['direct_recruits'],
                'Total Downline' => $metrics['total_downline'],
                'Prospects' => $metrics['prospects'],
            ] as $label => $value)
                <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                    <div class="mt-3 text-2xl font-semibold text-gw-primary">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        @include('team.downline.partials.checklist-type-starts')

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gw-primary">Progress Summary</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @include('team.downline.partials.progress-line', ['label' => 'Licensing', 'value' => $progress['licensing']])
                    @include('team.downline.partials.progress-line', ['label' => 'Onboarding', 'value' => $progress['onboarding']])
                    @include('team.downline.partials.progress-line', ['label' => 'Training', 'value' => $progress['training']])
                    @include('team.downline.partials.progress-line', ['label' => 'Field Apprenticeship', 'value' => $progress['apprenticeship']])
                    @include('team.downline.partials.progress-line', ['label' => 'Rank Advancement', 'value' => $progress['rank']])
                </div>
            </div>

            <div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Visibility-Safe Details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-500">Sponsor</dt><dd class="text-gw-primary">{{ $member->sponsor?->name ?? 'None' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">CFM / Mentor</dt><dd class="text-gw-primary">{{ $member->mentor?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Team</dt><dd class="text-gw-primary">{{ $member->team?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Location</dt><dd class="text-gw-primary">{{ $member->profile?->city ?? 'City not set' }}, {{ $member->profile?->country ?? 'Global' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Timezone</dt><dd class="text-gw-primary">{{ $member->profile?->timezone ?? 'Not set' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Joined</dt><dd class="text-gw-primary">{{ $member->joined_at?->format('M j, Y') ?? 'Not set' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Last Activity</dt><dd class="text-gw-primary">{{ $member->last_login_at?->diffForHumans() ?? 'No activity yet' }}</dd></div>
                    @if ($canSeeSensitive)
                        <div><dt class="font-bold text-slate-500">Phone</dt><dd class="text-gw-primary">{{ $member->profile?->phone ?? 'Not set' }}</dd></div>
                        <div><dt class="font-bold text-slate-500">License</dt><dd class="text-gw-primary">{{ $member->profile?->license_number ?? 'Not licensed yet' }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>
    </section>
</x-app-layout>
