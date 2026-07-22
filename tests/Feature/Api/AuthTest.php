<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TestSeeder::class);
    }

    public function test_guest_cannot_access_dashboard()
    {
        $this->get('/lessee/dashboard')->assertRedirect('/login');
        $this->get('/lessor/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::where('email', 'admin@test.com')->first();
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
    }

    public function test_lessee_cannot_access_admin()
    {
        $user = User::where('email', 'lessee@test.com')->first();
        $this->actingAs($user)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_lessor_cannot_access_lessee_dashboard()
    {
        $user = User::where('email', 'lessor@test.com')->first();
        $this->actingAs($user)->get('/lessee/dashboard')->assertStatus(403);
    }

    public function test_login_with_valid_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $response->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_password()
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
