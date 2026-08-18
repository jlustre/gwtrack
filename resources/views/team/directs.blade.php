<x-app-layout>
    <section class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-[#05070B] shadow-sm">
            <div class="bg-gradient-to-br from-[#05070B] via-[#111827] to-[#2A2110] px-6 py-7 text-white">
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">My Team</p>
                <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">My Directs</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                            Manage the members you personally sponsored. Search, filter, and review onboarding progress, CFM coverage, and team growth for each direct recruit.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('team.index') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Team Dashboard</a>
                        <a href="{{ route('team.tree') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Genealogy Tree</a>
                        <a href="{{ route('team.downlines') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">All Downlines</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                @foreach ([
                    ['label' => 'Total Directs', 'value' => $stats['total'], 'theme' => 'navy', 'subtitle' => 'Personally sponsored members'],
                    ['label' => 'Active', 'value' => $stats['active'], 'theme' => 'emerald', 'subtitle' => 'Currently active directs'],
                    ['label' => 'Licensed', 'value' => $stats['licensed'], 'theme' => 'gold', 'subtitle' => 'Licensed producers'],
                    ['label' => 'CFM Assigned', 'value' => $stats['cfm_assigned'], 'theme' => 'cyan', 'subtitle' => 'With mentor coverage'],
                    ['label' => 'New This Month', 'value' => $stats['new_this_month'], 'theme' => 'violet', 'subtitle' => 'Recent recruits'],
                    ['label' => 'Building Teams', 'value' => $stats['with_downline'], 'theme' => 'amber', 'subtitle' => 'Directs with their own recruits'],
                ] as $card)
                    <x-tracker-stat-card
                        :label="$card['label']"
                        :value="$card['value']"
                        :subtitle="$card['subtitle']"
                        :theme="$card['theme']"
                    />
                @endforeach
            </div>
        </div>

        @include('team.directs.partials.filters', ['filters' => $filters])

        @include('team.directs.partials.table', ['members' => $members])
    </section>
</x-app-layout>
