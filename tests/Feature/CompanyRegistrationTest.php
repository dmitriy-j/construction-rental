<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            \Log::debug("SQL: " . $query->sql . ", bindings: " . json_encode($query->bindings));
        });
    }

    public function test_company_registration()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->post(route('register.company.store'), [
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

        $this->assertDatabaseHas('companies', [
            'email' => 'test@example.com',
            'inn' => '123456789012',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'tenant',
        ]);

        $company = Company::where('email', 'test@example.com')->first();
        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($company);
        $this->assertNotNull($user);
        $this->assertEquals($company->id, $user->company_id);
    }
}
