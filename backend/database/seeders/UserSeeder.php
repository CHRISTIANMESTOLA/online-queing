<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@qserve.local'],
            [
                'name' => 'System Admin',
                'password' => 'password123',
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $registrarStaff = User::query()->updateOrCreate(
            ['email' => 'registrar.staff@qserve.local'],
            [
                'name' => 'Registrar Staff',
                'password' => 'password123',
                'role' => 'staff',
                'is_active' => true,
            ],
        );

        $cashierStaff = User::query()->updateOrCreate(
            ['email' => 'cashier.staff@qserve.local'],
            [
                'name' => 'Cashier Staff',
                'password' => 'password123',
                'role' => 'staff',
                'is_active' => true,
            ],
        );

        $registrar = Office::query()->where('prefix', 'REG')->first();
        $cashier = Office::query()->where('prefix', 'CAS')->first();

        if ($registrar) {
            $registrarStaff->offices()->syncWithoutDetaching([$registrar->id]);
        }

        if ($cashier) {
            $cashierStaff->offices()->syncWithoutDetaching([$cashier->id]);
        }

        $admin->tokens()->delete();
    }
}
