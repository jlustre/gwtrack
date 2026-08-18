<x-app-layout>
    @php
        $titles = [
            'create' => 'Add Prospect',
            'prospects' => 'Prospects',
            'activity-logs' => 'Activity Logs',
            'pipeline' => 'Sales Pipeline',
            'follow-ups' => 'Follow-Up Center',
            'appointments' => 'Appointment Calendar',
            'access-manager' => 'Access Manager',
            'import' => 'Prospect Import',
            'settings' => 'Prospect Settings',
        ];

        $descriptions = [
            'create' => 'Capture a personal prospect with source, stage, priority, interests, and contact details.',
            'prospects' => 'Browse active and archived prospect relationships with contact, interest, stage, and follow-up context.',
            'activity-logs' => 'Review calls, messages, meetings, presentations, outcomes, and next actions across your CRM relationships.',
            'pipeline' => 'Review your prospects by pipeline stage and activity status.',
            'follow-ups' => 'Track due dates, priority, status, and next actions for prospect follow-ups.',
            'appointments' => 'Manage scheduled calls, meetings, Zoom links, and helper assignments.',
            'access-manager' => 'Audit active sharing permissions, expiration dates, and collaborator access.',
            'import' => 'Stage CSV imports, duplicate checks, skipped rows, and completed batches.',
            'settings' => 'Review lookup values used by prospect screens.',
        ];
    @endphp

    <section class="space-y-6" x-data="{ showCreateProspect: false, showQuickAddProspect: false }">
        @if (session('prospect_quick_add_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('prospect_quick_add_status') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
            <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                    <h1 class="mt-2 text-2xl font-semibold">{{ $titles[$screenKey] ?? 'Prospect Module' }}</h1>
                    <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-200">{{ $descriptions[$screenKey] ?? 'Prospect module scaffold.' }}</p>
                </div>
                <a href="{{ route('team.prospects') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                    Back to Overview
                </a>
            </div>
        </div>

        @if ($screenKey === 'create')
            {{-- Create route redirects to team.prospects.create --}}
        @elseif ($screenKey === 'prospects')
            @include('team.prospects.partials.prospect-directory', ['directoryRoute' => route('team.prospects.screen', ['prospects'])])
            <livewire:prospects.shared-with-me-prospects :embedded="true" />
            <livewire:prospects.shared-by-me-prospects :embedded="true" />
        @elseif ($screenKey === 'activity-logs')
            @include('team.partials.prospect-activity-table', ['rows' => $activities, 'title' => 'CRM Activity Logs'])
        @elseif ($screenKey === 'pipeline')
            @include('team.partials.prospect-prospect-table', ['rows' => $prospects, 'title' => 'Pipeline Prospect Table'])
        @elseif ($screenKey === 'follow-ups')
            @include('team.partials.prospect-followup-table', ['rows' => $followUps, 'title' => 'Follow-Up Table'])
        @elseif ($screenKey === 'appointments')
            @include('team.partials.prospect-appointment-table', ['rows' => $appointments, 'title' => 'Appointment Table'])
        @elseif ($screenKey === 'import')
            @include('team.partials.prospect-import-table', ['rows' => $imports, 'title' => 'Import Batch Table'])
        @elseif ($screenKey === 'settings')
            <div class="grid gap-6 xl:grid-cols-2">
                @include('team.partials.prospect-lookup-table', ['rows' => $sources, 'title' => 'Sources'])
                @include('team.partials.prospect-lookup-table', ['rows' => $pipelineStages, 'title' => 'Pipeline Stages'])
                @include('team.partials.prospect-lookup-table', ['rows' => $types, 'title' => 'Types'])
                @include('team.partials.prospect-lookup-table', ['rows' => $interests, 'title' => 'Interests'])
            </div>
        @endif
    </section>
</x-app-layout>
