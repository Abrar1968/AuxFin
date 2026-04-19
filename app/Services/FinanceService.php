<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\Project;
use App\Models\ProjectPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    /**
     * @var array<int, string>
     */
    private const ACCRUAL_INVOICE_STATUSES = ['sent', 'partial', 'paid', 'overdue'];

    public function projectRevenueSummary(Project $project): array
    {
        $accruedRevenue = (float) $project->invoices()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->sum('amount');

        $cashCollected = (float) $project->payments()->sum('amount');
        $accountsReceivable = max(0, $accruedRevenue - $cashCollected);

        return [
            // Keep legacy keys for compatibility while exposing accrual/cash metrics.
            'booked_revenue' => round($accruedRevenue, 2),
            'recognized_revenue' => round($accruedRevenue, 2),
            'accrued_revenue' => round($accruedRevenue, 2),
            'cash_collected' => round($cashCollected, 2),
            'accounts_receivable' => round($accountsReceivable, 2),
            'collection_rate_percent' => $accruedRevenue > 0
                ? round(($cashCollected / $accruedRevenue) * 100, 2)
                : 0,
        ];
    }

    public function transitionInvoice(
        Invoice $invoice,
        string $status,
        ?float $partialAmount = null,
        ?string $paidAt = null,
        ?array $paymentContext = null,
    ): Invoice {
        $project = $invoice->project;

        if (! $project instanceof Project) {
            throw ValidationException::withMessages([
                'project_id' => ['Invoice does not belong to a valid project.'],
            ]);
        }

        if (in_array($status, ['partial', 'paid'], true)) {
            $paymentAmount = $this->resolvePaymentAmountForTransition($invoice, $status, $partialAmount);

            $this->recordProjectPayment(
                $project,
                $invoice,
                [
                    'payment_date' => $paidAt ? Carbon::parse($paidAt)->toDateString() : now()->toDateString(),
                    'amount' => $paymentAmount,
                    'payment_method' => $paymentContext['payment_method'] ?? 'bank_transfer',
                    'reference_number' => $paymentContext['reference_number'] ?? null,
                    'notes' => $paymentContext['notes'] ?? null,
                ],
                $paymentContext['recorded_by'] ?? null,
            );
        }

        if (in_array($status, ['draft', 'sent', 'overdue'], true)) {
            $invoice->update(['status' => $status]);
        }

        return $this->syncInvoicePaymentState($invoice, $status, $paidAt);
    }

    public function recordProjectPayment(
        Project $project,
        ?Invoice $invoice,
        array $payload,
        ?int $recordedBy = null,
    ): ProjectPayment {
        if ($invoice && (int) $invoice->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'invoice_id' => ['Selected invoice does not belong to the provided project.'],
            ]);
        }

        $amount = round((float) ($payload['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Payment amount must be greater than zero.'],
            ]);
        }

        $paymentDate = Carbon::parse((string) ($payload['payment_date'] ?? now()->toDateString()))->toDateString();
        $paymentMethod = (string) ($payload['payment_method'] ?? 'bank_transfer');
        $referenceNumber = $payload['reference_number'] ?? null;
        $notes = $payload['notes'] ?? null;

        return DB::transaction(function () use (
            $project,
            $invoice,
            $amount,
            $recordedBy,
            $paymentDate,
            $paymentMethod,
            $referenceNumber,
            $notes,
        ): ProjectPayment {
            $appliedPaymentId = null;
            $remainingAmount = $amount;

            if ($invoice) {
                $alreadyPaid = round((float) $invoice->payments()->sum('amount'), 2);
                $outstanding = round(max(0, (float) $invoice->amount - $alreadyPaid), 2);

                if ($outstanding <= 0) {
                    throw ValidationException::withMessages([
                        'invoice_id' => ['Invoice is already fully settled.'],
                    ]);
                }

                if ($amount > $outstanding) {
                    throw ValidationException::withMessages([
                        'amount' => ['Payment amount cannot exceed invoice outstanding amount.'],
                    ]);
                }

                $applied = $this->applyProjectAdvancesToInvoice($project, $invoice, $amount, $paymentDate);
                $remainingAmount = (float) $applied['remaining'];
                $appliedPaymentId = $applied['applied_payment_id'];
            }

            $createdPayment = null;
            if ($remainingAmount > 0) {
                $createdPayment = ProjectPayment::query()->create([
                    'project_id' => $project->id,
                    'invoice_id' => $invoice?->id,
                    'recorded_by' => $recordedBy,
                    'payment_date' => $paymentDate,
                    'amount' => round($remainingAmount, 2),
                    'payment_method' => $paymentMethod,
                    'reference_number' => $referenceNumber,
                    'notes' => $notes,
                ]);
            }

            if ($invoice) {
                $this->syncInvoicePaymentState($invoice);
            }

            if ($createdPayment instanceof ProjectPayment) {
                return $createdPayment->fresh(['project.client', 'invoice', 'recorder']);
            }

            if ($appliedPaymentId) {
                return ProjectPayment::query()
                    ->with(['project.client', 'invoice', 'recorder'])
                    ->findOrFail($appliedPaymentId);
            }

            throw ValidationException::withMessages([
                'amount' => ['Unable to apply payment amount.'],
            ]);
        });
    }

    /**
     * @return array{remaining: float, applied_payment_id: int|null}
     */
    private function applyProjectAdvancesToInvoice(Project $project, Invoice $invoice, float $amount, string $paymentDate): array
    {
        $remaining = round($amount, 2);
        $appliedPaymentId = null;

        /** @var \Illuminate\Database\Eloquent\Collection<int, ProjectPayment> $advancePayments */
        $advancePayments = ProjectPayment::query()
            ->where('project_id', $project->id)
            ->whereNull('invoice_id')
            ->where('amount', '>', 0)
            ->whereDate('payment_date', '<=', $paymentDate)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($advancePayments as $advancePayment) {
            if ($remaining <= 0) {
                break;
            }

            $available = round((float) $advancePayment->amount, 2);
            if ($available <= 0) {
                continue;
            }

            $applyAmount = round(min($remaining, $available), 2);

            if ($applyAmount >= $available) {
                $advancePayment->update([
                    'invoice_id' => $invoice->id,
                ]);

                $appliedPaymentId = (int) $advancePayment->id;
            } else {
                $advancePayment->update([
                    'amount' => round($available - $applyAmount, 2),
                ]);

                $linkedAdvance = ProjectPayment::query()->create([
                    'project_id' => $project->id,
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $advancePayment->recorded_by,
                    'payment_date' => optional($advancePayment->payment_date)->toDateString() ?? $paymentDate,
                    'amount' => $applyAmount,
                    'payment_method' => $advancePayment->payment_method,
                    'reference_number' => $advancePayment->reference_number,
                    'notes' => $advancePayment->notes,
                ]);

                $appliedPaymentId = (int) $linkedAdvance->id;
            }

            $remaining = round($remaining - $applyAmount, 2);
        }

        return [
            'remaining' => max(0, $remaining),
            'applied_payment_id' => $appliedPaymentId,
        ];
    }

    public function deleteProjectPayment(ProjectPayment $payment): void
    {
        $invoice = $payment->invoice;
        $payment->delete();

        if ($invoice) {
            $this->syncInvoicePaymentState($invoice);
        }
    }

    /**
     * @return array<int, string>
     */
    public function accrualInvoiceStatuses(): array
    {
        return self::ACCRUAL_INVOICE_STATUSES;
    }

    public function syncInvoicePaymentState(Invoice $invoice, ?string $preferredStatus = null, ?string $paidAt = null): Invoice
    {
        $totalPayments = round((float) $invoice->payments()->sum('amount'), 2);
        $invoiceAmount = round((float) $invoice->amount, 2);

        $updates = [
            'invoice_date' => $invoice->invoice_date ?? optional($invoice->created_at)->toDateString() ?? now()->toDateString(),
        ];

        if ($totalPayments <= 0) {
            $updates['partial_amount'] = null;
            $updates['payment_completed_at'] = null;
            $updates['status'] = in_array($preferredStatus, ['draft', 'sent', 'overdue'], true)
                ? $preferredStatus
                : 'draft';
        } elseif ($totalPayments >= $invoiceAmount) {
            $updates['partial_amount'] = $invoiceAmount;
            $updates['status'] = 'paid';
            $updates['payment_completed_at'] = $paidAt
                ? Carbon::parse($paidAt)->toDateTimeString()
                : ($invoice->payment_completed_at ?? now()->toDateTimeString());
        } else {
            $updates['partial_amount'] = $totalPayments;
            $updates['status'] = 'partial';
            $updates['payment_completed_at'] = null;
        }

        $invoice->update($updates);

        return $invoice->fresh(['project.client', 'payments']);
    }

    public function processLiabilityPayment(Liability $liability, ?float $paymentAmount = null): Liability
    {
        if ($liability->status !== 'active') {
            return $liability;
        }

        $amount = max(0, (float) ($paymentAmount ?? $liability->monthly_payment));
        $remaining = max(0, (float) $liability->outstanding - $amount);

        $baseDueDate = Carbon::parse($liability->next_due_date ?? now()->toDateString());
        $nextDueDate = $remaining > 0 ? $baseDueDate->copy()->addMonth()->toDateString() : null;

        $liability->update([
            'outstanding' => round($remaining, 2),
            'status' => $remaining <= 0 ? 'completed' : 'active',
            'next_due_date' => $nextDueDate,
        ]);

        return $liability->fresh();
    }

    public function liabilityMeta(Liability $liability): array
    {
        $monthlyPayment = (float) $liability->monthly_payment;
        $outstanding = (float) $liability->outstanding;
        $monthsLeft = $monthlyPayment > 0
            ? (int) ceil($outstanding / $monthlyPayment)
            : 0;

        return [
            'months_left' => max(0, $monthsLeft),
            'next_payment_amount' => round(min($monthlyPayment, $outstanding), 2),
        ];
    }

    public function normalizeAssetPayload(array $payload, ?Asset $existing = null): array
    {
        $purchaseCost = (float) ($payload['purchase_cost'] ?? $existing?->purchase_cost ?? 0);
        $usefulLifeMonths = max(1, (int) ($payload['useful_life_months'] ?? $existing?->useful_life_months ?? 1));

        $payload['monthly_depreciation'] = round($purchaseCost / $usefulLifeMonths, 2);

        if (! array_key_exists('current_book_value', $payload)) {
            $payload['current_book_value'] = $existing
                ? (float) $existing->current_book_value
                : $purchaseCost;
        }

        return $payload;
    }

    public function depreciateAsset(Asset $asset): Asset
    {
        if (! in_array($asset->status, ['active', 'fully_depreciated'], true)) {
            return $asset;
        }

        $nextValue = max(0, (float) $asset->current_book_value - (float) $asset->monthly_depreciation);
        $asset->update([
            'current_book_value' => round($nextValue, 2),
            'status' => $nextValue <= 0 ? 'fully_depreciated' : 'active',
        ]);

        return $asset->fresh();
    }

    private function resolvePaymentAmountForTransition(Invoice $invoice, string $status, ?float $partialAmount): float
    {
        $paidSoFar = round((float) $invoice->payments()->sum('amount'), 2);
        $invoiceAmount = round((float) $invoice->amount, 2);
        $outstanding = round(max(0, $invoiceAmount - $paidSoFar), 2);

        if ($outstanding <= 0) {
            throw ValidationException::withMessages([
                'status' => ['Invoice is already fully settled.'],
            ]);
        }

        if ($status === 'paid') {
            return $outstanding;
        }

        $candidate = round((float) ($partialAmount ?? 0), 2);

        if ($candidate <= 0) {
            throw ValidationException::withMessages([
                'partial_amount' => ['Payment amount must be greater than zero.'],
            ]);
        }

        if ($candidate >= $outstanding) {
            throw ValidationException::withMessages([
                'partial_amount' => ['Partial payment must be less than invoice outstanding amount.'],
            ]);
        }

        return $candidate;
    }
}
