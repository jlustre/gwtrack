@php
    $profile = $portal['profile'];
    $training = $portal['training'];
@endphp

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gw-primary">Profile</h3>
            @if ($portal['canEditProfile'])
                <button
                    type="button"
                    @click="showEditProfileModal = true"
                    class="inline-flex items-center rounded-lg border border-gw-accent/40 px-3 py-1.5 text-xs font-semibold text-gw-accent-dark transition hover:bg-gw-accent-soft"
                >
                    Edit
                </button>
            @endif
        </div>

        <div class="mb-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div><span class="text-slate-500">Email:</span> <span class="text-gw-primary">{{ $profile['email'] }}</span></div>
            <div><span class="text-slate-500">Phone:</span> <span class="text-gw-primary">{{ $profile['phone'] }}</span></div>
            <div><span class="text-slate-500">Location:</span> <span class="text-gw-primary">{{ $profile['city'] }}, {{ $profile['province'] }}, {{ $profile['country'] }}</span></div>
            <div><span class="text-slate-500">Timezone:</span> <span class="text-gw-primary">{{ $profile['timezone'] }}</span></div>
            <div><span class="text-slate-500">Agency Owner:</span> <span class="text-gw-primary">{{ $profile['agencyOwner'] }}</span></div>
            <div><span class="text-slate-500">Last Activity:</span> <span class="text-gw-primary">{{ $profile['lastActivity'] }}</span></div>
            <div class="sm:col-span-2"><span class="text-slate-500">Languages:</span> <span class="text-gw-primary">{{ implode(', ', $profile['languages'] ?: ['—']) }}</span></div>
            <div class="sm:col-span-2"><span class="text-slate-500">Specialties:</span> <span class="text-gw-primary">{{ implode(', ', $profile['specialties'] ?: ['—']) }}</span></div>
            <div class="sm:col-span-2">
                <span class="text-slate-500">Licensed jurisdictions:</span>
                <span class="text-gw-primary">{{ $profile['licensedJurisdictionsLabel'] ?? '—' }}</span>
            </div>
        </div>

        @if ($profile['bio'])
            <p class="border-t border-slate-200 pt-4 text-sm leading-relaxed text-slate-600">{{ $profile['bio'] }}</p>
        @endif
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gw-primary">AI Coaching Assistant</h3>
            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase text-emerald-800">Live</span>
        </div>
        <ul class="space-y-3">
            @foreach ($aiPriorities as $priority)
                <li class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                    @if ($priority['trainee_id'])
                        <button type="button" wire:click="selectTrainee({{ $priority['trainee_id'] }}); setSection('assistant')" class="text-left font-semibold text-gw-primary hover:text-gw-accent-dark">{{ $priority['message'] }}</button>
                    @else
                        {{ $priority['message'] }}
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    @include('cfm-portal.partials.training')

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gw-primary">Quick Actions</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <a href="{{ route('cfm-training.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft">Open CFM Training</a>
            <a href="{{ route('calendar.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft">My Calendar</a>
            <a href="{{ route('bookings.dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft">Mentor Scheduling</a>
            <a href="{{ route('goals.coaching') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-soft">Goals Coaching</a>
        </div>
    </div>
</div>

@include('cfm-portal.partials.rank-structure')

@if (count($profile['apprentices'] ?? []) > 0)
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gw-primary">Trainee Mentoring Actions</h3>
            <span class="text-xs text-slate-500">{{ count($profile['apprentices']) }} active trainee(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="pb-2 font-semibold">Name</th>
                        <th class="pb-2 font-semibold">Checklist</th>
                        <th class="pb-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($profile['apprentices'] as $trainee)
                        <tr wire:key="mentoring-row-{{ $trainee['id'] }}">
                            <td class="py-2.5 font-medium text-gw-primary">{{ $trainee['name'] }}</td>
                            <td class="py-2.5 text-xs text-slate-500">{{ $trainee['checklistPercent'] ?? 0 }}% complete</td>
                            <td class="space-x-2 py-2.5 text-right">
                                @if (! empty($trainee['assignmentId']))
                                    <button
                                        type="button"
                                        class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:text-gw-accent-dark"
                                        @click="openTraineeChecklistModal(@js(route('cfm.portal.trainees.checklist', $trainee['assignmentId'])))"
                                    >
                                        View checklist
                                    </button>
                                    <a
                                        href="{{ route('cfm.portal.trainees.checklist', $trainee['assignmentId']) }}"
                                        class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:text-gw-accent-dark"
                                    >
                                        Track mentoring
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    @include('cfm-portal.partials.calendar')
    @include('cfm-portal.partials.activity')
</div>
