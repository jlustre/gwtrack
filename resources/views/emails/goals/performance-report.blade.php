<x-mail::message>
# {{ $report['period_label'] ?? 'Performance' }} Goal Report

Hello {{ $user->name }},

Here is your {{ strtolower($report['period_label'] ?? 'performance') }} goal summary for **{{ $report['period_start']->format('M j') }} – {{ $report['period_end']->format('M j, Y') }}**.

**Average progress:** {{ $report['average_progress'] ?? 0 }}%  
**Completed:** {{ $report['completed_count'] ?? 0 }}  
**Off track:** {{ $report['off_track_count'] ?? 0 }}

@if (($report['goals'] ?? collect())->isNotEmpty())
<x-mail::table>
| Goal | Progress | Status |
|:-----|:--------:|:-------|
@foreach ($report['goals']->take(10) as $goal)
| {{ $goal->name }} | {{ $goal->progressPercent() }}% | {{ config('goals.statuses.'.$goal->status, $goal->status) }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="route('goals.index')">
View Goals Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
