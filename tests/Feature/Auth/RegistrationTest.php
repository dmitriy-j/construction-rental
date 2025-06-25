<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registration()
    {
        $response = $this->postJson('/api/register', [
            'company' => [
                'name' => 'Test Company',
                'type' => 'tenant',
                'inn' => '1234567890',
                'ogrn' => '1234567890123',
                'legal_address' => 'Test Address',
                'bank_name' => 'Test Bank',
                'bank_account' => '12345678901234567890',
                'bik' => '123456789',
                'correspondent_account' => '12345678901234567890',
                'director' => 'Test Director',
                'phone' => '+71234567890',
            ],
            'user' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        ]);

        $response->assertStatus(201);

        // Проверяем создание компании
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'status' => 'pending'
        ]);

        // Проверяем создание пользователя
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'type' => 'tenant'
        ]);

        // Проверяем хеширование пароля
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_registration_validation_errors()
    {
        $response = $this->postJson('/api/register', [
            'company' => ['name' => ''],
            'user' => ['email' => 'invalid']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'company.name',
                'company.inn',
                'user.email',
                'user.password'
            ]);
    }
}
