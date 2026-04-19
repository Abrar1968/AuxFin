<?php

namespace App\Http\Controllers\Analytics;

use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Services\ForecastService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AnomalyController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ACCRUAL_INVOICE_STATUSES = ['sent', 'partial', 'paid', 'overdue'];

    public function __construct(private readonly ForecastService $forecastService)
    {
    }

    public function index(): JsonResponse
    {
        $data = Cache::remember('analytics:anomalies:expenses', now()->addMinutes(10), function (): array {
            $expenses = Expense::query()
                ->orderByDesc('expense_date')
                ->limit(36)
                ->pluck('amount')
                ->map(fn ($amount) => (float) $amount)
                ->reverse()
                ->values()
                ->all();

            return $this->forecastService->detectAnomalies($expenses);
        });

        $anomalyCount = collect($data)->filter(fn (array $row): bool => (bool) ($row['is_anomaly'] ?? false))->count();

        event(new InsightStreamed('insight.analytics.anomalies', [
            'scope' => 'analytics',
            'points' => count($data),
            'anomaly_count' => $anomalyCount,
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function arHealth(): JsonResponse
    {
        $data = Cache::remember('analytics:anomalies:ar-health', now()->addMinutes(10), function (): array {
            $today = now()->startOfDay();

            $items = Invoice::query()
                ->withSum('payments as paid_amount', 'amount')
                ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
                ->get()
                ->map(function ($invoice) use ($today) {
                    $outstanding = max(0, (float) $invoice->amount - (float) ($invoice->paid_amount ?? 0));
                    if ($outstanding <= 0) {
                        return null;
                    }

                    $age = (int) Carbon::parse($invoice->due_date)->startOfDay()->diffInDays($today, false);

                    return [
                        'amount' => $outstanding,
                        'bucket' => $this->bucketFromAge($age),
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return $this->forecastService->arHealth($items);
        });

        event(new InsightStreamed('insight.analytics.ar_health', [
            'scope' => 'analytics',
            'health_score' => (float) ($data['score'] ?? 0),
            'health_status' => $data['status'] ?? null,
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    private function bucketFromAge(int $age): string
    {
        if ($age <= 30) {
            return '0_30d';
        }

        if ($age <= 60) {
            return '31_60d';
        }

        if ($age <= 90) {
            return '61_90d';
        }

        return '90plus';
    }
}
