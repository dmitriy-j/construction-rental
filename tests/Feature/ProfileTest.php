<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile()
    {
        $user = User::factory()->verifiedCompany()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }

    public function test_user_can_update_password()
    {
        $user = User::factory()->verifiedCompany()->create([
            'password' => bcrypt('oldpassword')
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_company_admin_can_update_company()
    {
        $user = User::factory()->verifiedCompany()->create([
            'role' => 'company_admin'
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/company', [
            'name' => 'Updated Company',
            'phone' => '+79998887766'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'name' => 'Updated Company',
            'phone' => '+79998887766'
        ]);
    }
}
