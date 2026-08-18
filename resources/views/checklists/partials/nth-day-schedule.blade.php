@if ($step->nth_day)
    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">Day {{ $step->nth_day }}</span>
@endif
@if (! empty($step->expected_due_date))
    @php
        $stepStatus = ($step->is_completed ?? false) ? 'completed' : ($step->status ?? 'not_started');
        $isDueOverdue = \App\Support\ChecklistDueDisplay::isOverdue($step->expected_due_date, $stepStatus);
    @endphp
    <span class="{{ \App\Support\ChecklistDueDisplay::badgeClass($isDueOverdue) }}">Due {{ $step->expected_due_date->format('m/d/Y') }}</span>
@endif
