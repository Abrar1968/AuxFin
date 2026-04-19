<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\SalaryMonth;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $rows = SalaryMonth::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('month')
            ->paginate($request->integer('per_page', 12));

        return response()->json($rows);
    }

    public function show(Request $request, string $month): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $monthDate = Carbon::parse($month)->startOfMonth()->toDateString();

        $salaryMonth = SalaryMonth::query()
            ->where('employee_id', $employee->id)
            ->whereDate('month', $monthDate)
            ->firstOrFail();

        /** @var SalaryMonth $salaryMonth */

        return response()->json($this->payrollService->buildPayslipPayload($salaryMonth));
    }

    public function pdf(Request $request, string $month): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $monthDate = Carbon::parse($month)->startOfMonth()->toDateString();

        $salaryMonth = SalaryMonth::query()
            ->where('employee_id', $employee->id)
            ->whereDate('month', $monthDate)
            ->firstOrFail();

        /** @var SalaryMonth $salaryMonth */

        $payload = $this->payrollService->buildPayslipPayload($salaryMonth);

        return response()->json([
            'message' => 'Return structured payslip payload for JS PDF generation.',
            'filename' => sprintf('payslip-%s-%s.pdf', $employee->employee_code, Carbon::parse($monthDate)->format('Y-m')),
            'payslip' => $payload,
        ]);
    }
}
