<x-app-layout>
    <section class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
            <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Activity</p>
                    <h1 class="mt-2 text-2xl font-semibold">{{ $prospect->displayName() }}</h1>
                    <p class="mt-2 text-sm text-slate-200">
                        {{ $prospect->stage?->name ?? 'No stage' }}
                        · {{ str($prospect->interest_level)->title() }} interest
                        · {{ str($prospect->status)->title() }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('update', $prospect)
                        <button
                            type="button"
                            onclick="Livewire.dispatch('open-prospect-quick-log-modal', { prospectId: '{{ $prospect->id }}', tab: 'activity', activityType: 'phone_call' })"
                            class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary"
                        >
                            Log Call
                        </button>
                        <button
                            type="button"
                            onclick="Livewire.dispatch('open-prospect-quick-log-modal', { prospectId: '{{ $prospect->id }}' })"
                            class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10"
                        >
                            Log Activity
                        </button>
                    @endcan
                    <a href="{{ route('team.prospects.records.show', $prospect) }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Full Profile</a>
                    <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Back</a>
                </div>
            </div>
        </div>

        @if (session('prospect_quick_log_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('prospect_quick_log_status') }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Phone</p>
                <p class="mt-2 text-sm font-semibold text-gw-primary">
                    @if ($prospect->phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $prospect->phone) }}" class="text-gw-accent-dark hover:underline">{{ $prospect->phone }}</a>
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Email</p>
                <p class="mt-2 text-sm font-semibold text-gw-primary">
                    @if ($prospect->email)
                        <a href="mailto:{{ $prospect->email }}" class="text-gw-accent-dark hover:underline">{{ $prospect->email }}</a>
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Last Contact</p>
                <p class="mt-2 text-sm font-semibold text-gw-primary">{{ $prospect->last_contacted_at?->format('M j, Y g:i A') ?? 'Never' }}</p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Next Follow-Up</p>
                <p class="mt-2 text-sm font-semibold text-gw-primary">{{ $prospect->next_follow_up_at?->format('M j, Y g:i A') ?? 'Not scheduled' }}</p>
            </div>
        </div>

        @include('prospects.partials.mobile-quick-actions', [
            'prospectId' => $prospect->id,
            'phone' => $prospect->phone,
            'email' => $prospect->email,
            'showDesktop' => true,
        ])

        <livewire:prospects.prospect-profile-tabs :prospect="$prospect" initial-tab="timeline" />
    </section>

    <livewire:prospects.prospect-quick-log-modal />
    <livewire:prospects.log-activity-modal />
    <livewire:prospects.log-communication-modal />
</x-app-layout>
