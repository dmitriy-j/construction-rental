<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_sent()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $this->actingAs($user)
            ->postJson('/api/email/verification-notification');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_can_be_verified()
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)
            ->getJson($verificationUrl);

        $response->assertStatus(200);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
