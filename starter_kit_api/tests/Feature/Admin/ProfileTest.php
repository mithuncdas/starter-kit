<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function profile_view_returns_authenticated_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/profile');

        $response->assertOk()
            ->assertJsonPath('data.id', $admin->id)
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonPath('data.user_type_label', 'Admin')
            ->assertJsonPath('data.status_label', 'Active');
    }

    #[Test]
    public function profile_update_changes_name_and_email(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/admin/profile', [
                'name' => 'New Name',
                'email' => 'new-email@example.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new-email@example.com');

        $admin->refresh();
        $this->assertSame('New Name', $admin->name);
        $this->assertSame('new-email@example.com', $admin->email);
    }

    #[Test]
    public function profile_update_rejects_duplicate_email(): void
    {
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/admin/profile', [
                'name' => 'New Name',
                'email' => $other->email,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function profile_update_allows_keeping_own_email(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/admin/profile', [
                'name' => 'Updated Name',
                'email' => $admin->email,
            ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');
    }
}
