<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->where('email', 'admin@finerp.local')->first()
            ?? User::query()->where('email', 'owner@finerp.local')->first();

        $departments = [];
        foreach (['Engineering', 'Finance', 'Operations'] as $name) {
            $departments[$name] = Department::query()->updateOrCreate(['name' => $name], []);
        }

        $employees = [
            [
                'name' => 'Sadia Rahman',
                'email' => 'sadia@finerp.local',
                'passkey' => 'Sadia#2026',
                'employee_code' => 'EMP-0101',
                'department' => 'Engineering',
                'designation' => 'Software Engineer',
                'date_of_joining' => '2024-03-10',
                'basic_salary' => 52000,
                'house_rent' => 18000,
                'conveyance' => 5000,
                'medical_allowance' => 3500,
                'pf_rate' => 10,
                'tds_rate' => 5,
                'professional_tax' => 500,
                'working_days_per_week' => 5,
                'weekly_off_days' => ['friday', 'saturday'],
            ],
            [
                'name' => 'Fahim Chowdhury',
                'email' => 'fahim@finerp.local',
                'passkey' => 'Fahim#2026',
                'employee_code' => 'EMP-0102',
                'department' => 'Finance',
                'designation' => 'Accounts Executive',
                'date_of_joining' => '2023-11-01',
                'basic_salary' => 47000,
                'house_rent' => 16000,
                'conveyance' => 4500,
                'medical_allowance' => 3200,
                'pf_rate' => 10,
                'tds_rate' => 4,
                'professional_tax' => 500,
                'working_days_per_week' => 6,
                'weekly_off_days' => ['friday'],
            ],
            [
                'name' => 'Nabila Karim',
                'email' => 'nabila@finerp.local',
                'passkey' => 'Nabila#2026',
                'employee_code' => 'EMP-0103',
                'department' => 'Operations',
                'designation' => 'Operations Analyst',
                'date_of_joining' => '2022-08-15',
                'basic_salary' => 44000,
                'house_rent' => 15000,
                'conveyance' => 4000,
                'medical_allowance' => 3000,
                'pf_rate' => 10,
                'tds_rate' => 3,
                'professional_tax' => 500,
                'working_days_per_week' => 5,
                'weekly_off_days' => ['friday', 'saturday'],
            ],
        ];

        foreach ($employees as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'passkey' => $row['passkey'],
                    'role' => 'employee',
                    'is_active' => true,
                    'created_by' => $creator?->id,
                ]
            );

            Employee::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => $row['employee_code'],
                    'department_id' => $departments[$row['department']]->id,
                    'designation' => $row['designation'],
                    'date_of_joining' => $row['date_of_joining'],
                    'bank_account_number' => '1234567890'.substr($row['employee_code'], -2),
                    'bank_name' => 'Trust Bank',
                    'basic_salary' => $row['basic_salary'],
                    'house_rent' => $row['house_rent'],
                    'conveyance' => $row['conveyance'],
                    'medical_allowance' => $row['medical_allowance'],
                    'pf_rate' => $row['pf_rate'],
                    'tds_rate' => $row['tds_rate'],
                    'professional_tax' => $row['professional_tax'],
                    'late_threshold_days' => 3,
                    'late_penalty_type' => 'full_day',
                    'working_days_per_week' => $row['working_days_per_week'],
                    'weekly_off_days' => $row['weekly_off_days'],
                ]
            );
        }
    }
}
