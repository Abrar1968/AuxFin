<?php

namespace Database\Seeders;

use App\Models\BusinessOwner;
use App\Models\OwnerEquityEntry;
use App\Models\PublicHoliday;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SettingsAndEquitySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@auxfin.local')->firstOrFail();

        // ─────────────────────────────────────────────
        //  SETTINGS
        // ─────────────────────────────────────────────
        Setting::query()->updateOrCreate(
            ['key' => 'general_settings'],
            ['value' => [
                'company_name'   => 'AuxFin Technologies Ltd',
                'company_email'  => 'finance@auxfin.local',
                'currency'       => 'BDT',
                'timezone'       => 'Asia/Dhaka',
                'available_cash' => 2500000,
            ]]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'loan_policy'],
            ['value' => [
                'max_loan_multiplier'    => 3,
                'max_repayment_months'   => 12,
                'cooling_period_months'  => 3,
                'concurrent_loans'       => 1,
            ]]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'payroll_settings'],
            ['value' => [
                'salary_payment_day' => 28,
                'late_grace_minutes' => 15,
                'overtime_rate'      => 1.5,
            ]]
        );

        // ─────────────────────────────────────────────
        //  PUBLIC HOLIDAYS  (Bangladesh calendar)
        // ─────────────────────────────────────────────
        $year = now()->year;
        $holidays = [
            ['name' => 'International New Year',         'date' => "{$year}-01-01", 'is_optional' => false],
            ['name' => 'Language Martyrs Day',           'date' => "{$year}-02-21", 'is_optional' => false],
            ['name' => 'Independence Day',               'date' => "{$year}-03-26", 'is_optional' => false],
            ['name' => 'Bangla New Year (Pahela Baishakh)', 'date' => "{$year}-04-14", 'is_optional' => false],
            ['name' => 'May Day',                        'date' => "{$year}-05-01", 'is_optional' => false],
            ['name' => 'National Mourning Day',          'date' => "{$year}-08-15", 'is_optional' => false],
            ['name' => 'Victory Day',                    'date' => "{$year}-12-16", 'is_optional' => false],
            ['name' => 'Christmas Day',                  'date' => "{$year}-12-25", 'is_optional' => true],
            // Previous year extras
            ['name' => 'Eid al-Fitr',                   'date' => ($year - 1).'-04-10', 'is_optional' => false],
            ['name' => 'Eid ul-Adha',                   'date' => ($year - 1).'-06-17', 'is_optional' => false],
        ];

        foreach ($holidays as $h) {
            PublicHoliday::query()->updateOrCreate(
                ['date' => $h['date']],
                ['name' => $h['name'], 'is_optional' => $h['is_optional']]
            );
        }

        // ─────────────────────────────────────────────
        //  BUSINESS OWNERS  (total = 100%)
        // ─────────────────────────────────────────────
        $owners = [
            [
                'name'                 => 'Arif Hossain',
                'ownership_percentage' => 50.00,
                'initial_investment'   => 5000000.00,
                'notes'                => 'Founding partner and majority shareholder.',
                'is_active'            => true,
            ],
            [
                'name'                 => 'Rehana Begum',
                'ownership_percentage' => 30.00,
                'initial_investment'   => 3000000.00,
                'notes'                => 'Silent partner, Series A investor.',
                'is_active'            => true,
            ],
            [
                'name'                 => 'Shakhawat Hossen',
                'ownership_percentage' => 20.00,
                'initial_investment'   => 2000000.00,
                'notes'                => 'Operations co-founder.',
                'is_active'            => true,
            ],
        ];

        $ownerModels = [];
        foreach ($owners as $row) {
            $ownerModels[] = BusinessOwner::query()->updateOrCreate(
                ['name' => $row['name']],
                $row
            );
        }

        // ─────────────────────────────────────────────
        //  OWNER EQUITY ENTRIES
        // ─────────────────────────────────────────────
        $base = Carbon::now()->startOfMonth();

        $equityEntries = [
            // Capital contributions – initial investments
            ['owner' => 0, 'type' => 'capital_contribution', 'amount' => 5000000.00,
             'notes' => 'Initial capital injection – Arif Hossain',
             'date'  => $base->copy()->subMonths(12)->toDateString()],
            ['owner' => 1, 'type' => 'capital_contribution', 'amount' => 3000000.00,
             'notes' => 'Initial capital injection – Rehana Begum',
             'date'  => $base->copy()->subMonths(12)->toDateString()],
            ['owner' => 2, 'type' => 'capital_contribution', 'amount' => 2000000.00,
             'notes' => 'Initial capital injection – Shakhawat Hossen',
             'date'  => $base->copy()->subMonths(12)->toDateString()],
            // Mid-year additional contributions
            ['owner' => 0, 'type' => 'capital_contribution', 'amount' => 500000.00,
             'notes' => 'Q2 expansion funding',
             'date'  => $base->copy()->subMonths(6)->toDateString()],
            ['owner' => 1, 'type' => 'capital_contribution', 'amount' => 300000.00,
             'notes' => 'Q2 expansion funding',
             'date'  => $base->copy()->subMonths(6)->toDateString()],
            // Drawings
            ['owner' => 0, 'type' => 'drawing', 'amount' => 150000.00,
             'notes' => 'Monthly director drawing – Arif Hossain',
             'date'  => $base->copy()->subMonths(3)->toDateString()],
            ['owner' => 1, 'type' => 'drawing', 'amount' => 90000.00,
             'notes' => 'Monthly director drawing – Rehana Begum',
             'date'  => $base->copy()->subMonths(3)->toDateString()],
            ['owner' => 2, 'type' => 'drawing', 'amount' => 60000.00,
             'notes' => 'Monthly director drawing – Shakhawat Hossen',
             'date'  => $base->copy()->subMonths(3)->toDateString()],
            // Current month drawings
            ['owner' => 0, 'type' => 'drawing', 'amount' => 150000.00,
             'notes' => 'Current month director drawing – Arif Hossain',
             'date'  => $base->copy()->subMonths(1)->toDateString()],
            ['owner' => 1, 'type' => 'drawing', 'amount' => 90000.00,
             'notes' => 'Current month director drawing – Rehana Begum',
             'date'  => $base->copy()->subMonths(1)->toDateString()],
            ['owner' => 2, 'type' => 'drawing', 'amount' => 60000.00,
             'notes' => 'Current month director drawing – Shakhawat Hossen',
             'date'  => $base->copy()->subMonths(1)->toDateString()],
        ];

        foreach ($equityEntries as $entry) {
            OwnerEquityEntry::query()->updateOrCreate(
                [
                    'business_owner_id' => $ownerModels[$entry['owner']]->id,
                    'entry_date'        => $entry['date'],
                    'entry_type'        => $entry['type'],
                    'amount'            => $entry['amount'],
                ],
                [
                    'notes'       => $entry['notes'],
                    'recorded_by' => $admin->id,
                ]
            );
        }
    }
}
