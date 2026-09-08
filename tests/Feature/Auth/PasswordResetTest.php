<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.reset'));

        Mail::assertSent(PasswordResetOtp::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/reset-password');

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Mail::assertSent(PasswordResetOtp::class, function ($mail) use ($user) {
            $response = $this->post('/reset-password', [
                'email' => $user->email,
                'otp' => $mail->otp,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_password_reset_attempts_are_rate_limited(): void
    {
        $payload = [
            'email' => 'person@example.com',
            'otp' => '000000',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/reset-password', $payload)->assertSessionHasErrors('otp');
        }

        $this->post('/reset-password', $payload)->assertTooManyRequests();
        RateLimiter::clear('person@example.com|127.0.0.1');
    }
}
