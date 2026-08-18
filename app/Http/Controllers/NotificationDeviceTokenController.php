<?php

namespace App\Http\Controllers;

use App\Services\Notifications\NotificationDeviceTokenService;
use App\Services\Notifications\Transports\WebPushTransport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationDeviceTokenController extends Controller
{
    public function vapidPublicKey(WebPushTransport $webPush): JsonResponse
    {
        return response()->json([
            'enabled' => (bool) config('notifications.push.enabled', false),
            'public_key' => $webPush->publicKey(),
        ]);
    }

    public function store(Request $request, NotificationDeviceTokenService $tokens): JsonResponse
    {
        abort_unless($request->user()?->can('manage own notification preferences'), 403);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', 'in:ios,android,web'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'subscription_payload' => ['nullable', 'string', 'max:5000'],
        ]);

        $record = $tokens->register(
            $request->user(),
            $validated['token'],
            $validated['platform'],
            $validated['device_name'] ?? null,
            $validated['subscription_payload'] ?? null,
        );

        return response()->json([
            'id' => $record->id,
            'platform' => $record->platform,
            'token' => $record->token,
        ]);
    }

    public function destroy(Request $request, NotificationDeviceTokenService $tokens): JsonResponse
    {
        abort_unless($request->user()?->can('manage own notification preferences'), 403);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $tokens->revoke($request->user(), $validated['token']);

        return response()->json(['revoked' => true]);
    }
}
