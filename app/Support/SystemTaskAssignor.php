<?php

namespace App\Support;

class SystemTaskAssignor
{
    public const USER_ID = 1;

    public const NAME = 'System';

    public const EMAIL = 'system@gwtracks.com';

    public static function id(): int
    {
        return self::USER_ID;
    }

    public static function isSystemAssignor(?int $assignorId): bool
    {
        return (int) $assignorId === self::USER_ID;
    }
}
