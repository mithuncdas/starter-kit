<?php

namespace Tests\Feature\Admin;

use App\Models\AdminArea;
use App\Models\Country;
use Database\Seeders\LocationDataSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(LocationDataSeeder::class);
    }

    #[Test]
    public function listing_countries_requires_locations_view_permission(): void
    {
        $admin = $this->adminWithPermissions([]);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/locations/countries')
            ->assertStatus(403);
    }

    #[Test]
    public function countries_endpoint_returns_only_bangladesh(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/locations/countries');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message',
                'data' => [['id', 'iso2', 'iso3', 'name', 'isd_prefix', 'default_timezone', 'is_active']],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('BD', $response->json('data.0.iso2'));
    }

    #[Test]
    public function structure_endpoint_for_bangladesh_returns_four_levels_in_order(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);
        $bd = Country::query()->where('iso2', 'BD')->firstOrFail();

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/locations/countries/{$bd->id}/structure");

        $response->assertOk()
            ->assertJsonPath('data.iso2', 'BD')
            ->assertJsonCount(4, 'data.structure')
            ->assertJsonPath('data.structure.0.level', 'DIVISION')
            ->assertJsonPath('data.structure.1.level', 'DISTRICT')
            ->assertJsonPath('data.structure.2.level', 'UPAZILA')
            ->assertJsonPath('data.structure.3.level', 'POSTAL_CODE');
    }

    #[Test]
    public function top_level_endpoint_returns_all_divisions_for_bangladesh(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);
        $bd = Country::query()->where('iso2', 'BD')->firstOrFail();

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/locations/countries/{$bd->id}/top-level");

        $response->assertOk();
        $rows = $response->json('data');
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertSame(1, $row['depth']);
            $this->assertSame('DIVISION', $row['level']);
            $this->assertNull($row['parent_id']);
        }

        $codes = collect($rows)->pluck('code')->all();
        $this->assertContains('DHAKA', $codes);
    }

    #[Test]
    public function children_endpoint_returns_districts_under_dhaka_division(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);
        $dhakaDivision = AdminArea::query()->where('code', 'DHAKA')->firstOrFail();

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/locations/areas/{$dhakaDivision->id}/children");

        $response->assertOk();
        $rows = $response->json('data');
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertSame('DISTRICT', $row['level']);
            $this->assertSame(2, $row['depth']);
        }

        $names = collect($rows)->pluck('name')->all();
        $this->assertContains('Dhaka', $names);
    }

    #[Test]
    public function tree_endpoint_for_bangladesh_walks_division_to_postal_code(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);
        $bd = Country::query()->where('iso2', 'BD')->firstOrFail();

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/locations/countries/{$bd->id}/tree");

        $response->assertOk();

        $tree = $response->json('data');
        $this->assertNotEmpty($tree);

        foreach ($tree as $root) {
            $this->assertSame('DIVISION', $root['level']);
            $this->assertSame(1, $root['depth']);
        }

        $dhaka = collect($tree)->firstWhere('code', 'DHAKA');
        $this->assertNotNull($dhaka);

        $district = collect($dhaka['children'])->firstWhere('code', 'DHAKA-DHAKA');
        $this->assertNotNull($district);
        $this->assertSame('DISTRICT', $district['level']);

        $upazila = collect($district['children'])->firstWhere('code', 'DHAKA-DHAKA-DEMRA');
        $this->assertNotNull($upazila);
        $this->assertSame('UPAZILA', $upazila['level']);
        $this->assertSame(3, $upazila['depth']);

        $postal = collect($upazila['children'])->firstWhere('code', '1360');
        $this->assertNotNull($postal);
        $this->assertSame('POSTAL_CODE', $postal['level']);
        $this->assertSame(4, $postal['depth']);
    }

    #[Test]
    public function country_structure_returns_404_for_unknown_country(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/locations/countries/999999/structure')
            ->assertStatus(404);
    }

    #[Test]
    public function country_top_level_returns_404_for_unknown_country(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/locations/countries/999999/top-level')
            ->assertStatus(404);
    }

    #[Test]
    public function country_tree_returns_404_for_unknown_country(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/locations/countries/999999/tree')
            ->assertStatus(404);
    }

    #[Test]
    public function area_children_returns_404_for_unknown_area(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/locations/areas/999999/children')
            ->assertStatus(404);
    }

    #[Test]
    public function tree_endpoint_rejects_out_of_range_max_depth(): void
    {
        $admin = $this->adminWithPermissions(['locations.view']);
        $bd = Country::query()->where('iso2', 'BD')->firstOrFail();
        $token = $this->tokenFor($admin);

        $this->asToken($token)->getJson("/api/admin/locations/countries/{$bd->id}/tree?max_depth=99")
            ->assertStatus(422)->assertJsonValidationErrors(['max_depth']);

        $this->asToken($token)->getJson("/api/admin/locations/countries/{$bd->id}/tree?max_depth=0")
            ->assertStatus(422)->assertJsonValidationErrors(['max_depth']);

        $this->asToken($token)->getJson("/api/admin/locations/countries/{$bd->id}/tree?max_depth=abc")
            ->assertStatus(422)->assertJsonValidationErrors(['max_depth']);
    }
}
