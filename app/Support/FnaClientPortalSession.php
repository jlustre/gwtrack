<?php

namespace App\Support;

use App\Models\FnaClientInvite;

class FnaClientPortalSession
{
    public const INVITE_ID_KEY = 'fna_client_invite_id';

    public const VERIFIED_AT_KEY = 'fna_client_invite_verified_at';

    public static function markVerified(FnaClientInvite $invite): void
    {
        session([
            self::INVITE_ID_KEY => $invite->id,
            self::VERIFIED_AT_KEY => now()->timestamp,
        ]);
    }

    public static function clear(): void
    {
        session()->forget([self::INVITE_ID_KEY, self::VERIFIED_AT_KEY]);
    }

    public static function isVerifiedFor(FnaClientInvite $invite): bool
    {
        if (session(self::INVITE_ID_KEY) !== $invite->id) {
            return false;
        }

        $verifiedAt = session(self::VERIFIED_AT_KEY);

        if (! $verifiedAt) {
            return false;
        }

        $ttlMinutes = (int) config('fna.client_portal.session_ttl_minutes', 120);

        return now()->timestamp - (int) $verifiedAt <= ($ttlMinutes * 60);
    }

    public static function assertVerifiedFor(FnaClientInvite $invite): void
    {
        if (! self::isVerifiedFor($invite)) {
            abort(403, 'Your secure session has expired. Please verify your access again.');
        }
    }
}
