<?php

namespace Tests\Feature\Admin;

use App\Enums\UserAddressLabelEnum;
use App\Models\AdminArea;
use App\Models\User;
use App\Models\UserAddress;
use Chronicle\Entry\Entry;
use Database\Seeders\LocationDataSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class AuditUserAddressTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected User $actor;

    protected string $token;

    protected User $target;

    protected AdminArea $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(LocationDataSeeder::class);

        $this->actor = $this->adminWithPermissions(['admins.view', 'admins.update']);
        $this->token = $this->tokenFor($this->actor);
        $this->target = User::factory()->admin()->create();
        $this->area = AdminArea::query()->where('code', '1360')->firstOrFail();
    }

    #[Test]
    public function creating_address_records_user_address_created(): void
    {
        $this->asToken($this->token)
            ->postJson("/api/admin/admin-users/{$this->target->id}/addresses", [
                'admin_area_id' => $this->area->id,
                'label' => UserAddressLabelEnum::Home->value,
                'is_primary' => true,
                'address_line1' => '123 Main St',
            ])
            ->assertCreated();

        $address = UserAddress::query()->where('user_id', $this->target->id)->firstOrFail();
        $entry = Entry::query()->action('user_address.created')->forSubject($address)->first();

        $this->assertNotNull($entry);
        $this->assertSame($this->target->id, $entry->metadata['user_id']);
        $this->assertTrue($entry->metadata['is_primary']);
    }

    #[Test]
    public function update_records_user_address_updated_with_field_diff(): void
    {
        $address = UserAddress::factory()->for($this->target)->create([
            'admin_area_id' => $this->area->id,
            'address_line1' => 'Old Street',
            'is_primary' => false,
        ]);

        $this->asToken($this->token)
            ->putJson("/api/admin/admin-users/{$this->target->id}/addresses/{$address->id}", [
                'admin_area_id' => $this->area->id,
                'label' => $address->label->value,
                'is_primary' => false,
                'address_line1' => 'New Street',
            ])
            ->assertOk();

        $entry = Entry::query()->action('user_address.updated')->forSubject($address)->first();
        $this->assertNotNull($entry);
        $this->assertSame('Old Street', $entry->diff['address_line1']['old']);
        $this->assertSame('New Street', $entry->diff['address_line1']['new']);
    }

    #[Test]
    public function promoting_to_primary_records_primary_set_with_previous_id(): void
    {
        $primary = UserAddress::factory()->for($this->target)->primary()->create([
            'admin_area_id' => $this->area->id,
        ]);

        $other = UserAddress::factory()->for($this->target)->create([
            'admin_area_id' => $this->area->id,
            'is_primary' => false,
        ]);

        $this->asToken($this->token)
            ->putJson("/api/admin/admin-users/{$this->target->id}/addresses/{$other->id}", [
                'admin_area_id' => $this->area->id,
                'label' => $other->label->value,
                'is_primary' => true,
            ])
            ->assertOk();

        $entry = Entry::query()->action('user_address.primary_set')->forSubject($other)->first();
        $this->assertNotNull($entry);
        $this->assertSame($primary->id, $entry->metadata['previous_primary_address_id']);
    }

    #[Test]
    public function deleting_address_records_user_address_deleted_with_snapshot(): void
    {
        $address = UserAddress::factory()->for($this->target)->primary()->create([
            'admin_area_id' => $this->area->id,
            'label' => UserAddressLabelEnum::Home,
            'address_line1' => 'Snapshot Lane',
        ]);

        $this->asToken($this->token)
            ->deleteJson("/api/admin/admin-users/{$this->target->id}/addresses/{$address->id}")
            ->assertOk();

        $entry = Entry::query()->action('user_address.deleted')->where('subject_id', (string) $address->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame('Snapshot Lane', $entry->metadata['address_line1']);
        $this->assertTrue($entry->metadata['was_primary']);
    }
}
