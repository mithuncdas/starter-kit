<?php

namespace Database\Seeders;

use App\Models\AdminLevel;
use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAreaSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedBangladesh();
    }

    private function seedBangladesh(): void
    {
        $country = Country::query()->where('iso2', 'BD')->first();

        if ($country === null) {
            return;
        }

        $levelIds = AdminLevel::query()->pluck('id', 'code');
        $rows = $this->loadBangladeshData(base_path('data/geo/BD/locations.json'));

        $divisions = [];
        $districts = [];
        $upazilas = [];
        $postalCodes = [];

        foreach ($rows as $row) {
            $division = trim((string) $row['division']);
            $district = trim((string) $row['district']);
            $upazila = trim((string) $row['upazila']);
            $postalCode = trim((string) $row['postal_code']);

            $divCode = $this->slugCode($division);
            $distCode = $divCode.'-'.$this->slugCode($district);
            $upaCode = $distCode.'-'.$this->slugCode($upazila);
            $pcCode = $postalCode;

            $divisions[$divCode] = ['code' => $divCode, 'name' => $division];
            $districts[$distCode] = [
                'code' => $distCode,
                'name' => $district,
                'parent_code' => $divCode,
            ];
            $upazilas[$upaCode] = [
                'code' => $upaCode,
                'name' => $upazila,
                'parent_code' => $distCode,
            ];
            $postalCodes[$pcCode] = [
                'code' => $pcCode,
                'name' => $postalCode,
                'parent_code' => $upaCode,
            ];
        }

        DB::transaction(function () use ($country, $levelIds, $divisions, $districts, $upazilas, $postalCodes): void {
            $divisionIds = $this->upsertLevel(
                $country->id,
                (int) $levelIds['DIVISION'],
                1,
                $divisions,
                [],
            );

            $districtIds = $this->upsertLevel(
                $country->id,
                (int) $levelIds['DISTRICT'],
                2,
                $districts,
                $divisionIds,
            );

            $upazilaIds = $this->upsertLevel(
                $country->id,
                (int) $levelIds['UPAZILA'],
                3,
                $upazilas,
                $districtIds,
            );

            $this->upsertLevel(
                $country->id,
                (int) $levelIds['POSTAL_CODE'],
                4,
                $postalCodes,
                $upazilaIds,
            );
        });
    }

    /**
     * @param  array<string, array{code: string, name: string, parent_code?: string}>  $items
     * @param  array<string, int>  $parentMap
     * @return array<string, int>
     */
    private function upsertLevel(
        int $countryId,
        int $adminLevelId,
        int $depth,
        array $items,
        array $parentMap,
    ): array {
        $now = now();
        $payload = [];

        foreach ($items as $item) {
            $parentId = null;
            if (isset($item['parent_code'])) {
                if (! isset($parentMap[$item['parent_code']])) {
                    continue;
                }
                $parentId = $parentMap[$item['parent_code']];
            }

            $payload[] = [
                'country_id' => $countryId,
                'parent_id' => $parentId,
                'admin_level_id' => $adminLevelId,
                'depth' => $depth,
                'code' => $item['code'],
                'name' => $item['name'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('admin_areas')->upsert(
                $chunk,
                ['country_id', 'admin_level_id', 'code'],
                ['parent_id', 'depth', 'name', 'is_active', 'updated_at'],
            );
        }

        return DB::table('admin_areas')
            ->where('country_id', $countryId)
            ->where('admin_level_id', $adminLevelId)
            ->pluck('id', 'code')
            ->all();
    }

    /**
     * Load the bundled Bangladesh dataset (division, district, upazila, post office, postal code).
     *
     * @return list<array{division: string, district: string, upazila: string, post_office: string, postal_code: string}>
     */
    private function loadBangladeshData(string $file): array
    {
        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function slugCode(string $value): string
    {
        return strtoupper(Str::slug($value));
    }
}
