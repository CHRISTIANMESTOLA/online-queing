<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offices = [
            ['name' => 'Registrar', 'prefix' => 'REG'],
            ['name' => 'Cashier', 'prefix' => 'CAS'],
            ['name' => 'Clinic', 'prefix' => 'CLI'],
            ['name' => 'Guidance', 'prefix' => 'GUI'],
            ['name' => 'Library', 'prefix' => 'LIB'],
        ];

        foreach ($offices as $officeData) {
            Office::query()->updateOrCreate(
                ['prefix' => $officeData['prefix']],
                [
                    'name' => $officeData['name'],
                    'is_active' => true,
                ],
            );
        }
    }
}
