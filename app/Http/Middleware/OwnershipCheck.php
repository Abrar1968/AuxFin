<?php

namespace App\Http\Middleware;

use App\Models\EmployeeMessage;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\SalaryMonth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnershipCheck
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'employee') {
            return $next($request);
        }

        $employee = $user->employee;
        if (! $employee) {
            abort(403, 'Employee profile missing.');
        }

        $targetEmployeeId = $request->route('employee_id')
            ?? $request->input('employee_id')
            ?? optional($request->route('salaryMonth'))->employee_id
            ?? optional($request->route('loan'))->employee_id
            ?? optional($request->route('attendance'))->employee_id
            ?? optional($request->route('leave'))->employee_id
            ?? optional($request->route('employeeMessage'))->employee_id;

        if (! $targetEmployeeId) {
            $targetEmployeeId = $this->resolveEmployeeIdFromRouteResource($request);
        }

        if ($targetEmployeeId && (int) $targetEmployeeId !== (int) $employee->id) {
            abort(403, 'You can only access your own records.');
        }

        return $next($request);
    }

    private function resolveEmployeeIdFromRouteResource(Request $request): ?int
    {
        $id = $request->route('id');
        if (! $id) {
            return null;
        }

        $path = trim($request->path(), '/');

        if (str_contains($path, 'employee/loans/')) {
            return Loan::query()->whereKey((int) $id)->value('employee_id');
        }

        if (str_contains($path, 'employee/messages/')) {
            return EmployeeMessage::query()->whereKey((int) $id)->value('employee_id');
        }

        if (str_contains($path, 'employee/leaves/')) {
            return Leave::query()->whereKey((int) $id)->value('employee_id');
        }

        if (str_contains($path, 'employee/salary/')) {
            return SalaryMonth::query()->whereKey((int) $id)->value('employee_id');
        }

        return null;
    }
}
