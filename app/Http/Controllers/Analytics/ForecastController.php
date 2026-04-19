<?php

namespace App\Http\Controllers\Analytics;

use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\ForecastService;
use App\Services\SnapshotService;
use App\Services\TimeframeMetricsService;
use App\Support\TimeframeRange;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ForecastController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ACCRUAL_INVOICE_STATUSES = ['sent', 'partial', 'paid', 'overdue'];

    public function __construct(
        private readonly ForecastService $forecastService,
        private readonly SnapshotService $snapshotService,
        private readonly TimeframeMetricsService $timeframeMetrics,
    ) {
    }

    public function forecast(Request $request): JsonResponse
    {
        $timeframe = TimeframeRange::normalize((string) $request->query('timeframe', 'month'));
        $anchorDate = $request->query('anchor_date');
        [$periodFrom, $periodTo] = TimeframeRange::bounds($timeframe, is_string($anchorDate) ? $anchorDate : null);

        $cacheKey = sprintf(
            'analytics:forecast:cashflow:%s:%s:%s',
            $timeframe,
            $periodFrom->toDateString(),
            $periodTo->toDateString(),
        );

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

            return array_merge($this->forecastService->forecastCashFlow($items), [
                'timeframe' => $timeframe,
                'period_from' => $periodFrom->toDateString(),
                'period_to' => $periodTo->toDateString(),
            ]);
        });

        event(new InsightStreamed('insight.analytics.forecast', [
            'scope' => 'analytics',
            'timeframe' => $timeframe,
            'p10' => (float) ($data['p10'] ?? 0),
            'p50' => (float) ($data['p50'] ?? 0),
            'p90' => (float) ($data['p90'] ?? 0),
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json($data);
    }

    public function burnRate(Request $request): JsonResponse
    {
        $availableCash = (float) $request->query('available_cash', 0);
        $timeframe = TimeframeRange::normalize((string) $request->query('timeframe', 'month'));
        $anchorDate = $request->query('anchor_date');
        $points = $request->has('points') ? $request->integer('points') : null;

        $cacheKey = sprintf(
            'analytics:forecast:burn-rate:%s:%s:%s:%s',
            number_format($availableCash, 2, '.', ''),
            $timeframe,
            is_string($anchorDate) ? $anchorDate : 'auto',
            $points ?? 'auto',
        );

        $runwayData = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($availableCash, $timeframe, $anchorDate, $points): array {
            $seriesPayload = $this->timeframeMetrics->series(
                $timeframe,
                is_string($anchorDate) ? $anchorDate : null,
                $points,
            );

            $averageBurnRate = round((float) collect($seriesPayload['series'] ?? [])->avg('burn_rate'), 2);

            if ($averageBurnRate <= 0) {
                $fallbackRunway = $this->snapshotService->calculateRunway($availableCash);

                return [
                    'cash_runway_months' => $fallbackRunway,
                    'average_burn_rate' => 0.0,
                    'timeframe' => $seriesPayload['timeframe'] ?? $timeframe,
                    'period_from' => $seriesPayload['period_from'] ?? null,
                    'period_to' => $seriesPayload['period_to'] ?? null,
                ];
            }

            return [
                'cash_runway_months' => round($availableCash / $averageBurnRate, 2),
                'average_burn_rate' => $averageBurnRate,
                'timeframe' => $seriesPayload['timeframe'] ?? $timeframe,
                'period_from' => $seriesPayload['period_from'] ?? null,
                'period_to' => $seriesPayload['period_to'] ?? null,
            ];
        });

        $runway = (float) ($runwayData['cash_runway_months'] ?? 0);

        event(new InsightStreamed('insight.analytics.runway', [
            'scope' => 'analytics',
            'timeframe' => $runwayData['timeframe'] ?? $timeframe,
            'available_cash' => $availableCash,
            'cash_runway_months' => $runway,
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json([
            'available_cash' => $availableCash,
            'cash_runway_months' => $runway,
            'average_burn_rate' => (float) ($runwayData['average_burn_rate'] ?? 0),
            'timeframe' => $runwayData['timeframe'] ?? $timeframe,
            'period_from' => $runwayData['period_from'] ?? null,
            'period_to' => $runwayData['period_to'] ?? null,
        ]);
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
