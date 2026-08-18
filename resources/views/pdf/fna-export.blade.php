<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FNA Export — {{ $reference_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; color: #172033; margin: 32px 40px; }
        h1 { font-size: 18px; color: #2D3E50; margin: 0 0 4px; }
        h2 { font-size: 12px; color: #2D3E50; margin: 16px 0 6px; border-bottom: 1px solid #84BB41; padding-bottom: 3px; text-transform: uppercase; }
        .meta { font-size: 9px; color: #64748b; margin-bottom: 14px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.info td { border-bottom: 1px solid #e2e8f0; padding: 5px 6px; vertical-align: top; }
        table.info td.label { font-weight: bold; width: 32%; color: #2D3E50; }
        .restricted { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 8px; color: #64748b; font-style: italic; }
        .disclaimer { margin-top: 20px; padding: 8px; background: #F5FAF0; border: 1px solid #84BB41; font-size: 8px; color: #2D3E50; }
        ul { margin: 4px 0 8px 14px; padding: 0; }
        li { margin-bottom: 2px; }
        .comment { border-left: 3px solid #84BB41; padding: 4px 8px; margin-bottom: 6px; background: #f8fafc; }
    </style>
</head>
<body>
    <h1>Financial Needs Analysis</h1>
    <div class="meta">
        {{ $reference_code }} · {{ $status }} · {{ $completeness_score }}% complete · Generated {{ $generated_at->format('M j, Y g:i A') }}
        @if ($owner_name) · Associate: {{ $owner_name }} @endif
        @if ($cfm_name) · CFM: {{ $cfm_name }} @endif
    </div>

    @include('fna.partials.export-content')

    <div class="disclaimer">{{ $dime_disclaimer }}</div>
</body>
</html>
