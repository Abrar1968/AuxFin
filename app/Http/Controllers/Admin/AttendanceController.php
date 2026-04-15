<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
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
        $payload = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['nullable', 'date'],
        ]);

        $employee = Employee::query()->findOrFail($payload['employee_id']);
        $month = Carbon::parse((string) ($payload['month'] ?? now()->toDateString()))->startOfMonth();

        $records = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderBy('date')
            ->get();

        return response()->json([
            'month' => $month->toDateString(),
            'employee' => $employee->load(['user', 'department']),
            'records' => $records,
            'summary' => $this->payrollService->getAttendanceSummary($employee, $month->toDateString()),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,weekly_off,holiday'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'is_late' => ['nullable', 'boolean'],
            'late_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        $isLate = array_key_exists('is_late', $payload)
            ? (bool) $payload['is_late']
            : $payload['status'] === 'late';

        $record = Attendance::query()->updateOrCreate(
            [
                'employee_id' => $payload['employee_id'],
                'date' => $payload['date'],
            ],
            [
                'status' => $payload['status'],
                'check_in' => $payload['check_in'] ?? null,
                'check_out' => $payload['check_out'] ?? null,
                'is_late' => $isLate,
                'late_minutes' => $payload['late_minutes'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Attendance record saved successfully.',
            'record' => $record,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = Attendance::query()
            ->with(['employee.user', 'employee.department'])
            ->findOrFail($id);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Attendance::query()->findOrFail($id);
        $record->delete();

        return response()->json([
            'message' => 'Attendance record deleted successfully.',
        ]);
    }
}
