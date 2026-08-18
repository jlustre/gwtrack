<?php

namespace App\Services\Notifications\Transports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsTransport
{
    /**
     * @return array{success: bool, message: string, driver: string, sid?: string}
     */
    public function send(string $phone, string $body, ?string $from = null): array
    {
        $body = trim($body);

        if ($body === '') {
            return [
                'success' => false,
                'message' => 'SMS body is empty.',
                'driver' => (string) config('notifications.sms.driver', 'log'),
            ];
        }

        $driver = (string) config('notifications.sms.driver', 'log');
        $fromLabel = $from ?? (string) config('notifications.sms.from', 'GWTrack');

        return match ($driver) {
            'twilio' => $this->sendViaTwilio($phone, $body, $fromLabel),
            default => $this->sendViaLog($phone, $body, $fromLabel),
        };
    }

    public function isConfigured(): bool
    {
        if (! config('notifications.sms.enabled', false)) {
            return false;
        }

        return match (config('notifications.sms.driver', 'log')) {
            'twilio' => filled(config('notifications.sms.twilio.sid'))
                && filled(config('notifications.sms.twilio.token'))
                && filled(config('notifications.sms.twilio.from')),
            default => true,
        };
    }

    /**
     * @return array{success: bool, message: string, driver: string}
     */
    private function sendViaLog(string $phone, string $body, string $from): array
    {
        Log::info('Notification SMS (log driver)', [
            'to' => $phone,
            'from' => $from,
            'body' => $body,
        ]);

        return [
            'success' => true,
            'message' => 'SMS logged for delivery.',
            'driver' => 'log',
        ];
    }

    /**
     * @return array{success: bool, message: string, driver: string, sid?: string}
     */
    private function sendViaTwilio(string $phone, string $body, string $from): array
    {
        $sid = (string) config('notifications.sms.twilio.sid');
        $token = (string) config('notifications.sms.twilio.token');
        $fromNumber = (string) config('notifications.sms.twilio.from');

        if ($sid === '' || $token === '' || $fromNumber === '') {
            return [
                'success' => false,
                'message' => 'Twilio credentials are not configured.',
                'driver' => 'twilio',
            ];
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $phone,
                'From' => $fromNumber,
                'Body' => $body,
            ]);

        if (! $response->successful()) {
            Log::warning('Twilio SMS failed', [
                'to' => $phone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => (string) ($response->json('message') ?? 'Twilio SMS request failed.'),
                'driver' => 'twilio',
            ];
        }

        return [
            'success' => true,
            'message' => 'SMS sent via Twilio.',
            'driver' => 'twilio',
            'sid' => (string) $response->json('sid'),
        ];
    }
}
