<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = Department::query()
            ->with(['head.user:id,name,email'])
            ->withCount('employees')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->query('search');
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:departments,name'],
            'head_id' => ['nullable', 'exists:employees,id'],
        ]);

        $department = Department::query()->create($payload);

        return response()->json([
            'message' => 'Department created successfully.',
            'department' => $department->load('head.user:id,name,email'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $department = Department::query()
            ->with([
                'head.user:id,name,email',
                'employees.user:id,name,email',
            ])
            ->withCount('employees')
            ->findOrFail($id);

        return response()->json($department);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $department = Department::query()->findOrFail($id);

        $payload = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:150',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'head_id' => ['nullable', 'exists:employees,id'],
        ]);

        $department->update($payload);

        return response()->json([
            'message' => 'Department updated successfully.',
            'department' => $department->fresh()->load('head.user:id,name,email'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $department = Department::query()->findOrFail($id);
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully.']);
    }
}
