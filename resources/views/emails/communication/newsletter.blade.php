<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $newsletter->subject }}</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;">
    <p style="font-family:sans-serif;font-size:13px;color:#64748B;margin:0 0 16px;">
        Hello {{ $user->name }},
    </p>
    {!! $newsletter->html_body !!}
</body>
</html>
