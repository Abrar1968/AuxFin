<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function index(Request $request, int $projectId): JsonResponse
    {
        Project::query()->findOrFail($projectId);

        $rows = Invoice::query()
            ->with('project.client')
            ->where('project_id', $projectId)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function store(Request $request, int $projectId): JsonResponse
    {
        Project::query()->findOrFail($projectId);

        $payload = $request->validate([
            'invoice_number' => ['required', 'string', 'max:30', 'unique:invoices,invoice_number'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'status' => ['nullable', 'in:draft,sent,partial,paid,overdue'],
            'partial_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($payload['status'] ?? 'draft') === 'partial' && ! array_key_exists('partial_amount', $payload)) {
            return response()->json(['message' => 'partial_amount is required when status is partial.'], 422);
        }

        $invoice = Invoice::query()->create([
            'project_id' => $projectId,
            'invoice_number' => $payload['invoice_number'],
            'amount' => $payload['amount'],
            'due_date' => $payload['due_date'],
            'status' => $payload['status'] ?? 'draft',
            'partial_amount' => $payload['partial_amount'] ?? null,
            'payment_completed_at' => ($payload['status'] ?? null) === 'paid' ? now() : null,
            'notes' => $payload['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Invoice created successfully.',
            'invoice' => $invoice->load('project.client'),
        ], 201);
    }

    public function update(Request $request, int $projectId, int $id): JsonResponse
    {
        Project::query()->findOrFail($projectId);

        $invoice = Invoice::query()
            ->where('project_id', $projectId)
            ->findOrFail($id);

        $payload = $request->validate([
            'invoice_number' => ['sometimes', 'string', 'max:30', 'unique:invoices,invoice_number,'.$invoice->id],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'due_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
            'partial_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoice->update($payload);

        return response()->json([
            'message' => 'Invoice updated successfully.',
            'invoice' => $invoice->fresh()->load('project.client'),
        ]);
    }

    public function transition(Request $request, int $projectId, int $id): JsonResponse
    {
        Project::query()->findOrFail($projectId);

        $invoice = Invoice::query()
            ->where('project_id', $projectId)
            ->findOrFail($id);

        $payload = $request->validate([
            'status' => ['required', 'in:draft,sent,partial,paid,overdue'],
            'partial_amount' => ['nullable', 'numeric', 'gt:0'],
            'paid_at' => ['nullable', 'date'],
        ]);

        if ($payload['status'] === 'partial') {
            if (! array_key_exists('partial_amount', $payload)) {
                return response()->json(['message' => 'partial_amount is required for partial status.'], 422);
            }

            if ((float) $payload['partial_amount'] >= (float) $invoice->amount) {
                return response()->json(['message' => 'partial_amount must be less than invoice amount.'], 422);
            }
        }

        $updated = $this->financeService->transitionInvoice(
            $invoice,
            $payload['status'],
            isset($payload['partial_amount']) ? (float) $payload['partial_amount'] : null,
            $payload['paid_at'] ?? null,
        );

        return response()->json([
            'message' => 'Invoice status updated successfully.',
            'invoice' => $updated,
        ]);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        Project::query()->findOrFail($projectId);

        $invoice = Invoice::query()
            ->where('project_id', $projectId)
            ->findOrFail($id);

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }
}
