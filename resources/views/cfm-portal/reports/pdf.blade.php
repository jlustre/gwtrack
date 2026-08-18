<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['report_type_label'] ?? 'Progress Report' }} — {{ $payload['trainee']['name'] ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #2D3E50; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 20px 0 8px; border-bottom: 1px solid #84BB41; padding-bottom: 4px; }
        .meta { color: #64748b; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; }
        .stat-grid { width: 100%; margin: 12px 0; }
        .stat-grid td { border: none; padding: 8px; background: #F5FAF0; text-align: center; width: 20%; }
        .stat-value { font-size: 18px; font-weight: bold; color: #2D3E50; }
        .stat-label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        ul { margin: 8px 0; padding-left: 18px; }
    </style>
</head>
<body>
    <h1>{{ $payload['report_type_label'] ?? 'Trainee Progress Report' }}</h1>
    <p class="meta">
        {{ $payload['trainee']['name'] ?? '' }} &middot;
        CFM: {{ $payload['cfm']['name'] ?? '' }} &middot;
        Generated {{ $report->created_at?->format('M j, Y g:i A') }}
    </p>

    <table class="stat-grid">
        <tr>
            <td><div class="stat-value">{{ $payload['progress']['onboarding'] ?? 0 }}%</div><div class="stat-label">Onboarding</div></td>
            <td><div class="stat-value">{{ $payload['progress']['fap'] ?? 0 }}%</div><div class="stat-label">FAP</div></td>
            <td><div class="stat-value">{{ $payload['progress']['licensing'] ?? 0 }}%</div><div class="stat-label">Licensing</div></td>
            <td><div class="stat-value">{{ $payload['progress']['training'] ?? 0 }}%</div><div class="stat-label">Training</div></td>
            <td><div class="stat-value">{{ $payload['progress']['rank'] ?? 0 }}%</div><div class="stat-label">Rank</div></td>
        </tr>
    </table>

    <h2>Summary</h2>
    <table>
        <tr><th>Rank</th><td>{{ $payload['trainee']['rank'] ?? '—' }}</td></tr>
        <tr><th>Joined</th><td>{{ $payload['trainee']['joined_at'] ?? '—' }}</td></tr>
        <tr><th>Open coaching tasks</th><td>{{ $payload['open_tasks'] ?? 0 }}</td></tr>
        <tr><th>Risk level</th><td>{{ ucfirst($payload['risk']['level'] ?? 'low') }} ({{ $payload['risk']['score'] ?? 0 }})</td></tr>
        <tr><th>Promotion ready</th><td>{{ ($payload['promotion_ready'] ?? false) ? 'Yes' : 'No' }}</td></tr>
    </table>

    @if (! empty($payload['goals']))
        <h2>Goals</h2>
        <table>
            <thead>
                <tr>
                    <th>Goal</th>
                    <th>Category</th>
                    <th>Progress</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['goals'] as $goal)
                    <tr>
                        <td>{{ $goal['name'] }}</td>
                        <td>{{ $goal['category'] }}</td>
                        <td>{{ $goal['progress'] }}%</td>
                        <td>{{ $goal['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($payload['recent_notes']))
        <h2>Recent coaching notes</h2>
        <ul>
            @foreach ($payload['recent_notes'] as $note)
                <li><strong>{{ $note['category'] }}</strong> ({{ $note['created_at'] }}) — {{ \Illuminate\Support\Str::limit($note['body'], 120) }}</li>
            @endforeach
        </ul>
    @endif

    @if (! empty($payload['risk']['recommended_actions']))
        <h2>Recommended actions</h2>
        <ul>
            @foreach ($payload['risk']['recommended_actions'] as $action)
                <li>{{ $action }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
