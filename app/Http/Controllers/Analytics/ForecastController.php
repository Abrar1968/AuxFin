<?php

namespace App\Http\Controllers\Analytics;

use App\Events\InsightStreamed;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\ForecastService;
use App\Services\SnapshotService;
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
    ) {
    }

    public function forecast(): JsonResponse
    {
        $data = Cache::remember('analytics:forecast:cashflow', now()->addMinutes(10), function (): array {
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

            return $this->forecastService->forecastCashFlow($items);
        });

        event(new InsightStreamed('insight.analytics.forecast', [
            'scope' => 'analytics',
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
        $cacheKey = sprintf('analytics:forecast:burn-rate:%s', number_format($availableCash, 2, '.', ''));
        $runway = Cache::remember($cacheKey, now()->addMinutes(5), fn () => $this->snapshotService->calculateRunway($availableCash));

        event(new InsightStreamed('insight.analytics.runway', [
            'scope' => 'analytics',
            'available_cash' => $availableCash,
            'cash_runway_months' => (float) $runway,
            'generated_at' => now()->toIso8601String(),
        ]));

        return response()->json([
            'available_cash' => $availableCash,
            'cash_runway_months' => $runway,
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
