<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceLeaveSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = User::query()->where('email', 'admin@auxfin.local')->firstOrFail();
        $employees = Employee::query()->with('user')->get();

        // ─────────────────────────────────────────────
        //  ATTENDANCE – 3 full months history per employee
        // ─────────────────────────────────────────────
        $monthOffsets = [2, 1, 0]; // 2 months ago → last month → current month

        foreach ($employees as $emp) {
            $offDays    = (array) ($emp->weekly_off_days ?? []);
            $lateCounter = 0;

            foreach ($monthOffsets as $offset) {
                $monthStart   = Carbon::now()->startOfMonth()->subMonths($offset);
                $daysInMonth  = $monthStart->daysInMonth;

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $monthStart->copy()->day($day);

                    // Skip future dates
                    if ($date->isFuture()) {
                        continue;
                    }

                    $dayName = strtolower($date->englishDayOfWeek);

                    // Weekly off
                    if (in_array($dayName, $offDays, true)) {
                        Attendance::query()->updateOrCreate(
                            ['employee_id' => $emp->id, 'date' => $date->toDateString()],
                            ['status' => 'weekly_off', 'check_in' => null, 'check_out' => null,
                             'is_late' => false, 'late_minutes' => null]
                        );
                        continue;
                    }

                    // Every 6th working day = late; every 15th = absent
                    $lateCounter++;
                    $isAbsent = ($lateCounter % 18 === 0);
                    $isLate   = (!$isAbsent && $lateCounter % 6 === 0);

                    if ($isAbsent) {
                        Attendance::query()->updateOrCreate(
                            ['employee_id' => $emp->id, 'date' => $date->toDateString()],
                            ['status' => 'absent', 'check_in' => null, 'check_out' => null,
                             'is_late' => false, 'late_minutes' => null]
                        );
                    } elseif ($isLate) {
                        Attendance::query()->updateOrCreate(
                            ['employee_id' => $emp->id, 'date' => $date->toDateString()],
                            ['status' => 'late', 'check_in' => '09:32:00', 'check_out' => '18:10:00',
                             'is_late' => true, 'late_minutes' => 32]
                        );
                    } else {
                        Attendance::query()->updateOrCreate(
                            ['employee_id' => $emp->id, 'date' => $date->toDateString()],
                            ['status' => 'present', 'check_in' => '09:02:00', 'check_out' => '18:05:00',
                             'is_late' => false, 'late_minutes' => null]
                        );
                    }
                }
            }
        }

        // ─────────────────────────────────────────────
        //  LEAVES
        // ─────────────────────────────────────────────
        $base = Carbon::now()->startOfMonth();

        $leaveDefinitions = [
            // EMP-0101 Sadia
            'EMP-0101' => [
                [
                    'from_date'   => $base->copy()->subMonths(2)->addDays(8)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(2)->addDays(9)->toDateString(),
                    'leave_type'  => 'casual',
                    'days'        => 2,
                    'reason'      => 'Family engagement',
                    'status'      => 'approved',
                    'admin_note'  => 'Approved – planned in advance',
                    'reviewed_by' => 'admin',
                ],
                [
                    'from_date'   => $base->copy()->subMonths(1)->addDays(5)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(1)->addDays(5)->toDateString(),
                    'leave_type'  => 'sick',
                    'days'        => 1,
                    'reason'      => 'Medical appointment',
                    'status'      => 'approved',
                    'admin_note'  => 'Approved with medical certificate',
                    'reviewed_by' => 'admin',
                ],
                [
                    'from_date'   => $base->copy()->addDays(20)->toDateString(),
                    'to_date'     => $base->copy()->addDays(21)->toDateString(),
                    'leave_type'  => 'earned',
                    'days'        => 2,
                    'reason'      => 'Personal event',
                    'status'      => 'pending',
                    'admin_note'  => null,
                    'reviewed_by' => null,
                ],
            ],
            // EMP-0102 Fahim
            'EMP-0102' => [
                [
                    'from_date'   => $base->copy()->subMonths(3)->addDays(12)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(3)->addDays(12)->toDateString(),
                    'leave_type'  => 'sick',
                    'days'        => 1,
                    'reason'      => 'Fever and rest',
                    'status'      => 'approved',
                    'admin_note'  => 'Approved',
                    'reviewed_by' => 'admin',
                ],
                [
                    'from_date'   => $base->copy()->subMonths(1)->addDays(15)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(1)->addDays(15)->toDateString(),
                    'leave_type'  => 'unpaid',
                    'days'        => 1,
                    'reason'      => 'Personal emergency',
                    'status'      => 'rejected',
                    'admin_note'  => 'Policy restriction – unpaid leave requires prior approval',
                    'reviewed_by' => 'admin',
                ],
                [
                    'from_date'   => $base->copy()->addDays(18)->toDateString(),
                    'to_date'     => $base->copy()->addDays(19)->toDateString(),
                    'leave_type'  => 'casual',
                    'days'        => 2,
                    'reason'      => 'Travel',
                    'status'      => 'pending',
                    'admin_note'  => null,
                    'reviewed_by' => null,
                ],
            ],
            // EMP-0103 Nabila
            'EMP-0103' => [
                [
                    'from_date'   => $base->copy()->subMonths(4)->addDays(6)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(4)->addDays(8)->toDateString(),
                    'leave_type'  => 'earned',
                    'days'        => 3,
                    'reason'      => 'Annual leave',
                    'status'      => 'approved',
                    'admin_note'  => 'Approved – annual entitlement',
                    'reviewed_by' => 'admin',
                ],
                [
                    'from_date'   => $base->copy()->subMonths(2)->addDays(3)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(2)->addDays(3)->toDateString(),
                    'leave_type'  => 'sick',
                    'days'        => 1,
                    'reason'      => 'Migraine',
                    'status'      => 'approved',
                    'admin_note'  => 'Approved',
                    'reviewed_by' => 'admin',
                ],
            ],
            // EMP-0104 Karim
            'EMP-0104' => [
                [
                    'from_date'   => $base->copy()->subMonths(1)->addDays(10)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(1)->addDays(11)->toDateString(),
                    'leave_type'  => 'casual',
                    'days'        => 2,
                    'reason'      => 'Wedding ceremony',
                    'status'      => 'approved',
                    'admin_note'  => 'Approved',
                    'reviewed_by' => 'admin',
                ],
                [
                    'from_date'   => $base->copy()->addDays(14)->toDateString(),
                    'to_date'     => $base->copy()->addDays(14)->toDateString(),
                    'leave_type'  => 'sick',
                    'days'        => 1,
                    'reason'      => 'Not feeling well',
                    'status'      => 'pending',
                    'admin_note'  => null,
                    'reviewed_by' => null,
                ],
            ],
            // EMP-0105 Tania
            'EMP-0105' => [
                [
                    'from_date'   => $base->copy()->subMonths(2)->addDays(20)->toDateString(),
                    'to_date'     => $base->copy()->subMonths(2)->addDays(20)->toDateString(),
                    'leave_type'  => 'casual',
                    'days'        => 1,
                    'reason'      => 'Bank work',
                    'status'      => 'approved',
                    'admin_note'  => 'Approved',
                    'reviewed_by' => 'admin',
                ],
            ],
        ];

        foreach ($leaveDefinitions as $empCode => $leaves) {
            $emp = Employee::query()->where('employee_code', $empCode)->firstOrFail();

            foreach ($leaves as $l) {
                Leave::query()->updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'from_date'   => $l['from_date'],
                        'to_date'     => $l['to_date'],
                    ],
                    [
                        'leave_type'  => $l['leave_type'],
                        'days'        => $l['days'],
                        'reason'      => $l['reason'],
                        'status'      => $l['status'],
                        'admin_note'  => $l['admin_note'],
                        'reviewed_by' => $l['reviewed_by'] === 'admin' ? $admin->id : null,
                        'reviewed_at' => $l['reviewed_by'] === 'admin' ? now()->subDays(rand(2, 10)) : null,
                    ]
                );
            }
        }
    }
}
