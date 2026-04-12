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
}
