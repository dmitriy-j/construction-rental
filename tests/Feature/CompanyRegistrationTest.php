<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registration()
    {
        $response = $this->post('/register/company', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Test Company',
            'vat' => true,
            'inn' => '1234567890',
            'ogrn' => '1234567890123',
            'legal_address' => 'Test Legal Address',
            'same_address' => true,
            'bank_name' => 'Test Bank',
            'bank_account' => '12345678901234567890',
            'bik' => '123456789',
            'correspondent_account' => '09876543210987654321',
            'director' => 'Test Director',
            'phone' => '+79123456789',
        ]);

        $this->assertDatabaseHas('companies', ['name' => 'Test Company']);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $response->assertRedirect('/tenant/dashboard');
    }
}
