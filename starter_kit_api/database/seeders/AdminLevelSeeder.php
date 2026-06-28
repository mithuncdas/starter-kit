<?php

namespace Database\Seeders;

use App\Models\AdminLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminLevelSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $levels = [
            ['code' => 'DIVISION', 'name' => 'Division'],
            ['code' => 'DISTRICT', 'name' => 'District'],
            ['code' => 'UPAZILA', 'name' => 'Upazila'],
            ['code' => 'POSTAL_CODE', 'name' => 'Postal Code'],
        ];

        foreach ($levels as $row) {
            AdminLevel::query()->updateOrCreate(
                ['code' => $row['code']],
                $row,
            );
        }
    }
}
