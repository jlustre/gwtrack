<x-app-layout>
    <section class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
            <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Profile</p>
                    <h1 class="mt-2 text-2xl font-semibold">{{ $prospect->displayName() }}</h1>
                    <p class="mt-2 text-sm text-slate-200">
                        @if ($prospect->fullName() && $prospect->fullName() !== $prospect->displayName())
                            Legal name: {{ $prospect->fullName() }} ·
                        @endif
                        {{ $prospect->stage?->name ?? 'No stage' }}
                        · {{ str($prospect->interest_level)->title() }} interest
                        · {{ str($prospect->status)->title() }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('convert', $prospect)
                        <button type="button" onclick="Livewire.dispatch('open-prospect-convert-modal', { prospectId: '{{ $prospect->id }}' })" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Convert</button>
                    @endcan
                    @can('share', $prospect)
                        <button type="button" onclick="Livewire.dispatch('open-prospect-share-modal', { prospectId: '{{ $prospect->id }}' })" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Share</button>
                    @endcan
                    @can('requestFnaClientPortal', $prospect)
                        @can('create', \App\Models\FnaClientInvite::class)
                            <button type="button" onclick="Livewire.dispatch('open-fna-client-invite-modal', { prospectId: '{{ $prospect->id }}' })" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Send FNA Link</button>
                        @else
                            <button type="button" onclick="Livewire.dispatch('open-fna-client-invite-modal', { prospectId: '{{ $prospect->id }}' })" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">FNA via CFM</button>
                        @endcan
                    @endcan
                    <a href="{{ route('team.prospects.records.edit', $prospect) }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Edit</a>
                    <a href="{{ route('team.prospects.records.activity', $prospect) }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Activity</a>
                    <a href="{{ route('team.prospects.screen', 'prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Back To Listing</a>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Funnel</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">{{ $prospect->funnel?->name ?? str($prospect->funnel_type ?? 'insurance')->title() }}</p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Stage</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">{{ $prospect->stage?->name ?? 'Not set' }}</p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Interest</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">{{ str($prospect->interest_level)->title() }}@if ($prospect->interest_score) ({{ $prospect->interest_score }}/10)@endif</p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Source</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">{{ $prospect->source?->name ?? 'Not set' }}</p>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <a href="{{ route('team.prospects.appointments') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-sm transition hover:border-gw-accent/60">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Next Meeting</p>
                <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $relationshipStats['next_calendar_label'] }}</p>
                @if ($relationshipStats['next_calendar_title'])
                    <p class="mt-1 truncate text-xs text-slate-500">{{ $relationshipStats['next_calendar_title'] }}</p>
                @endif
            </a>
            <a href="{{ route('team.prospects.follow-ups') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-sm transition hover:border-gw-accent/60">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Next Follow-Up</p>
                <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $relationshipStats['next_follow_up_label'] }}</p>
                @if ($relationshipStats['next_follow_up_type'])
                    <p class="mt-1 truncate text-xs text-slate-500">{{ str($relationshipStats['next_follow_up_type'])->replace('_', ' ')->headline() }}</p>
                @endif
            </a>
            <a href="{{ route('tasks.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-sm transition hover:border-gw-accent/60">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Open Tasks</p>
                <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $relationshipStats['open_tasks'] }}</p>
            </a>
            <div class="rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">FNA Status</p>
                <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $relationshipStats['fna_status'] ? str($relationshipStats['fna_status'])->headline() : 'No FNA yet' }}</p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Files</p>
                <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $relationshipStats['files_count'] }}</p>
            </div>
        </div>

        @can('share', $prospect)
            <livewire:prospects.prospect-share-modal />
        @endcan

        @can('convert', $prospect)
            <livewire:prospects.prospect-convert-modal />
        @endcan

        @can('requestFnaClientPortal', $prospect)
            <livewire:fna.fna-client-invite-modal />
        @endcan

        @if ($prospect->conversions->isNotEmpty())
            <div class="rounded-lg border border-slate-300 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Conversion History</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">By</th>
                                <th class="px-3 py-2">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prospect->conversions->sortByDesc('converted_at') as $conversion)
                                <tr class="border-b border-slate-100">
                                    <td class="px-3 py-3 font-semibold text-gw-primary">{{ config('prospects.conversion_types.'.$conversion->conversion_type, str($conversion->conversion_type)->title()) }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $conversion->converted_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $conversion->convertedBy?->name ?? '—' }}</td>
                                    <td class="px-3 py-3 text-slate-600">
                                        @if ($conversion->policy_reference)
                                            Policy: {{ $conversion->policy_reference }}
                                        @endif
                                        @if ($conversion->application_reference)
                                            @if ($conversion->policy_reference)<br>@endif
                                            Application: {{ $conversion->application_reference }}
                                        @endif
                                        @if ($conversion->createdUser)
                                            @if ($conversion->policy_reference || $conversion->application_reference)<br>@endif
                                            Member:
                                            @can('view own team')
                                                <a href="{{ route('team.member.profile', $conversion->createdUser) }}" class="font-semibold text-gw-primary hover:underline">{{ $conversion->createdUser->name }}</a>
                                            @else
                                                {{ $conversion->createdUser->name }}
                                            @endcan
                                        @endif
                                        @if ($conversion->notes)
                                            @if ($conversion->policy_reference || $conversion->application_reference || $conversion->createdUser)<br>@endif
                                            {{ $conversion->notes }}
                                        @endif
                                        @if (! $conversion->policy_reference && ! $conversion->application_reference && ! $conversion->createdUser && ! $conversion->notes)
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
            @include('prospects.partials.contact-panel', ['prospect' => $prospect])

            <div class="lg:col-span-2">
                <livewire:prospects.prospect-profile-tabs :prospect="$prospect" />
            </div>
        </div>

        <livewire:prospects.prospect-ai-coach-panel :prospect="$prospect" />
    </section>

    <livewire:prospects.log-activity-modal />
    <livewire:prospects.log-communication-modal />
    <livewire:prospects.prospect-quick-log-modal />
</x-app-layout>
