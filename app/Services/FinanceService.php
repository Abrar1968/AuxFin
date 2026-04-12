<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\Project;
use Carbon\Carbon;

class FinanceService
{
    public function projectRevenueSummary(Project $project): array
    {
        $bookedRevenue = (float) $project->invoices()->sum('amount');
        $recognizedRevenue = (float) $project->invoices()->whereNotNull('payment_completed_at')->sum('amount');
        $accountsReceivable = max(0, $bookedRevenue - $recognizedRevenue);

        return [
            'booked_revenue' => round($bookedRevenue, 2),
            'recognized_revenue' => round($recognizedRevenue, 2),
            'accounts_receivable' => round($accountsReceivable, 2),
            'collection_rate_percent' => $bookedRevenue > 0
                ? round(($recognizedRevenue / $bookedRevenue) * 100, 2)
                : 0,
        ];
    }

    public function transitionInvoice(
        Invoice $invoice,
        string $status,
        ?float $partialAmount = null,
        ?string $paidAt = null,
    ): Invoice {
        $updates = [
            'status' => $status,
        ];

        if ($status === 'partial') {
            $updates['partial_amount'] = round((float) $partialAmount, 2);
            $updates['payment_completed_at'] = null;
        } elseif ($status === 'paid') {
            $updates['partial_amount'] = (float) $invoice->amount;
            $updates['payment_completed_at'] = $paidAt
                ? Carbon::parse($paidAt)->toDateTimeString()
                : now();
        } else {
            if ($status !== 'overdue') {
                $updates['partial_amount'] = null;
            }

            $updates['payment_completed_at'] = null;
        }

        $invoice->update($updates);

        return $invoice->fresh(['project.client']);
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
}
