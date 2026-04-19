<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $month = Carbon::parse((string) $request->query('month', now()->toDateString()))->startOfMonth();

        $rows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderBy('date')
            ->get();

        $summary = $this->payrollService->getAttendanceSummary($employee, $month->toDateString());

        return response()->json([
            'month' => $month->toDateString(),
            'records' => $rows,
            'summary' => $summary,
        ]);
    }
}
