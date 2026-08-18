<?php

namespace Database\Factories;

use App\Models\RegistrationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RegistrationInvitation>
 */
class RegistrationInvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sponsor_id' => User::factory(),
            'code' => RegistrationInvitation::generateCode(),
            'email' => null,
            'role_name' => 'member',
            'max_uses' => 1,
            'uses_count' => 0,
            'expires_at' => now()->addDays(14),
        ];
    }
}
