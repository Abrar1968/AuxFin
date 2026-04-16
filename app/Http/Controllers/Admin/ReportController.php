<?php

namespace App\Http\Controllers\Admin;

use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from_month' => ['nullable', 'date'],
            'to_month' => ['nullable', 'date'],
        ]);

        $from = $payload['from_month'] ?? null;
        $to = $payload['to_month'] ?? null;

        $cacheKey = sprintf('reports:profit-loss:%s:%s', $from ?? 'auto', $to ?? 'auto');

        $data = Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->reportService->profitLoss($from, $to));

        event(new InsightStreamed('insight.report.profit_loss', [
            'scope' => 'reports',
            'report' => 'profit_loss',
            'from' => $data['from'] ?? $from,
            'to' => $data['to'] ?? $to,
            'row_count' => count($data['rows'] ?? []),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function taxSummary(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from_month' => ['nullable', 'date'],
            'to_month' => ['nullable', 'date'],
        ]);

        $from = $payload['from_month'] ?? null;
        $to = $payload['to_month'] ?? null;

        $cacheKey = sprintf('reports:tax-summary:%s:%s', $from ?? 'auto', $to ?? 'auto');

        $data = Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->reportService->taxSummary($from, $to));

        event(new InsightStreamed('insight.report.tax_summary', [
            'scope' => 'reports',
            'report' => 'tax_summary',
            'from' => $data['from'] ?? $from,
            'to' => $data['to'] ?? $to,
            'tax_rate_percent' => (float) ($data['tax_rate_percent'] ?? 0),
            'row_count' => count($data['rows'] ?? []),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function arAging(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        $asOf = $payload['as_of'] ?? null;
        $cacheKey = sprintf('reports:ar-aging:%s', $asOf ?? now()->toDateString());

        $data = Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->reportService->arAging($asOf));

        event(new InsightStreamed('insight.report.ar_aging', [
            'scope' => 'reports',
            'report' => 'ar_aging',
            'as_of' => $data['as_of'] ?? $asOf,
            'total_outstanding' => (float) ($data['total_outstanding'] ?? 0),
            'health_score' => (float) ($data['health']['score'] ?? 0),
            'health_status' => $data['health']['status'] ?? null,
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        $asOf = $payload['as_of'] ?? null;
        $cacheKey = sprintf('reports:trial-balance:%s', $asOf ?? now()->toDateString());

        $data = Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->reportService->trialBalance($asOf));

        event(new InsightStreamed('insight.report.trial_balance', [
            'scope' => 'reports',
            'report' => 'trial_balance',
            'as_of' => $data['as_of'] ?? $asOf,
            'total_debit' => (float) ($data['totals']['debit'] ?? 0),
            'total_credit' => (float) ($data['totals']['credit'] ?? 0),
            'is_balanced' => (bool) ($data['is_balanced'] ?? false),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        $asOf = $payload['as_of'] ?? null;
        $cacheKey = sprintf('reports:balance-sheet:%s', $asOf ?? now()->toDateString());

        $data = Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->reportService->balanceSheet($asOf));

        event(new InsightStreamed('insight.report.balance_sheet', [
            'scope' => 'reports',
            'report' => 'balance_sheet',
            'as_of' => $data['as_of'] ?? $asOf,
            'assets_total' => (float) ($data['totals']['assets'] ?? 0),
            'liabilities_total' => (float) ($data['totals']['liabilities'] ?? 0),
            'equity_total' => (float) ($data['totals']['equity'] ?? 0),
            'is_balanced' => (bool) ($data['is_balanced'] ?? false),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from_month' => ['nullable', 'date'],
            'to_month' => ['nullable', 'date'],
        ]);

        $from = $payload['from_month'] ?? null;
        $to = $payload['to_month'] ?? null;

        $cacheKey = sprintf('reports:cash-flow:%s:%s', $from ?? 'auto', $to ?? 'auto');

        $data = Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->reportService->cashFlow($from, $to));

        event(new InsightStreamed('insight.report.cash_flow', [
            'scope' => 'reports',
            'report' => 'cash_flow',
            'from' => $data['from'] ?? $from,
            'to' => $data['to'] ?? $to,
            'net_cash_flow' => (float) ($data['totals']['net_cash_flow'] ?? 0),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function generalLedger(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $fromDate = $payload['from_date'] ?? null;
        $toDate = $payload['to_date'] ?? null;
        $projectId = isset($payload['project_id']) ? (int) $payload['project_id'] : null;
        $invoiceId = isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null;
        $perPage = isset($payload['per_page']) ? (int) $payload['per_page'] : 50;
        $page = isset($payload['page']) ? (int) $payload['page'] : 1;

        $data = $this->reportService->generalLedger($fromDate, $toDate, $projectId, $invoiceId, $perPage, $page);

        event(new InsightStreamed('insight.report.general_ledger', [
            'scope' => 'reports',
            'report' => 'general_ledger',
            'from' => $data['from'] ?? $fromDate,
            'to' => $data['to'] ?? $toDate,
            'entry_count' => count($data['entries'] ?? []),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function paymentLedger(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $fromDate = $payload['from_date'] ?? null;
        $toDate = $payload['to_date'] ?? null;
        $projectId = isset($payload['project_id']) ? (int) $payload['project_id'] : null;
        $invoiceId = isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null;
        $perPage = isset($payload['per_page']) ? (int) $payload['per_page'] : 50;
        $page = isset($payload['page']) ? (int) $payload['page'] : 1;

        $data = $this->reportService->paymentLedger($fromDate, $toDate, $projectId, $invoiceId, $perPage, $page);

        event(new InsightStreamed('insight.report.payment_ledger', [
            'scope' => 'reports',
            'report' => 'payment_ledger',
            'from' => $data['from'] ?? $fromDate,
            'to' => $data['to'] ?? $toDate,
            'payment_count' => count($data['entries'] ?? []),
            'total_amount' => (float) ($data['summary']['total_amount'] ?? 0),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }
}
