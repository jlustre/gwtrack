<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 28px 32px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.55;
            color: #1e293b;
            margin: 0;
        }

        .cover {
            background: #2D3E50;
            color: #ffffff;
            padding: 22px 24px 20px;
            margin: -8px -8px 22px;
            border-radius: 6px;
        }

        .cover .eyebrow {
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #F5C518;
            margin: 0 0 8px;
            font-weight: bold;
        }

        .cover h1 {
            font-size: 24px;
            margin: 0 0 8px;
            color: #ffffff;
            line-height: 1.25;
        }

        .cover .summary {
            font-size: 11px;
            color: #cbd5e1;
            margin: 0;
            max-width: 92%;
        }

        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 18px;
        }

        .meta-grid td {
            width: 33.33%;
            vertical-align: top;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
        }

        .meta-grid .label {
            display: block;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 4px;
        }

        .meta-grid .value {
            color: #2D3E50;
            font-size: 10.5px;
            font-weight: bold;
        }

        .content h1 {
            font-size: 18px;
            color: #2D3E50;
            margin: 22px 0 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #F5C518;
            page-break-after: avoid;
        }

        .content h2 {
            font-size: 14px;
            color: #2D3E50;
            margin: 20px 0 8px;
            padding: 6px 10px;
            background: #f1f5f9;
            border-left: 4px solid #F5C518;
            page-break-after: avoid;
        }

        .content h3 {
            font-size: 12.5px;
            color: #334155;
            margin: 16px 0 6px;
            page-break-after: avoid;
        }

        .content h4 {
            font-size: 11.5px;
            color: #475569;
            margin: 14px 0 6px;
            page-break-after: avoid;
        }

        .content p {
            margin: 0 0 9px;
        }

        .content ul,
        .content ol {
            margin: 0 0 12px 18px;
            padding: 0;
        }

        .content li {
            margin: 0 0 5px;
        }

        .content strong {
            color: #0f172a;
        }

        .content a {
            color: #2D3E50;
            text-decoration: underline;
        }

        .content hr {
            border: 0;
            border-top: 1px solid #e2e8f0;
            margin: 16px 0;
        }

        .content blockquote {
            margin: 10px 0 14px;
            padding: 10px 12px;
            background: #fffbeb;
            border-left: 4px solid #F5C518;
            color: #78350f;
        }

        .content pre,
        .content code {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9.5px;
        }

        .content pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 12px 14px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 10px 0 14px;
            page-break-inside: avoid;
        }

        .content code {
            background: #f1f5f9;
            color: #1e293b;
            padding: 1px 4px;
            border-radius: 3px;
        }

        .content pre code {
            background: transparent;
            color: inherit;
            padding: 0;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 16px;
            font-size: 10px;
            page-break-inside: auto;
        }

        .content thead {
            display: table-header-group;
        }

        .content tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .content th {
            background: #2D3E50;
            color: #ffffff;
            text-align: left;
            padding: 8px 9px;
            font-weight: bold;
            border: 1px solid #243342;
        }

        .content td {
            padding: 7px 9px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .content tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .content img.emoji-inline {
            display: inline-block;
            width: 1.05em;
            height: 1.05em;
            vertical-align: -0.12em;
            margin-right: 0.15em;
        }

        .footer-note {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="cover">
        <p class="eyebrow">GWTracks User Guide</p>
        <h1>{{ $title }}</h1>
        @if (filled($summary))
            <p class="summary">{{ $summary }}</p>
        @endif
    </div>

    @if (! empty($meta))
        <table class="meta-grid">
            <tr>
                @foreach ($meta as $label => $value)
                    <td>
                        <span class="label">{{ $label }}</span>
                        <span class="value">{{ $value }}</span>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="content">
        {!! $content !!}
    </div>

    <div class="footer-note">
        Generated for GWTracks &mdash; {{ now()->format('F j, Y') }}
    </div>
</body>
</html>
