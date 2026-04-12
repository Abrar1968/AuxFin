<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'owner@finerp.local',
        ], [
            'name' => 'FinERP Owner',
            'passkey' => 'Owner#2026',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        User::query()->updateOrCreate([
            'email' => 'admin@finerp.local',
        ], [
            'name' => 'FinERP Admin',
            'passkey' => 'Admin#2026',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->call([
            DemoEmployeesSeeder::class,
            Phase3DemoSeeder::class,
            Phase4LoanDemoSeeder::class,
        ]);
    }
}
