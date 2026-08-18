<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['report_type_label'] ?? 'CFM Effectiveness Report' }} — {{ $payload['cfm']['name'] ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #2D3E50; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 20px 0 8px; border-bottom: 1px solid #84BB41; padding-bottom: 4px; }
        .meta { color: #64748b; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; }
        .stat-grid { width: 100%; margin: 12px 0; }
        .stat-grid td { border: none; padding: 8px; background: #F5FAF0; text-align: center; width: 25%; }
        .stat-value { font-size: 18px; font-weight: bold; color: #2D3E50; }
        .stat-label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        ul { margin: 8px 0; padding-left: 18px; }
        .risk-high { color: #b91c1c; }
        .risk-medium { color: #b45309; }
    </style>
</head>
<body>
    <h1>{{ $payload['report_type_label'] ?? 'CFM Effectiveness Report' }}</h1>
    <p class="meta">
        {{ $payload['cfm']['name'] ?? '' }} &middot;
        {{ $payload['period_label'] ?? '' }}:
        {{ \Carbon\Carbon::parse($payload['period_start'] ?? now())->format('M j, Y') }}
        –
        {{ \Carbon\Carbon::parse($payload['period_end'] ?? now())->format('M j, Y') }}
        &middot;
        Generated {{ $report->created_at?->format('M j, Y g:i A') }}
    </p>

    <table class="stat-grid">
        <tr>
            <td>
                <div class="stat-value">{{ $payload['effectiveness_score'] ?? 0 }}</div>
                <div class="stat-label">Effectiveness</div>
            </td>
            <td>
                <div class="stat-value">{{ $payload['trainee_satisfaction'] ?? 0 }}%</div>
                <div class="stat-label">Satisfaction</div>
            </td>
            <td>
                <div class="stat-value">{{ $payload['ao_rating'] ?? '—' }}</div>
                <div class="stat-label">AO Rating</div>
            </td>
            <td>
                <div class="stat-value">{{ count($payload['risks'] ?? []) }}</div>
                <div class="stat-label">Active risks</div>
            </td>
        </tr>
    </table>

    <h2>Score breakdown</h2>
    <table>
        <tr><th>Objective (70%)</th><td>{{ $payload['score_breakdown']['objective'] ?? 0 }}</td></tr>
        <tr><th>Trainee feedback (20%)</th><td>{{ $payload['score_breakdown']['feedback'] ?? 0 }}</td></tr>
        <tr><th>AO evaluation (10%)</th><td>{{ $payload['score_breakdown']['ao'] ?? 0 }}</td></tr>
        <tr><th>Open coaching items</th><td>{{ $payload['open_coaching_items'] ?? 0 }}</td></tr>
        <tr><th>Pending reviews</th><td>{{ $payload['upcoming_reviews'] ?? 0 }}</td></tr>
    </table>

    @if (! empty($payload['objective_metrics']))
        <h2>Objective metrics</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Score</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['objective_metrics'] as $metric)
                    <tr>
                        <td>{{ $metric['label'] ?? '' }}</td>
                        <td>{{ $metric['score'] ?? 0 }}</td>
                        <td>{{ $metric['detail'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @php($analytics = $payload['success_analytics'] ?? [])
    @if (! empty($analytics))
        <h2>Trainee success analytics</h2>
        <table>
            <tr><th>Trainees tracked</th><td>{{ $analytics['trainee_count'] ?? 0 }}</td></tr>
            <tr><th>Avg. time to license</th><td>{{ $analytics['avg_time_to_license_days'] ?? '—' }} days</td></tr>
            <tr><th>Avg. time to FAP completion</th><td>{{ $analytics['avg_time_to_fap_days'] ?? '—' }} days</td></tr>
            <tr><th>Avg. time to first sale</th><td>{{ $analytics['avg_time_to_first_sale_days'] ?? '—' }} days</td></tr>
            <tr><th>Avg. time to first recruit</th><td>{{ $analytics['avg_time_to_first_recruit_days'] ?? '—' }} days</td></tr>
        </table>
    @endif

    @if (! empty($payload['risks']))
        <h2>Risk flags</h2>
        <ul>
            @foreach ($payload['risks'] as $risk)
                <li class="risk-{{ $risk['level'] ?? $risk['severity'] ?? 'medium' }}">
                    {{ $risk['message'] ?? '' }}
                </li>
            @endforeach
        </ul>
    @endif

    @if (! empty($payload['recommendations']))
        <h2>Improvement recommendations</h2>
        <table>
            <thead>
                <tr>
                    <th>Area</th>
                    <th>Score</th>
                    <th>Priority</th>
                    <th>Suggestion</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['recommendations'] as $item)
                    <tr>
                        <td>{{ $item['area'] ?? '' }}</td>
                        <td>{{ $item['current_score'] ?? '—' }}</td>
                        <td>{{ ucfirst($item['priority'] ?? 'medium') }}</td>
                        <td>{{ $item['suggestion'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($payload['leaderboard']))
        <h2>Mentor comparison leaderboard</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>CFM</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['leaderboard'] as $row)
                    <tr>
                        <td>{{ $row['rank'] ?? '—' }}</td>
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td>{{ $row['score'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($payload['agency_overview']))
        @php($agency = $payload['agency_overview'])
        <h2>Agency overview</h2>
        <table>
            <tr><th>CFMs</th><td>{{ $agency['cfm_count'] ?? 0 }}</td></tr>
            <tr><th>Average effectiveness</th><td>{{ $agency['average_effectiveness'] ?? 0 }}</td></tr>
            <tr><th>At-risk CFMs</th><td>{{ count($agency['at_risk_cfms'] ?? []) }}</td></tr>
        </table>
    @endif
</body>
</html>
