<?php

namespace Database\Seeders;

use App\Models\AdminLevel;
use App\Models\Country;
use App\Models\CountryAdminStructure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountryAdminStructureSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $structures = [
            'BD' => ['DIVISION', 'DISTRICT', 'UPAZILA', 'POSTAL_CODE'],
        ];

        $levelIds = AdminLevel::query()->pluck('id', 'code');

        foreach ($structures as $iso2 => $levels) {
            $country = Country::query()->where('iso2', $iso2)->first();

            if ($country === null) {
                continue;
            }

            foreach ($levels as $index => $code) {
                CountryAdminStructure::query()->updateOrCreate(
                    ['country_id' => $country->id, 'depth' => $index + 1],
                    ['admin_level_id' => $levelIds[$code]],
                );
            }
        }
    }
}
