<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $countries = [
            ['iso2' => 'BD', 'iso3' => 'BGD', 'name' => 'Bangladesh', 'isd_prefix' => '+880', 'default_timezone' => 'Asia/Dhaka'],
        ];

        foreach ($countries as $row) {
            Country::query()->updateOrCreate(
                ['iso2' => $row['iso2']],
                $row + ['is_active' => true],
            );
        }
    }
}
