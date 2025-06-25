<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request()
    {
        Notification::fake();

        $user = User::factory()->verifiedCompany()->create();

        $this->postJson('/api/forgot-password', [
            'email' => $user->email
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset()
    {
        $user = User::factory()->verifiedCompany()->create();

        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }
}
