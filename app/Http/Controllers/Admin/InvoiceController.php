<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\FinanceService;
use Carbon\Carbon;
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
            'invoice_number' => ['nullable', 'string', 'max:30', 'unique:invoices,invoice_number'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['nullable', 'in:draft,sent,partial,paid,overdue'],
            'partial_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($payload['status'] ?? 'draft') === 'partial' && ! array_key_exists('partial_amount', $payload)) {
            return response()->json(['message' => 'partial_amount is required when status is partial.'], 422);
        }

        $requestedStatus = $payload['status'] ?? 'draft';
        $invoiceDate = $payload['invoice_date'] ?? now()->toDateString();
        $invoiceNumber = $payload['invoice_number'] ?? $this->financeService->generateInvoiceNumber(Carbon::parse($invoiceDate));

        $invoice = Invoice::query()->create([
            'project_id' => $projectId,
            'invoice_number' => $invoiceNumber,
            'amount' => $payload['amount'],
            'invoice_date' => $invoiceDate,
            'due_date' => $payload['due_date'],
            'status' => in_array($requestedStatus, ['draft', 'sent', 'overdue'], true) ? $requestedStatus : 'draft',
            'partial_amount' => null,
            'payment_completed_at' => null,
            'notes' => $payload['notes'] ?? null,
        ]);

        if (in_array($requestedStatus, ['partial', 'paid'], true)) {
            $invoice = $this->financeService->transitionInvoice(
                $invoice,
                $requestedStatus,
                isset($payload['partial_amount']) ? (float) $payload['partial_amount'] : null,
                $payload['paid_at'] ?? null,
            );
        } else {
            $invoice = $this->financeService->syncInvoicePaymentState($invoice, $requestedStatus);
        }

        return response()->json([
            'message' => 'Invoice created successfully.',
            'invoice' => $invoice->load('project.client', 'payments'),
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
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice->update($payload);
        $invoice = $this->financeService->syncInvoicePaymentState($invoice);

        return response()->json([
            'message' => 'Invoice updated successfully.',
            'invoice' => $invoice->load('project.client', 'payments'),
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
