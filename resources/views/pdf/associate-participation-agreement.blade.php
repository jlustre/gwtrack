<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Associate Participation Agreement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; color: #172033; margin: 36px 42px; }
        h1 { font-size: 20px; color: #2D3E50; margin: 0 0 4px; }
        h2 { font-size: 13px; color: #2D3E50; margin: 18px 0 8px; border-bottom: 1px solid #84BB41; padding-bottom: 4px; text-transform: uppercase; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 16px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.info td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; vertical-align: top; }
        table.info td.label { font-weight: bold; width: 28%; color: #2D3E50; }
        ul { margin: 6px 0 10px 16px; padding: 0; }
        li { margin-bottom: 3px; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .signature-table td { border-bottom: 1px solid #cbd5e1; padding: 8px 6px; }
        .signature-table .label { font-size: 10px; font-weight: bold; color: #64748b; }
        .note { background: #F5FAF0; border: 1px solid #84BB41; padding: 8px; margin: 8px 0; }
    </style>
</head>
<body>
    <h1>Associate Participation Agreement</h1>
    <div class="meta">Effective Date: {{ $agreement->effective_date?->format('F j, Y') }} · CONFIDENTIAL · v1.0</div>

    <h2>Associate Information</h2>
    <table class="info">
        <tr><td class="label">Full Name</td><td>{{ $agreement->full_name }}</td><td class="label">Email</td><td>{{ $agreement->email }}</td></tr>
        <tr><td class="label">Phone</td><td>{{ $agreement->phone }}</td><td class="label">Associate ID</td><td>{{ $agreement->associate_id }}</td></tr>
        <tr><td class="label">Address</td><td colspan="3">{{ $agreement->address }}</td></tr>
        <tr><td class="label">City</td><td>{{ $agreement->city }}</td><td class="label">State / Province</td><td>{{ $agreement->state_province }}</td></tr>
        <tr><td class="label">Country</td><td>{{ $agreement->country }}</td><td class="label">Sponsor Name</td><td>{{ $agreement->sponsor_name }}</td></tr>
    </table>

    @include('pdf.partials.associate-participation-agreement-body')

    <h2>Associate Signature</h2>
    <table class="signature-table">
        <tr>
            <td class="label">Associate Name</td>
            <td class="label">Signature</td>
            <td class="label">Date</td>
        </tr>
        <tr>
            <td>{{ $agreement->full_name }}</td>
            <td><em>{{ $agreement->associate_signature }}</em></td>
            <td>{{ $agreement->associate_signed_at?->format('F j, Y') }}</td>
        </tr>
    </table>

    <h2>Sponsor Acknowledgment</h2>
    <table class="signature-table">
        <tr><td class="label">Sponsor Name</td><td class="label">Signature</td><td class="label">Date</td></tr>
        <tr><td>{{ $agreement->sponsor_name ?: '—' }}</td><td><em>Awaiting sponsor</em></td><td>—</td></tr>
    </table>

    <h2>Organization Approval</h2>
    <table class="signature-table">
        <tr><td class="label">Authorized Representative</td><td class="label">Signature</td><td class="label">Date</td></tr>
        <tr><td><em>Awaiting organization</em></td><td>—</td><td>—</td></tr>
    </table>

    <div class="meta" style="margin-top: 24px; text-align: center;">
        Document Owner: {{ config('app.name') }} · Submitted {{ $agreement->updated_at?->format('F j, Y g:i A') }}
    </div>
</body>
</html>
