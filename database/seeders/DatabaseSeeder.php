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
        // Normalize legacy seeded identities from earlier FinERP naming.
        $legacyIdentityMap = [
            'owner@finerp.local' => [
                'email' => 'owner@auxfin.local',
                'name' => 'AuxFin Owner',
            ],
            'admin@finerp.local' => [
                'email' => 'admin@auxfin.local',
                'name' => 'AuxFin Admin',
            ],
            'sadia@finerp.local' => [
                'email' => 'sadia@auxfin.local',
            ],
            'fahim@finerp.local' => [
                'email' => 'fahim@auxfin.local',
            ],
            'nabila@finerp.local' => [
                'email' => 'nabila@auxfin.local',
            ],
        ];

        foreach ($legacyIdentityMap as $legacyEmail => $target) {
            $legacyUser = User::query()->where('email', $legacyEmail)->first();

            if (! $legacyUser) {
                continue;
            }

            $targetEmail = $target['email'];
            $hasTargetConflict = User::query()
                ->where('email', $targetEmail)
                ->where('id', '!=', $legacyUser->id)
                ->exists();

            if (! $hasTargetConflict) {
                $legacyUser->email = $targetEmail;
            }

            if (isset($target['name'])) {
                $legacyUser->name = $target['name'];
            } elseif (str_contains($legacyUser->name, 'FinERP')) {
                $legacyUser->name = str_replace('FinERP', 'AuxFin', $legacyUser->name);
            }

            $legacyUser->save();
        }

        User::query()->updateOrCreate([
            'email' => 'owner@auxfin.local',
        ], [
            'name' => 'AuxFin Owner',
            'passkey' => 'Owner#2026',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        User::query()->updateOrCreate([
            'email' => 'admin@auxfin.local',
        ], [
            'name' => 'AuxFin Admin',
            'passkey' => 'Admin#2026',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->call([
            DemoEmployeesSeeder::class,
            Phase3DemoSeeder::class,
            Phase4LoanDemoSeeder::class,
            Phase5OperationsDemoSeeder::class,
        ]);
    }
}
