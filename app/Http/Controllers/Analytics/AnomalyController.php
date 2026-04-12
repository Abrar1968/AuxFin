<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Services\ForecastService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AnomalyController extends Controller
{
    public function __construct(private readonly ForecastService $forecastService)
    {
    }

    public function index(): JsonResponse
    {
        $expenses = Expense::query()
            ->orderByDesc('expense_date')
            ->limit(36)
            ->pluck('amount')
            ->map(fn ($amount) => (float) $amount)
            ->reverse()
            ->values()
            ->all();

        return response()->json($this->forecastService->detectAnomalies($expenses));
    }

    public function arHealth(): JsonResponse
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

        return response()->json($this->forecastService->arHealth($items));
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
