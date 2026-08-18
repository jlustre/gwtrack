<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $mail['subject'] ?? config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #2D3E50; line-height: 1.5;">
    @if (! empty($mail['greeting']))
        <p><strong>{{ $mail['greeting'] }}</strong></p>
    @endif

    @foreach ($mail['lines'] ?? [] as $line)
        <p>{{ $line }}</p>
    @endforeach

    @if (! empty($mail['action_text']) && ! empty($mail['action_url']))
        <p>
            <a href="{{ $mail['action_url'] }}" style="display:inline-block;padding:10px 16px;background:#84BB41;color:#2D3E50;text-decoration:none;border-radius:6px;font-weight:bold;">
                {{ $mail['action_text'] }}
            </a>
        </p>
    @endif

    <p style="color:#64748B;font-size:12px;margin-top:24px;">{{ config('app.name') }}</p>
</body>
</html>
