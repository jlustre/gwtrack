<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Financial Needs Analysis Invite</title>
</head>
<body style="font-family: Arial, sans-serif; color: #2D3E50; line-height: 1.5;">
    <p>Hello {{ $recipientName }},</p>

    <p>{{ $agentName }} has invited you to complete your Financial Needs Analysis through a secure online portal.</p>

    <p style="margin-top: 1rem; padding: 12px 14px; border-left: 4px solid #84BB41; background: #F5FAF0;">
        <strong>Your privacy:</strong>
        The information you provide is strictly confidential.
        @if ($cfmName)
            Only <strong>{{ $cfmName }}</strong>, your Certified Field Mentor and licensed insurance writing agent, can access your Financial Needs Analysis responses.
        @else
            Only your Certified Field Mentor (CFM), acting as your licensed insurance writing agent, can access your Financial Needs Analysis responses.
        @endif
        No other agents — including other licensed associates — can view your personal financial information.
    </p>

    @if (filled($personalMessage))
        <p style="white-space: pre-line;">{{ $personalMessage }}</p>
    @endif

    <p><strong>Step 1:</strong> Open your secure invite link:</p>
    <p><a href="{{ $inviteUrl }}">{{ $inviteUrl }}</a></p>

    <p><strong>Step 2:</strong> Enter this security code when prompted:</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 0.25em;">{{ $securityCode }}</p>

    @if ($expiresAt)
        <p>This invite expires on {{ $expiresAt->timezone(config('app.timezone'))->format('F j, Y g:i A T') }}.</p>
    @endif

    <p>If you have questions, reply to this email and {{ $agentName }} will get back to you.</p>

    <p>Thank you,<br>{{ $agentName }}</p>
</body>
</html>
