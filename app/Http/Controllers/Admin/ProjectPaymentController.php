<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectPaymentController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function index(Request $request, int $projectId): JsonResponse
    {
        Project::query()->findOrFail($projectId);

        $rows = ProjectPayment::query()
            ->with(['invoice', 'recorder'])
            ->where('project_id', $projectId)
            ->when($request->filled('invoice_id'), fn ($query) => $query->where('invoice_id', $request->integer('invoice_id')))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('payment_date', '>=', $request->query('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('payment_date', '<=', $request->query('to_date')))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::query()->findOrFail($projectId);

        $payload = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = null;
        if (array_key_exists('invoice_id', $payload) && $payload['invoice_id']) {
            $invoice = Invoice::query()
                ->where('project_id', $project->id)
                ->findOrFail((int) $payload['invoice_id']);
        }

        $payment = $this->financeService->recordProjectPayment(
            $project,
            $invoice,
            $payload,
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Project payment recorded successfully.',
            'payment' => $payment,
        ], 201);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        Project::query()->findOrFail($projectId);

        $payment = ProjectPayment::query()
            ->where('project_id', $projectId)
            ->findOrFail($id);

        $this->financeService->deleteProjectPayment($payment);

        return response()->json(['message' => 'Project payment deleted successfully.']);
    }
}
