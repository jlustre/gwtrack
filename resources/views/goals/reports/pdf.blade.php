<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $period_label }} Goal Report — {{ $user->name }}</title>
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
    </style>
</head>
<body>
    <h1>{{ $period_label }} Goal Performance Report</h1>
    <p class="meta">
        {{ $user->name }} &middot;
        {{ $period_start->format('M j, Y') }} – {{ $period_end->format('M j, Y') }} &middot;
        Generated {{ $generated_at->format('M j, Y g:i A') }}
    </p>

    <table class="stat-grid">
        <tr>
            <td><div class="stat-value">{{ $summary['total'] ?? 0 }}</div><div class="stat-label">Total Goals</div></td>
            <td><div class="stat-value">{{ $average_progress }}%</div><div class="stat-label">Avg Progress</div></td>
            <td><div class="stat-value">{{ $completed_count }}</div><div class="stat-label">Completed</div></td>
            <td><div class="stat-value">{{ $off_track_count }}</div><div class="stat-label">Off Track</div></td>
        </tr>
    </table>

    <h2>Goals</h2>
    <table>
        <thead>
            <tr>
                <th>Goal</th>
                <th>Category</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Deadline</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($goals as $goal)
                <tr>
                    <td>{{ $goal->name }}</td>
                    <td>{{ $goal->category?->name }}</td>
                    <td>{{ $goal->progressPercent() }}%</td>
                    <td>{{ config('goals.statuses.'.$goal->status, $goal->status) }}</td>
                    <td>{{ $goal->deadline_at?->format('M j, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No goals in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($scorecard)
        <h2>Category Scorecard</h2>
        <table>
            <thead><tr><th>Category</th><th>Goals</th><th>Score</th></tr></thead>
            <tbody>
                @foreach ($scorecard->scores ?? [] as $slug => $row)
                    <tr>
                        <td>{{ $row['name'] ?? $slug }}</td>
                        <td>{{ $row['goal_count'] ?? 0 }}</td>
                        <td>{{ $row['score'] ?? 0 }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Overall score:</strong> {{ $scorecard->overall_score }}%</p>
    @endif

    @if ($achievements->isNotEmpty())
        <h2>Achievements Earned</h2>
        <ul>
            @foreach ($achievements as $achievement)
                <li>{{ $achievement->badge?->name }} — {{ $achievement->earned_at->format('M j, Y') }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
