<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminLevelSeeder::class,
            CountrySeeder::class,
            CountryAdminStructureSeeder::class,
            AdminAreaSeeder::class,
        ]);
    }
}
