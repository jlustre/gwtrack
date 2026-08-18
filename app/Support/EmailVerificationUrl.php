<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class EmailVerificationUrl
{
    public static function signedUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) Config::get('auth.verification.expire', 4320)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }

    public static function expiresInHours(): int
    {
        return (int) ceil(((int) Config::get('auth.verification.expire', 4320)) / 60);
    }
}
