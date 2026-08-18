<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified_without_logging_in(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('login', absolute: false));
        $this->assertGuest();
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->get($verificationUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_guest_can_request_a_new_verification_email(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $this->get(route('verification.resend', ['email' => 'member@example.com']))
            ->assertOk()
            ->assertSee('Resend Verification Email', false)
            ->assertSee('Send Verification Link', false);

        $user = User::factory()->unverified()->create();

        $this->post(route('verification.resend.store'), [
            'email' => $user->email,
        ])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status');
    }
}
