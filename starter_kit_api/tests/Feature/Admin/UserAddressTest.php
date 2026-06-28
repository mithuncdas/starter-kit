<?php

namespace Tests\Feature\Admin;

use App\Enums\UserAddressLabelEnum;
use App\Models\AdminArea;
use App\Models\User;
use App\Models\UserAddress;
use Database\Seeders\LocationDataSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class UserAddressTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(LocationDataSeeder::class);
    }

    private function postalCode(string $code = '1360'): AdminArea
    {
        return AdminArea::query()->where('code', $code)->firstOrFail();
    }

    #[Test]
    public function index_requires_admins_view_permission(): void
    {
        $admin = $this->adminWithPermissions([]);
        $target = User::factory()->admin()->create();

        $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$target->id}/addresses")
            ->assertStatus(403);
    }

    #[Test]
    public function index_returns_addresses_with_primary_first(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $target = User::factory()->admin()->create();

        $area = $this->postalCode();
        UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
            'label' => UserAddressLabelEnum::Home,
            'is_primary' => false,
        ]);
        UserAddress::factory()->for($target)->primary()->create([
            'admin_area_id' => $area->id,
            'label' => UserAddressLabelEnum::Work,
        ]);

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$target->id}/addresses");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.is_primary', true)
            ->assertJsonPath('data.0.label', UserAddressLabelEnum::Work->value)
            ->assertJsonPath('data.1.is_primary', false);
    }

    #[Test]
    public function index_for_non_admin_user_returns_404(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $regularUser = User::factory()->create();

        $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$regularUser->id}/addresses")
            ->assertStatus(404);
    }

    #[Test]
    public function show_requires_admins_view_permission(): void
    {
        $admin = $this->adminWithPermissions([]);
        $target = User::factory()->admin()->create();
        $address = UserAddress::factory()->for($target)->create([
            'admin_area_id' => $this->postalCode()->id,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$target->id}/addresses/{$address->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function show_returns_single_address_with_hierarchy(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();
        $address = UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
            'address_line1' => 'Single fetch',
        ]);

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$target->id}/addresses/{$address->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $address->id)
            ->assertJsonPath('data.admin_area_id', $area->id)
            ->assertJsonPath('data.address_line1', 'Single fetch');

        $codes = collect($response->json('data.hierarchy'))->pluck('code')->all();
        $this->assertSame(['DHAKA', 'DHAKA-DHAKA', 'DHAKA-DHAKA-DEMRA', '1360'], $codes);
    }

    #[Test]
    public function show_with_address_belonging_to_other_user_returns_404(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $userA = User::factory()->admin()->create();
        $userB = User::factory()->admin()->create();
        $address = UserAddress::factory()->for($userB)->create([
            'admin_area_id' => $this->postalCode()->id,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$userA->id}/addresses/{$address->id}")
            ->assertStatus(404);
    }

    #[Test]
    public function show_for_non_admin_user_returns_404(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $regularUser = User::factory()->create();
        $address = UserAddress::factory()->for($regularUser)->create([
            'admin_area_id' => $this->postalCode()->id,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$regularUser->id}/addresses/{$address->id}")
            ->assertStatus(404);
    }

    #[Test]
    public function store_requires_admins_update_permission(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $target = User::factory()->admin()->create();

        $this->asToken($this->tokenFor($admin))
            ->postJson("/api/admin/admin-users/{$target->id}/addresses", [
                'admin_area_id' => $this->postalCode()->id,
                'label' => UserAddressLabelEnum::Home->value,
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function store_creates_address_and_returns_resource(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        $response = $this->asToken($this->tokenFor($admin))
            ->postJson("/api/admin/admin-users/{$target->id}/addresses", [
                'admin_area_id' => $area->id,
                'label' => UserAddressLabelEnum::Home->value,
                'is_primary' => true,
                'address_line1' => '123 Test Road',
                'latitude' => 23.7115253,
                'longitude' => 90.4111451,
                'notes' => 'Gate code 4321',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $target->id)
            ->assertJsonPath('data.admin_area_id', $area->id)
            ->assertJsonPath('data.label', UserAddressLabelEnum::Home->value)
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath('data.address_line1', '123 Test Road');

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $target->id,
            'admin_area_id' => $area->id,
            'address_line1' => '123 Test Road',
            'is_primary' => true,
        ]);
    }

    #[Test]
    public function store_with_is_primary_demotes_previous_primary(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        $existing = UserAddress::factory()->for($target)->primary()->create([
            'admin_area_id' => $area->id,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->postJson("/api/admin/admin-users/{$target->id}/addresses", [
                'admin_area_id' => $area->id,
                'label' => UserAddressLabelEnum::Work->value,
                'is_primary' => true,
            ])
            ->assertStatus(201);

        $this->assertSame(1, $target->addresses()->where('is_primary', true)->count());
        $this->assertFalse($existing->fresh()->is_primary);
    }

    #[Test]
    public function store_rejects_invalid_admin_area_id(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();

        $this->asToken($this->tokenFor($admin))
            ->postJson("/api/admin/admin-users/{$target->id}/addresses", [
                'admin_area_id' => 99999999,
                'label' => UserAddressLabelEnum::Home->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['admin_area_id']);
    }

    #[Test]
    public function store_rejects_invalid_label_value(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();

        $this->asToken($this->tokenFor($admin))
            ->postJson("/api/admin/admin-users/{$target->id}/addresses", [
                'admin_area_id' => $this->postalCode()->id,
                'label' => 5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['label']);
    }

    #[Test]
    public function store_rejects_out_of_range_latitude(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();

        $this->asToken($this->tokenFor($admin))
            ->postJson("/api/admin/admin-users/{$target->id}/addresses", [
                'admin_area_id' => $this->postalCode()->id,
                'label' => UserAddressLabelEnum::Home->value,
                'latitude' => 95.0,
                'longitude' => 90.0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }

    #[Test]
    public function store_rejects_partial_coordinates(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();

        $this->asToken($this->tokenFor($admin))
            ->postJson("/api/admin/admin-users/{$target->id}/addresses", [
                'admin_area_id' => $this->postalCode()->id,
                'label' => UserAddressLabelEnum::Home->value,
                'latitude' => 23.7,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }

    #[Test]
    public function update_changes_fields_and_keeps_address_owner(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        $address = UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
            'label' => UserAddressLabelEnum::Home,
            'address_line1' => 'Old',
        ]);

        $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/admin-users/{$target->id}/addresses/{$address->id}", [
                'address_line1' => 'New',
                'label' => UserAddressLabelEnum::Work->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.address_line1', 'New')
            ->assertJsonPath('data.label', UserAddressLabelEnum::Work->value);

        $this->assertSame('New', $address->fresh()->address_line1);
        $this->assertSame($target->id, $address->fresh()->user_id);
    }

    #[Test]
    public function update_setting_is_primary_demotes_other_primary(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        $primary = UserAddress::factory()->for($target)->primary()->create([
            'admin_area_id' => $area->id,
        ]);
        $other = UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
            'is_primary' => false,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/admin-users/{$target->id}/addresses/{$other->id}", [
                'is_primary' => true,
            ])
            ->assertOk();

        $this->assertTrue($other->fresh()->is_primary);
        $this->assertFalse($primary->fresh()->is_primary);
    }

    #[Test]
    public function update_with_address_belonging_to_other_user_returns_404(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $userA = User::factory()->admin()->create();
        $userB = User::factory()->admin()->create();
        $area = $this->postalCode();

        $address = UserAddress::factory()->for($userB)->create([
            'admin_area_id' => $area->id,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/admin-users/{$userA->id}/addresses/{$address->id}", [
                'address_line1' => 'Hijack',
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function destroy_deletes_address(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        $address = UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->deleteJson("/api/admin/admin-users/{$target->id}/addresses/{$address->id}")
            ->assertOk();

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    #[Test]
    public function destroy_requires_admins_update_permission(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        $address = UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->deleteJson("/api/admin/admin-users/{$target->id}/addresses/{$address->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function deleting_user_cascades_addresses(): void
    {
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        $address = UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
        ]);

        $target->delete();

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    #[Test]
    public function user_primary_address_relationship_returns_only_primary(): void
    {
        $target = User::factory()->admin()->create();
        $area = $this->postalCode();

        UserAddress::factory()->for($target)->create([
            'admin_area_id' => $area->id,
            'is_primary' => false,
        ]);
        $primary = UserAddress::factory()->for($target)->primary()->create([
            'admin_area_id' => $area->id,
        ]);

        $this->assertSame($primary->id, $target->fresh()->primaryAddress?->id);
    }

    #[Test]
    public function hierarchy_field_returns_full_chain_when_admin_area_loaded(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $target = User::factory()->admin()->create();
        $postal = $this->postalCode('1360'); // DHAKA > DHAKA-DHAKA > DHAKA-DHAKA-DEMRA > 1360

        UserAddress::factory()->for($target)->create([
            'admin_area_id' => $postal->id,
        ]);

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$target->id}/addresses");

        $codes = collect($response->json('data.0.hierarchy'))->pluck('code')->all();
        $this->assertSame(['DHAKA', 'DHAKA-DHAKA', 'DHAKA-DHAKA-DEMRA', '1360'], $codes);
    }
}
