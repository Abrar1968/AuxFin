<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $rows = Project::query()
            ->with('client')
            ->withCount('invoices')
            ->withSum('invoices as booked_revenue', 'amount')
            ->withSum([
                'invoices as recognized_revenue' => fn ($query) => $query->whereNotNull('payment_completed_at'),
            ], 'amount')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->integer('client_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->query('search');
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        $rows->getCollection()->transform(function (Project $project): Project {
            $booked = (float) ($project->booked_revenue ?? 0);
            $recognized = (float) ($project->recognized_revenue ?? 0);

            $project->setAttribute('booked_revenue', round($booked, 2));
            $project->setAttribute('recognized_revenue', round($recognized, 2));
            $project->setAttribute('accounts_receivable', round(max(0, $booked - $recognized), 2));

            return $project;
        });

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'contract_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,completed,on_hold,cancelled'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project = Project::query()->create([
            ...$payload,
            'status' => $payload['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $project->load('client'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::query()
            ->with([
                'client',
                'invoices' => fn ($query) => $query->latest('id'),
            ])
            ->findOrFail($id);

        $summary = $this->financeService->projectRevenueSummary($project);

        return response()->json([
            'project' => $project,
            'revenue' => $summary,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'client_id' => ['sometimes', 'exists:clients,id'],
            'name' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'contract_amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,completed,on_hold,cancelled'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project = Project::query()->findOrFail($id);
        $project->update($payload);

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => $project->fresh()->load('client'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = Project::query()->findOrFail($id);
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully.']);
    }

    public function revenue(int $id): JsonResponse
    {
        $project = Project::query()->with('client')->findOrFail($id);
        $summary = $this->financeService->projectRevenueSummary($project);

        $statusBreakdown = $project->invoices()
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return response()->json([
            'project' => $project,
            'summary' => $summary,
            'status_breakdown' => $statusBreakdown,
        ]);
    }
}
