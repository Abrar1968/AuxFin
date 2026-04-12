<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\ForecastService;
use App\Services\SnapshotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function __construct(
        private readonly ForecastService $forecastService,
        private readonly SnapshotService $snapshotService,
    ) {
    }

    public function forecast(): JsonResponse
    {
        $today = now();
        $items = Invoice::query()
            ->whereNull('payment_completed_at')
            ->get()
            ->map(function ($invoice) use ($today) {
                $age = Carbon::parse($invoice->due_date)->diffInDays($today, false);

                return [
                    'amount' => (float) $invoice->amount,
                    'bucket' => $this->bucketFromAge($age),
                ];
            })
            ->values()
            ->all();

        return response()->json($this->forecastService->forecastCashFlow($items));
    }

    public function burnRate(Request $request): JsonResponse
    {
        $availableCash = (float) $request->query('available_cash', 0);
        $runway = $this->snapshotService->calculateRunway($availableCash);

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
