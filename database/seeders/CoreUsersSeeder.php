<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoreUsersSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────
        //  SYSTEM USERS  (super_admin + admin)
        // ─────────────────────────────────────────────
        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@auxfin.local'],
            [
                'name'       => 'AuxFin Owner',
                'passkey'    => Hash::make('Owner#2026'),
                'role'       => 'super_admin',
                'is_active'  => true,
                'created_by' => null,
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@auxfin.local'],
            [
                'name'       => 'AuxFin Admin',
                'passkey'    => Hash::make('Admin#2026'),
                'role'       => 'admin',
                'is_active'  => true,
                'created_by' => $owner->id,
            ]
        );

        // ─────────────────────────────────────────────
        //  DEPARTMENTS
        // ─────────────────────────────────────────────
        $depts = [];
        foreach (['Engineering', 'Finance', 'Operations', 'Marketing', 'HR'] as $name) {
            $depts[$name] = Department::query()->updateOrCreate(['name' => $name], []);
        }

        // ─────────────────────────────────────────────
        //  EMPLOYEE USER DEFINITIONS
        // ─────────────────────────────────────────────
        $employeeRows = [
            [
                'name'                  => 'Sadia Rahman',
                'email'                 => 'sadia@auxfin.local',
                'passkey'               => 'Sadia#2026',
                'employee_code'         => 'EMP-0101',
                'department'            => 'Engineering',
                'designation'           => 'Senior Software Engineer',
                'date_of_joining'       => '2024-03-10',
                'bank_account_number'   => '1234567890101',
                'bank_name'             => 'Dutch-Bangla Bank',
                'basic_salary'          => 52000.00,
                'house_rent'            => 18000.00,
                'conveyance'            => 5000.00,
                'medical_allowance'     => 3500.00,
                'pf_rate'               => 10.00,
                'tds_rate'              => 5.00,
                'professional_tax'      => 500.00,
                'late_threshold_days'   => 3,
                'late_penalty_type'     => 'full_day',
                'working_days_per_week' => 5,
                'weekly_off_days'       => ['friday', 'saturday'],
            ],
            [
                'name'                  => 'Fahim Chowdhury',
                'email'                 => 'fahim@auxfin.local',
                'passkey'               => 'Fahim#2026',
                'employee_code'         => 'EMP-0102',
                'department'            => 'Finance',
                'designation'           => 'Accounts Executive',
                'date_of_joining'       => '2023-11-01',
                'bank_account_number'   => '1234567890102',
                'bank_name'             => 'BRAC Bank',
                'basic_salary'          => 47000.00,
                'house_rent'            => 16000.00,
                'conveyance'            => 4500.00,
                'medical_allowance'     => 3200.00,
                'pf_rate'               => 10.00,
                'tds_rate'              => 4.00,
                'professional_tax'      => 500.00,
                'late_threshold_days'   => 3,
                'late_penalty_type'     => 'half_day',
                'working_days_per_week' => 6,
                'weekly_off_days'       => ['friday'],
            ],
            [
                'name'                  => 'Nabila Karim',
                'email'                 => 'nabila@auxfin.local',
                'passkey'               => 'Nabila#2026',
                'employee_code'         => 'EMP-0103',
                'department'            => 'Operations',
                'designation'           => 'Operations Analyst',
                'date_of_joining'       => '2022-08-15',
                'bank_account_number'   => '1234567890103',
                'bank_name'             => 'Trust Bank',
                'basic_salary'          => 44000.00,
                'house_rent'            => 15000.00,
                'conveyance'            => 4000.00,
                'medical_allowance'     => 3000.00,
                'pf_rate'               => 10.00,
                'tds_rate'              => 3.00,
                'professional_tax'      => 500.00,
                'late_threshold_days'   => 3,
                'late_penalty_type'     => 'half_day',
                'working_days_per_week' => 5,
                'weekly_off_days'       => ['friday', 'saturday'],
            ],
            [
                'name'                  => 'Karim Uddin',
                'email'                 => 'karim@auxfin.local',
                'passkey'               => 'Karim#2026',
                'employee_code'         => 'EMP-0104',
                'department'            => 'Marketing',
                'designation'           => 'Marketing Manager',
                'date_of_joining'       => '2023-05-20',
                'bank_account_number'   => '1234567890104',
                'bank_name'             => 'Eastern Bank',
                'basic_salary'          => 55000.00,
                'house_rent'            => 20000.00,
                'conveyance'            => 6000.00,
                'medical_allowance'     => 4000.00,
                'pf_rate'               => 10.00,
                'tds_rate'              => 6.00,
                'professional_tax'      => 500.00,
                'late_threshold_days'   => 3,
                'late_penalty_type'     => 'full_day',
                'working_days_per_week' => 5,
                'weekly_off_days'       => ['friday', 'saturday'],
            ],
            [
                'name'                  => 'Tania Islam',
                'email'                 => 'tania@auxfin.local',
                'passkey'               => 'Tania#2026',
                'employee_code'         => 'EMP-0105',
                'department'            => 'HR',
                'designation'           => 'HR Coordinator',
                'date_of_joining'       => '2024-01-08',
                'bank_account_number'   => '1234567890105',
                'bank_name'             => 'Islami Bank',
                'basic_salary'          => 41000.00,
                'house_rent'            => 14000.00,
                'conveyance'            => 3500.00,
                'medical_allowance'     => 2500.00,
                'pf_rate'               => 10.00,
                'tds_rate'              => 3.00,
                'professional_tax'      => 500.00,
                'late_threshold_days'   => 3,
                'late_penalty_type'     => 'half_day',
                'working_days_per_week' => 5,
                'weekly_off_days'       => ['friday', 'saturday'],
            ],
        ];

        foreach ($employeeRows as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name'       => $row['name'],
                    'passkey'    => Hash::make($row['passkey']),
                    'role'       => 'employee',
                    'is_active'  => true,
                    'created_by' => $admin->id,
                ]
            );

            Employee::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code'         => $row['employee_code'],
                    'department_id'         => $depts[$row['department']]->id,
                    'designation'           => $row['designation'],
                    'date_of_joining'       => $row['date_of_joining'],
                    'bank_account_number'   => $row['bank_account_number'],
                    'bank_name'             => $row['bank_name'],
                    'basic_salary'          => $row['basic_salary'],
                    'house_rent'            => $row['house_rent'],
                    'conveyance'            => $row['conveyance'],
                    'medical_allowance'     => $row['medical_allowance'],
                    'pf_rate'               => $row['pf_rate'],
                    'tds_rate'              => $row['tds_rate'],
                    'professional_tax'      => $row['professional_tax'],
                    'late_threshold_days'   => $row['late_threshold_days'],
                    'late_penalty_type'     => $row['late_penalty_type'],
                    'working_days_per_week' => $row['working_days_per_week'],
                    'weekly_off_days'       => $row['weekly_off_days'],
                ]
            );
        }

        // Assign department heads
        $depts['Engineering']->update(['head_id' => Employee::query()->where('employee_code', 'EMP-0101')->value('id')]);
        $depts['Finance']->update(['head_id'     => Employee::query()->where('employee_code', 'EMP-0102')->value('id')]);
        $depts['Operations']->update(['head_id'  => Employee::query()->where('employee_code', 'EMP-0103')->value('id')]);
        $depts['Marketing']->update(['head_id'   => Employee::query()->where('employee_code', 'EMP-0104')->value('id')]);
        $depts['HR']->update(['head_id'          => Employee::query()->where('employee_code', 'EMP-0105')->value('id')]);
    }
}
