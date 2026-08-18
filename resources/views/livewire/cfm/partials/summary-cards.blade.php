<div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total Trainees', 'value' => $summary['total_trainees'], 'theme' => 'navy', 'subtitle' => 'On your roster'],
            ['label' => 'Active Trainees', 'value' => $summary['active_trainees'], 'theme' => 'cyan', 'subtitle' => 'Currently mentoring'],
            ['label' => 'FAP In Progress', 'value' => $summary['fap_in_progress'], 'theme' => 'gold', 'subtitle' => 'Field apprenticeship'],
            ['label' => 'Licensing In Progress', 'value' => $summary['licensing_in_progress'], 'theme' => 'gold', 'subtitle' => 'Licensing checklist'],
            ['label' => 'New (30 Days)', 'value' => $summary['new_associates_30d'], 'theme' => 'violet', 'subtitle' => 'Recent recruits'],
            ['label' => 'At Risk', 'value' => $summary['at_risk_trainees'], 'theme' => 'red', 'subtitle' => 'Needs coaching attention'],
            ['label' => 'Overdue Tasks', 'value' => $summary['overdue_tasks'], 'theme' => 'red', 'subtitle' => 'Past due items'],
            ['label' => 'Upcoming Meetings', 'value' => $summary['upcoming_meetings'], 'theme' => 'cyan', 'subtitle' => 'Scheduled sessions'],
            ['label' => 'FAP Graduates', 'value' => $summary['fap_graduates'], 'theme' => 'emerald', 'subtitle' => 'Completed apprenticeship'],
            ['label' => 'Promotion Ready', 'value' => $summary['promotion_ready'], 'theme' => 'emerald', 'subtitle' => 'Ready for next rank'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :subtitle="$card['subtitle']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid gap-3 border-t border-slate-200/80 p-4 sm:grid-cols-3">
        <x-tracker-stat-card
            label="Capacity"
            :value="$summary['capacity']['active'].'/'.$summary['capacity']['max'].' active slots'"
            theme="navy"
            :subtitle="$summary['pending_approvals'].' pending approval'"
        />
        <x-tracker-stat-card
            label="FAP Completion Rate"
            :value="$summary['fap_completion_rate'].'%'"
            theme="emerald"
            subtitle="Team apprenticeship success"
        />
        <x-tracker-stat-card
            label="Next Session"
            :value="$summary['next_slot']"
            theme="violet"
            :subtitle="'Score '.$summary['recommendation_score'].'/100'"
        />
    </div>
</div>
