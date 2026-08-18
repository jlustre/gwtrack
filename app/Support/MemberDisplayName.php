<?php

namespace App\Support;

use App\Models\User;

final class MemberDisplayName
{
    /** @var array<string, string> */
    private const FAP_QUEUE_DEMO_NAMES = [
        'fap.queue1@example.com' => 'Owen Taylor',
        'fap.queue2@example.com' => 'Quinn Martin',
        'fap.queue3@example.com' => 'Albert Reyes',
        'fap.queue4@example.com' => 'Morgan Lee',
        'fap.queue5@example.com' => 'Blair Chen',
        'fap.queue.us-ca@example.com' => 'Caleb Morris',
        'fap.queue.us-tx@example.com' => 'Dallas Brooks',
        'fap.queue.us-fl@example.com' => 'Fiona Grant',
        'fap.queue.us-ny@example.com' => 'Nina York',
        'fap.queue.us-wa@example.com' => 'Walter Stone',
    ];

    public static function for(User $member): string
    {
        $name = trim($member->name);

        if (! str_starts_with($name, 'FAP Queue')) {
            return $name;
        }

        $mapped = self::FAP_QUEUE_DEMO_NAMES[strtolower($member->email)] ?? null;
        if ($mapped !== null) {
            return $mapped;
        }

        $city = trim((string) ($member->profile?->city ?? ''));
        if ($city !== '') {
            return $city.' Associate';
        }

        return 'Associate';
    }

    public static function fapQueueLabelFor(User $member): ?string
    {
        $bio = trim((string) ($member->profile?->bio ?? ''));

        if (str_starts_with($bio, 'FAP Queue')) {
            return $bio;
        }

        if (str_starts_with($member->name, 'FAP Queue')) {
            return $member->name;
        }

        return null;
    }
}
