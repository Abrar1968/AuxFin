<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\SalaryMonth;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $month = Carbon::now()->startOfMonth();
        $year = Carbon::now()->year;

        $currentSalary = SalaryMonth::query()
            ->where('employee_id', $employee->id)
            ->whereDate('month', $month->toDateString())
            ->first();

        $ytdNet = (float) SalaryMonth::query()
            ->where('employee_id', $employee->id)
            ->whereYear('month', $year)
            ->sum('net_payable');

        $ytdDeductions = (float) SalaryMonth::query()
            ->where('employee_id', $employee->id)
            ->whereYear('month', $year)
            ->sum('total_deductions');

        $activeLoan = Loan::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'active'])
            ->orderByDesc('id')
            ->first();

        $attendanceSummary = $this->payrollService->getAttendanceSummary($employee, $month->toDateString());

        return response()->json([
            'current_month_net_salary' => (float) ($currentSalary?->net_payable ?? 0),
            'current_month_status' => $currentSalary?->status ?? 'draft',
            'total_earned_ytd' => round($ytdNet, 2),
            'total_deducted_ytd' => round($ytdDeductions, 2),
            'outstanding_loan_balance' => (float) ($activeLoan?->amount_remaining ?? 0),
            'attendance_summary' => $attendanceSummary,
        ]);
    }
}
