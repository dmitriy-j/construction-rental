<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyRegistrationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_company_registration()
    {
        $response = $this->post('/register/company', [
            'company_name' => 'Test Company',
            'company_type' => 'tenant',
            'inn' => '123456789012',
            'ogrn' => '1234567890123',
            'legal_address' => 'Test Address',
            'bank_name' => 'Test Bank',
            'bank_account' => '12345678901234567890',
            'bik' => '123456789',
            'correspondent_account' => '09876543210987654321',
            'director' => 'Test Director',
            'phone' => '+79991234567',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertRedirect('/tenant/dashboard');
        $this->assertDatabaseHas('companies', ['name' => 'Test Company']);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }
}
