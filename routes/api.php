<?php

use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\AssetController as AdminAssetController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\ExpenseController as AdminExpenseController;
use App\Http\Controllers\Admin\FinanceOverviewController as AdminFinanceOverviewController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\LeaveController as AdminLeaveController;
use App\Http\Controllers\Admin\LiabilityController as AdminLiabilityController;
use App\Http\Controllers\Admin\LoanController as AdminLoanController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\PayrollController as AdminPayrollController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Analytics\AnomalyController;
use App\Http\Controllers\Analytics\ForecastController;
use App\Http\Controllers\Analytics\GrowthController;
use App\Http\Controllers\Analytics\OverviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\Employee\LoanController as EmployeeLoanController;
use App\Http\Controllers\Employee\MessageController as EmployeeMessageController;
use App\Http\Controllers\Employee\SalaryController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-passkey', [AuthController::class, 'changePasskey']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::apiResource('employees', AdminEmployeeController::class);
        Route::post('employees/{id}/reset-passkey', [AdminEmployeeController::class, 'resetPasskey']);

        Route::get('finance/overview', [AdminFinanceOverviewController::class, 'index']);

        Route::apiResource('clients', AdminClientController::class);
        Route::apiResource('projects', AdminProjectController::class);
        Route::get('projects/{id}/revenue', [AdminProjectController::class, 'revenue']);

        Route::get('projects/{projectId}/invoices', [AdminInvoiceController::class, 'index']);
        Route::post('projects/{projectId}/invoices', [AdminInvoiceController::class, 'store']);
        Route::put('projects/{projectId}/invoices/{id}', [AdminInvoiceController::class, 'update']);
        Route::delete('projects/{projectId}/invoices/{id}', [AdminInvoiceController::class, 'destroy']);
        Route::post('projects/{projectId}/invoices/{id}/status', [AdminInvoiceController::class, 'transition']);

        Route::apiResource('expenses', AdminExpenseController::class);
        Route::get('expenses-summary', [AdminExpenseController::class, 'summary']);

        Route::apiResource('liabilities', AdminLiabilityController::class);
        Route::post('liabilities/{id}/process-payment', [AdminLiabilityController::class, 'processPayment']);
        Route::get('liabilities-due-soon', [AdminLiabilityController::class, 'dueSoon']);

        Route::apiResource('assets', AdminAssetController::class);
        Route::post('assets/{id}/depreciate', [AdminAssetController::class, 'depreciate']);

        Route::get('payroll/{month}', [AdminPayrollController::class, 'index']);
        Route::post('payroll/process', [AdminPayrollController::class, 'process']);
        Route::post('payroll/bulk-process', [AdminPayrollController::class, 'bulkProcess']);
        Route::put('payroll/{id}', [AdminPayrollController::class, 'update']);
        Route::post('payroll/{id}/mark-paid', [AdminPayrollController::class, 'markPaid']);

        Route::get('loans', [AdminLoanController::class, 'index']);
        Route::get('loans/{id}', [AdminLoanController::class, 'show']);
        Route::post('loans/{id}/approve', [AdminLoanController::class, 'approve']);
        Route::post('loans/{id}/reject', [AdminLoanController::class, 'reject']);

        Route::get('leaves', [AdminLeaveController::class, 'index']);
        Route::post('leaves/{id}/decision', [AdminLeaveController::class, 'decision']);

        Route::get('attendance', [AdminAttendanceController::class, 'index']);
        Route::post('attendance', [AdminAttendanceController::class, 'upsert']);

        Route::get('settings/late-policy', [AdminSettingsController::class, 'getLatePolicy']);
        Route::put('settings/late-policy', [AdminSettingsController::class, 'updateLatePolicy']);
        Route::get('settings/loan-policy', [AdminSettingsController::class, 'getLoanPolicy']);
        Route::put('settings/loan-policy', [AdminSettingsController::class, 'updateLoanPolicy']);
        Route::get('settings/holidays', [AdminSettingsController::class, 'holidays']);
        Route::post('settings/holidays', [AdminSettingsController::class, 'createHoliday']);
        Route::delete('settings/holidays/{id}', [AdminSettingsController::class, 'deleteHoliday']);

        Route::get('messages', [AdminMessageController::class, 'index']);
        Route::post('messages/mark-all-read', [AdminMessageController::class, 'markAllRead']);
        Route::get('messages/{id}', [AdminMessageController::class, 'show']);
        Route::post('messages/{id}/reply', [AdminMessageController::class, 'reply']);
        Route::post('messages/{id}/resolve', [AdminMessageController::class, 'resolve']);
        Route::post('messages/{id}/reject', [AdminMessageController::class, 'reject']);

        Route::get('analytics/overview', [OverviewController::class, 'index']);
        Route::get('analytics/cmgr', [OverviewController::class, 'cmgr']);
        Route::get('analytics/forecast', [ForecastController::class, 'forecast']);
        Route::get('analytics/anomalies', [AnomalyController::class, 'index']);
        Route::get('analytics/burn-rate', [ForecastController::class, 'burnRate']);
        Route::get('analytics/ar-health', [AnomalyController::class, 'arHealth']);
        Route::get('analytics/growth', [GrowthController::class, 'index']);
    });

    Route::middleware(['employee', 'ownership'])->prefix('employee')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::get('salary', [SalaryController::class, 'index']);
        Route::get('salary/{month}/payslip', [SalaryController::class, 'show']);
        Route::get('salary/{month}/payslip/pdf', [SalaryController::class, 'pdf']);

        Route::get('loans', [EmployeeLoanController::class, 'index']);
        Route::get('loans/policy', [EmployeeLoanController::class, 'policy']);
        Route::post('loans/apply', [EmployeeLoanController::class, 'apply']);
        Route::get('loans/{id}', [EmployeeLoanController::class, 'show']);

        Route::get('leaves', [LeaveController::class, 'index']);
        Route::post('leaves/apply', [LeaveController::class, 'apply']);

        Route::get('attendance', [AttendanceController::class, 'index']);

        Route::get('messages', [EmployeeMessageController::class, 'index']);
        Route::post('messages/mark-all-read', [EmployeeMessageController::class, 'markAllRead']);
        Route::post('messages', [EmployeeMessageController::class, 'store']);
        Route::get('messages/{id}', [EmployeeMessageController::class, 'show']);
    });
});
