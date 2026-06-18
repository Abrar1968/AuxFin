<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayrollRequest;
use App\Jobs\BulkProcessPayrollJob;
use App\Jobs\ProcessPayrollJob;
use App\Models\Employee;
use App\Models\SalaryMonth;
use App\Services\PaginationResponse;
use App\Services\PayrollProcessingService;
use Illuminate\Http\JsonResponse;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollProcessingService $payrollProcessingService,
    ) {
    }

    public function index(PayrollRequest $request, string $month): JsonResponse
    {
        $rows = $this->payrollProcessingService->listMonth(
            $month,
            $request->integer('per_page', 20),
        );

        return response()->json([
            'data' => $rows->items(),
            'meta' => PaginationResponse::meta($rows),
            'message' => 'Payroll month fetched successfully.',
        ]);
    }

    public function showPayslip(int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->payrollProcessingService->buildPayslip($id),
            'message' => 'Payslip fetched successfully.',
        ]);
    }

    public function process(PayrollRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $this->payrollProcessingService->ensureProcessable((int) $payload['employee_id'], $payload['month']);

        $employee = Employee::query()->findOrFail($payload['employee_id']);
        ProcessPayrollJob::dispatch(
            $employee->id,
            $payload['month'],
            (int) $request->user()->id,
            $payload,
        );

        return response()->json([
            'data' => [
                'employee_id' => $employee->id,
                'month' => $payload['month'],
                'status' => 'queued',
            ],
            'message' => 'Payroll processing queued successfully.',
        ], 202);
    }

    public function bulkProcess(PayrollRequest $request): JsonResponse
    {
        $payload = $request->validated();
        BulkProcessPayrollJob::dispatch($payload['month'], (int) $request->user()->id);

        return response()->json([
            'data' => [
                'month' => $payload['month'],
                'status' => 'queued',
            ],
            'message' => 'Bulk payroll processing queued successfully.',
        ], 202);
    }

    public function update(PayrollRequest $request, int $id): JsonResponse
    {
        $salaryMonth = SalaryMonth::query()->findOrFail($id);
        $updated = $this->payrollProcessingService->update($salaryMonth, $request->validated());

        return response()->json([
            'data' => $updated,
            'message' => 'Payroll record updated successfully.',
            'salary_month' => $updated,
        ]);
    }

    public function markPaid(int $id): JsonResponse
    {
        $salaryMonth = SalaryMonth::query()->findOrFail($id);
        $updated = $this->payrollProcessingService->markPaid($salaryMonth);

        return response()->json([
            'data' => $updated,
            'message' => 'Salary marked as paid.',
            'salary_month' => $updated,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $salaryMonth = SalaryMonth::query()->findOrFail($id);
        $this->payrollProcessingService->delete($salaryMonth);

        return response()->json([
            'message' => 'Payroll record deleted successfully.',
        ]);
    }
}
