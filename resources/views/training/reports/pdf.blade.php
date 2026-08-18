<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $period_label }} Training Report — {{ $viewer->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2D3E50; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; border-bottom: 1px solid #84BB41; padding-bottom: 3px; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e2e8f0; padding: 5px 6px; text-align: left; }
        th { background: #f8fafc; font-size: 9px; text-transform: uppercase; }
        .stat-grid { width: 100%; margin: 10px 0; }
        .stat-grid td { border: 1px solid #e2e8f0; padding: 6px; background: #F5FAF0; text-align: center; width: 12.5%; }
        .stat-value { font-size: 14px; font-weight: bold; color: #2D3E50; }
        .stat-label { font-size: 8px; color: #64748b; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>{{ $period_label }} Training Report</h1>
    <p class="meta">
        {{ $scope_label }} · {{ $viewer->name }} ·
        {{ $period_start->format('M j, Y') }} – {{ $period_end->format('M j, Y') }} ·
        Generated {{ $generated_at->format('M j, Y g:i A') }}
    </p>

    <table class="stat-grid">
        <tr>
            <td><div class="stat-value">{{ $summary['lessons_completed'] }}</div><div class="stat-label">Lessons</div></td>
            <td><div class="stat-value">{{ $summary['courses_completed'] }}</div><div class="stat-label">Courses</div></td>
            <td><div class="stat-value">{{ $summary['assessments_passed'] }}</div><div class="stat-label">Assessments</div></td>
            <td><div class="stat-value">{{ $summary['certifications_issued'] }}</div><div class="stat-label">Certs</div></td>
            <td><div class="stat-value">{{ $summary['training_hours'] }}h</div><div class="stat-label">Hours</div></td>
            <td><div class="stat-value">{{ $summary['avg_course_progress'] }}%</div><div class="stat-label">Avg progress</div></td>
            <td><div class="stat-value">{{ $summary['assignments_overdue'] }}</div><div class="stat-label">Overdue</div></td>
            <td><div class="stat-value">{{ $summary['active_learners'] }}</div><div class="stat-label">Active</div></td>
        </tr>
    </table>

    @if ($top_courses !== [])
        <h2>Top Completed Courses</h2>
        <table>
            <thead><tr><th>Course</th><th>Completions</th></tr></thead>
            <tbody>
                @foreach ($top_courses as $course)
                    <tr><td>{{ $course['title'] }}</td><td>{{ $course['completions'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($course_rows !== [])
        <h2>Course Progress</h2>
        <table>
            <thead><tr><th>Course</th><th>Progress</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($course_rows as $course)
                    <tr>
                        <td>{{ $course['title'] }}</td>
                        <td>{{ $course['progress_percent'] }}%</td>
                        <td>{{ $course['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($member_rows !== [])
        <h2>Learner Activity</h2>
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Lessons</th>
                    <th>Courses</th>
                    <th>Avg progress</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($member_rows as $member)
                    <tr>
                        <td>{{ $member['name'] }}</td>
                        <td>{{ $member['lessons_completed'] }}</td>
                        <td>{{ $member['courses_completed'] }}</td>
                        <td>{{ $member['avg_progress'] }}%</td>
                        <td>{{ number_format($member['points']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
