@php
    $sourceMax = max(1, (int) $sourcePerformance->max('prospect_count'));
@endphp

<div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gw-primary">Hot prospects</h2>
            <a href="{{ route('team.prospects', ['prospect_interest' => 'hot']) }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">View all</a>
        </div>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($hotProspects as $prospect)
                <li class="flex items-center justify-between gap-2 py-2.5">
                    <a href="{{ route('team.prospects.records.show', $prospect) }}" class="font-medium text-gw-primary hover:text-gw-accent-dark">{{ $prospect->first_name }} {{ $prospect->last_name }}</a>
                    <span class="text-xs text-slate-500">{{ $prospect->stage?->name ?? 'No stage' }}</span>
                </li>
            @empty
                <li class="py-4 text-sm text-slate-500">No hot prospects right now.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gw-primary">Upcoming appointments</h2>
            <a href="{{ route('team.prospects.appointments') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">Calendar</a>
        </div>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($upcomingAppointments as $appointment)
                <li class="py-2.5">
                    @if ($appointment->prospect_id ?? null)
                        <a href="{{ route('team.prospects.records.show', $appointment->prospect_id) }}" class="font-medium text-gw-primary hover:text-gw-accent-dark">{{ trim($appointment->first_name.' '.$appointment->last_name) }}</a>
                    @else
                        <p class="font-medium text-gw-primary">{{ trim($appointment->first_name.' '.$appointment->last_name) }}</p>
                    @endif
                    <p class="mt-0.5 text-sm text-slate-600">{{ \Illuminate\Support\Carbon::parse($appointment->scheduled_at)->format('M j, g:i A') }}</p>
                </li>
            @empty
                <li class="py-4 text-sm text-slate-500">No upcoming appointments.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gw-primary">Source performance</h2>
            <a href="{{ route('team.prospects.analytics') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">Analytics</a>
        </div>
        <div class="mt-4 space-y-3">
            @forelse ($sourcePerformance as $source)
                <div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gw-primary">{{ $source->name }}</span>
                        <span class="font-semibold text-slate-600">{{ $source->prospect_count }}</span>
                    </div>
                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(4, ((int) $source->prospect_count / $sourceMax) * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Source data will appear as you add prospects.</p>
            @endforelse
        </div>
        <p class="mt-4 text-xs text-slate-500">{{ $stats['converted'] }} converted · {{ $stats['conversion_rate'] }}% conversion rate</p>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gw-primary">Communication Timeline</h2>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($recentCommunications as $communication)
                <li class="py-2.5">
                    @if ($communication->prospect_id ?? null)
                        <a href="{{ route('team.prospects.records.show', $communication->prospect_id) }}" class="font-medium text-gw-primary hover:text-gw-accent-dark">{{ trim($communication->first_name.' '.$communication->last_name) }}</a>
                    @else
                        <p class="font-medium text-gw-primary">{{ trim($communication->first_name.' '.$communication->last_name) }}</p>
                    @endif
                    <p class="mt-0.5 text-sm text-slate-600">{{ $communication->communication_type ?? 'Contact' }} · {{ $communication->outcome ?? 'Logged' }}</p>
                </li>
            @empty
                <li class="py-4 text-sm text-slate-500">No communications logged yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gw-primary">Shared with me</h2>
            <a href="{{ route('team.prospects.screen', 'prospects') }}#shared-with-me" class="text-sm font-semibold text-gw-accent-dark hover:underline">View all</a>
        </div>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($sharedWithMe as $share)
                <li class="py-2.5">
                    @if ($share->prospect_id ?? null)
                        <a href="{{ route('team.prospects.records.show', $share->prospect_id) }}" class="font-medium text-gw-primary hover:text-gw-accent-dark">{{ trim($share->first_name.' '.$share->last_name) }}</a>
                    @else
                        <p class="font-medium text-gw-primary">{{ trim($share->first_name.' '.$share->last_name) }}</p>
                    @endif
                    <p class="mt-0.5 text-sm text-slate-600">Owner: {{ $share->owner_name }}</p>
                </li>
            @empty
                <li class="py-4 text-sm text-slate-500">No shared prospects.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gw-primary">Import & Duplicates</h2>
            <a href="{{ route('team.prospects.import') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">Import</a>
        </div>
        @if ($recentImport)
            <p class="mt-3 text-sm font-semibold text-gw-primary">{{ $recentImport->file_name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $recentImport->imported_rows }} imported · {{ $recentImport->duplicate_rows }} duplicates</p>
            <p class="mt-1 text-xs text-slate-500">{{ str($recentImport->status)->title() }} · {{ \Illuminate\Support\Carbon::parse($recentImport->completed_at ?? $recentImport->updated_at)->diffForHumans() }}</p>
        @else
            <p class="mt-4 text-sm text-slate-500">No imports yet. Upload a CSV to bulk-add prospects.</p>
        @endif
    </section>
</div>
