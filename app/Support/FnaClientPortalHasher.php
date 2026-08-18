<?php

namespace App\Support;

class FnaClientPortalHasher
{
    public static function hashSecurityCode(string $code): string
    {
        return self::hmac($code);
    }

    public static function verifySecurityCode(string $code, string $hash): bool
    {
        return hash_equals($hash, self::hashSecurityCode($code));
    }

    public static function hashAccessCredentials(string $email, string $phone, string $ssnLastFour): string
    {
        return self::hmac(self::normalizeCredentialPayload($email, $phone, $ssnLastFour));
    }

    public static function verifyAccessCredentials(string $email, string $phone, string $ssnLastFour, string $hash): bool
    {
        return hash_equals($hash, self::hashAccessCredentials($email, $phone, $ssnLastFour));
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public static function normalizeSsnLastFour(string $ssnLastFour): string
    {
        return substr(preg_replace('/\D+/', '', $ssnLastFour) ?? '', -4);
    }

    protected static function normalizeCredentialPayload(string $email, string $phone, string $ssnLastFour): string
    {
        return implode('|', [
            self::normalizeEmail($email),
            self::normalizePhone($phone),
            self::normalizeSsnLastFour($ssnLastFour),
        ]);
    }

    protected static function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
