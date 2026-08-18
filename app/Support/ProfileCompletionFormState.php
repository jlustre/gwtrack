<?php

namespace App\Support;

use App\Models\User;

class ProfileCompletionFormState
{
    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     phone: string,
     *     best_contact_time: string,
     *     gw_associate_id: string,
     *     gw_invite_link: string,
     *     city: string,
     *     country_id: string,
     *     state_province_id: string,
     *     timezone_id: string,
     *     bio: string
     * }
     */
    public static function forUser(User $user): array
    {
        $user->loadMissing('profile');

        return [
            'name' => (string) old('name', $user->name),
            'email' => (string) old('email', $user->email),
            'phone' => (string) old('phone', $user->profile?->phone ?? ''),
            'best_contact_time' => (string) old('best_contact_time', $user->profile?->best_contact_time ?? ''),
            'gw_associate_id' => (string) old('gw_associate_id', $user->profile?->gw_associate_id ?? ''),
            'gw_invite_link' => (string) old('gw_invite_link', $user->profile?->gw_invite_link ?? ''),
            'city' => (string) old('city', $user->profile?->city ?? ''),
            'country_id' => (string) old('country_id', $user->profile?->country_id ?? ''),
            'state_province_id' => (string) old('state_province_id', $user->profile?->state_province_id ?? ''),
            'timezone_id' => (string) old('timezone_id', $user->profile?->timezone_id ?? ''),
            'bio' => (string) old('bio', $user->profile?->bio ?? ''),
        ];
    }
}
