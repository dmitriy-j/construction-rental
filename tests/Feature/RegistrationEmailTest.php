<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Mail\CompanyRegisteredMail;

class RegistrationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sent_on_registration()
    {
        Mail::fake();

        $response = $this->post('/register/company', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Test Company',
            'inn' => '123456789012',
            'ogrn' => '1234567890123',
            'legal_address' => 'Test Legal Address',
            'bank_name' => 'Test Bank',
            'bank_account' => '12345678901234567890',
            'bik' => '123456789',
            'correspondent_account' => '09876543210987654321',
            'director' => 'Test Director',
            'phone' => '+79123456789',
        ]);

        $response->assertRedirect(route('tenant.dashboard'));

        // Исправлено: проверяем правильный класс CompanyRegisteredMail
        Mail::assertQueued(CompanyRegisteredMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }
}
