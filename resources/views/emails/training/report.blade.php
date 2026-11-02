<x-mail::message>
# {{ $report['period_label'] }} Training Report

**Scope:** {{ $report['scope_label'] }}  
**Period:** {{ $report['period_start']->format('M j, Y') }} – {{ $report['period_end']->format('M j, Y') }}

## Summary

| Metric | Value |
| :-- | --: |
| Lessons completed | {{ $report['summary']['lessons_completed'] }} |
| Courses completed | {{ $report['summary']['courses_completed'] }} |
| Assessments passed | {{ $report['summary']['assessments_passed'] }} |
| Certifications issued | {{ $report['summary']['certifications_issued'] }} |
| Training hours | {{ $report['summary']['training_hours'] }}h |
| Avg course progress | {{ $report['summary']['avg_course_progress'] }}% |
| Overdue assignments | {{ $report['summary']['assignments_overdue'] }} |

Open the full interactive report in GWTracks Academy for charts and learner breakdowns.

<x-mail::button :url="route('training.reports.index')">
View Training Reports
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Academy
</x-mail::message>
