<?php

namespace App\Http\Controllers\Analytics;

use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Support\TimeframeRange;
use App\Services\ForecastService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): JsonResponse
    {
        $timeframe = TimeframeRange::normalize((string) $request->query('timeframe', 'month'));
        $anchorDate = $request->query('anchor_date');
        $points = $request->has('points') ? $request->integer('points') : $this->defaultPoints($timeframe);

        [$periodFrom, $periodTo] = TimeframeRange::historyBounds(
            $timeframe,
            max(1, $points),
            is_string($anchorDate) ? $anchorDate : null,
        );

        $cacheKey = sprintf(
            'analytics:anomalies:expenses:%s:%s:%s',
            $timeframe,
            $periodFrom->toDateString(),
            $periodTo->toDateString(),
        );

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($periodFrom, $periodTo): array {
            $expenses = Expense::query()
                ->whereBetween('expense_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
                ->orderBy('expense_date')
                ->pluck('amount')
                ->map(fn ($amount) => (float) $amount)
                ->values()
                ->all();

            return $this->forecastService->detectAnomalies($expenses);
        });

        $anomalyCount = collect($data)->filter(fn (array $row): bool => (bool) ($row['is_anomaly'] ?? false))->count();

        event(new InsightStreamed('insight.analytics.anomalies', [
            'scope' => 'analytics',
            'timeframe' => $timeframe,
            'points' => count($data),
            'anomaly_count' => $anomalyCount,
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function arHealth(Request $request): JsonResponse
    {
        $timeframe = TimeframeRange::normalize((string) $request->query('timeframe', 'month'));
        $anchorDate = $request->query('anchor_date');
        [$periodFrom, $periodTo] = TimeframeRange::bounds($timeframe, is_string($anchorDate) ? $anchorDate : null);
        $asOfDate = $periodTo->toDateString();

        $cacheKey = sprintf('analytics:anomalies:ar-health:%s:%s', $timeframe, $asOfDate);

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($periodFrom, $periodTo, $timeframe): array {
            $today = $periodTo->copy()->startOfDay();

            $items = Invoice::query()
                ->withSum([
                    'payments as paid_amount' => fn ($query) => $query->whereDate('payment_date', '<=', $periodTo->toDateString()),
                ], 'amount')
                ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
                ->whereDate('invoice_date', '<=', $periodTo->toDateString())
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

            return array_merge($this->forecastService->arHealth($items), [
                'timeframe' => $timeframe,
                'period_from' => $periodFrom->toDateString(),
                'period_to' => $periodTo->toDateString(),
                'as_of' => $periodTo->toDateString(),
            ]);
        });

        event(new InsightStreamed('insight.analytics.ar_health', [
            'scope' => 'analytics',
            'timeframe' => $timeframe,
            'health_score' => (float) ($data['score'] ?? 0),
            'health_status' => $data['status'] ?? null,
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    private function defaultPoints(string $timeframe): int
    {
        return match ($timeframe) {
            'day' => 30,
            'week' => 12,
            'year' => 5,
            default => 12,
        };
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
