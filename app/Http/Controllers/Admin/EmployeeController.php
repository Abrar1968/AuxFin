<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employeeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->with(['user', 'department'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = (string) $request->query('search');
                $q->where(function ($sub) use ($search): void {
                    $sub->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($employees);
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        $result = $this->employeeService->create($request->validated(), (int) $request->user()->id);

        return response()->json([
            'message' => 'Employee created successfully.',
            'passkey' => $result['passkey'],
            'employee' => $result['employee']->load(['user', 'department']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $employee = Employee::query()
            ->with([
                'user',
                'department',
                'salaryMonths' => fn ($q) => $q->latest('month')->limit(24),
                'loans',
                'leaveRequests',
            ])
            ->findOrFail($id);

        return response()->json($employee);
    }

    public function update(EmployeeRequest $request, int $id): JsonResponse
    {
        $employee = Employee::query()->with('user')->findOrFail($id);
        $updated = $this->employeeService->update($employee, $request->validated());

        return response()->json([
            'message' => 'Employee updated successfully.',
            'employee' => $updated,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $employee = Employee::query()->with('user')->findOrFail($id);

        $this->employeeService->archive($employee);

        return response()->json(['message' => 'Employee archived successfully.']);
    }

    public function resetPasskey(int $id): JsonResponse
    {
        $employee = Employee::query()->with('user')->findOrFail($id);
        $plainPasskey = $this->employeeService->resetPasskey($employee);

        return response()->json([
            'message' => 'Passkey reset successfully.',
            'passkey' => $plainPasskey,
        ]);
    }

}
