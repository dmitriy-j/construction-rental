<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_successful_login()
    {
        $user = User::factory()->verifiedCompany()->create([
            'password' => bcrypt('password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_login_with_unverified_company()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Company not verified']);
    }

    public function test_login_with_invalid_credentials()
    {
        $user = User::factory()->verifiedCompany()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_platform_admin_login()
    {
        $admin = User::factory()->platformAdmin()->create([
            'password' => bcrypt('adminpassword')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'adminpassword'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }
}
