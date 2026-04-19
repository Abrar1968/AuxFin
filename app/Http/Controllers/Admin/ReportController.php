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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $from = $payload['from_month'] ?? null;
        $to = $payload['to_month'] ?? null;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;

        $cacheKey = sprintf(
            'reports:profit-loss:%s:%s:%s:%s',
            $from ?? 'auto',
            $to ?? 'auto',
            $timeframe,
            $anchorDate ?? 'auto',
        );

        $data = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->reportService->profitLoss($from, $to, $timeframe, $anchorDate),
        );

        event(new InsightStreamed('insight.report.profit_loss', [
            'scope' => 'reports',
            'report' => 'profit_loss',
            'timeframe' => $data['timeframe'] ?? $timeframe,
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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $from = $payload['from_month'] ?? null;
        $to = $payload['to_month'] ?? null;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;

        $cacheKey = sprintf(
            'reports:tax-summary:%s:%s:%s:%s',
            $from ?? 'auto',
            $to ?? 'auto',
            $timeframe,
            $anchorDate ?? 'auto',
        );

        $data = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->reportService->taxSummary($from, $to, $timeframe, $anchorDate),
        );

        event(new InsightStreamed('insight.report.tax_summary', [
            'scope' => 'reports',
            'report' => 'tax_summary',
            'timeframe' => $data['timeframe'] ?? $timeframe,
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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $asOf = $payload['as_of'] ?? null;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;
        $cacheKey = sprintf(
            'reports:ar-aging:%s:%s:%s',
            $asOf ?? 'auto',
            $timeframe,
            $anchorDate ?? 'auto',
        );

        $data = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->reportService->arAging($asOf, $timeframe, $anchorDate),
        );

        event(new InsightStreamed('insight.report.ar_aging', [
            'scope' => 'reports',
            'report' => 'ar_aging',
            'timeframe' => $data['timeframe'] ?? $timeframe,
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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $asOf = $payload['as_of'] ?? null;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;
        $cacheKey = sprintf(
            'reports:trial-balance:%s:%s:%s',
            $asOf ?? 'auto',
            $timeframe,
            $anchorDate ?? 'auto',
        );

        $data = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->reportService->trialBalance($asOf, $timeframe, $anchorDate),
        );

        event(new InsightStreamed('insight.report.trial_balance', [
            'scope' => 'reports',
            'report' => 'trial_balance',
            'timeframe' => $data['timeframe'] ?? $timeframe,
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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $asOf = $payload['as_of'] ?? null;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;
        $cacheKey = sprintf(
            'reports:balance-sheet:%s:%s:%s',
            $asOf ?? 'auto',
            $timeframe,
            $anchorDate ?? 'auto',
        );

        $data = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->reportService->balanceSheet($asOf, $timeframe, $anchorDate),
        );

        event(new InsightStreamed('insight.report.balance_sheet', [
            'scope' => 'reports',
            'report' => 'balance_sheet',
            'timeframe' => $data['timeframe'] ?? $timeframe,
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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $from = $payload['from_month'] ?? null;
        $to = $payload['to_month'] ?? null;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;

        $cacheKey = sprintf(
            'reports:cash-flow:%s:%s:%s:%s',
            $from ?? 'auto',
            $to ?? 'auto',
            $timeframe,
            $anchorDate ?? 'auto',
        );

        $data = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->reportService->cashFlow($from, $to, $timeframe, $anchorDate),
        );

        event(new InsightStreamed('insight.report.cash_flow', [
            'scope' => 'reports',
            'report' => 'cash_flow',
            'timeframe' => $data['timeframe'] ?? $timeframe,
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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $fromDate = $payload['from_date'] ?? null;
        $toDate = $payload['to_date'] ?? null;
        $projectId = isset($payload['project_id']) ? (int) $payload['project_id'] : null;
        $invoiceId = isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null;
        $perPage = isset($payload['per_page']) ? (int) $payload['per_page'] : 50;
        $page = isset($payload['page']) ? (int) $payload['page'] : 1;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;

        $data = $this->reportService->generalLedger(
            $fromDate,
            $toDate,
            $projectId,
            $invoiceId,
            $perPage,
            $page,
            $timeframe,
            $anchorDate,
        );

        event(new InsightStreamed('insight.report.general_ledger', [
            'scope' => 'reports',
            'report' => 'general_ledger',
            'timeframe' => $data['timeframe'] ?? $timeframe,
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
            'timeframe' => ['nullable', 'in:day,week,month,year'],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $fromDate = $payload['from_date'] ?? null;
        $toDate = $payload['to_date'] ?? null;
        $projectId = isset($payload['project_id']) ? (int) $payload['project_id'] : null;
        $invoiceId = isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null;
        $perPage = isset($payload['per_page']) ? (int) $payload['per_page'] : 50;
        $page = isset($payload['page']) ? (int) $payload['page'] : 1;
        $timeframe = $payload['timeframe'] ?? 'month';
        $anchorDate = $payload['anchor_date'] ?? null;

        $data = $this->reportService->paymentLedger(
            $fromDate,
            $toDate,
            $projectId,
            $invoiceId,
            $perPage,
            $page,
            $timeframe,
            $anchorDate,
        );

        event(new InsightStreamed('insight.report.payment_ledger', [
            'scope' => 'reports',
            'report' => 'payment_ledger',
            'timeframe' => $data['timeframe'] ?? $timeframe,
            'from' => $data['from'] ?? $fromDate,
            'to' => $data['to'] ?? $toDate,
            'payment_count' => count($data['entries'] ?? []),
            'total_amount' => (float) ($data['summary']['total_amount'] ?? 0),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }
}
