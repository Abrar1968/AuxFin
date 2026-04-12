<?php

namespace App\Services;

use App\Algorithms\ARHealthScore;
use App\Algorithms\LinearRegression;
use App\Algorithms\MonteCarloForecast;
use App\Algorithms\MovingAverage;
use App\Algorithms\PayrollEfficiencyIndex;
use App\Algorithms\ZScoreAnomaly;

class ForecastService
{
    public function forecastCashFlow(array $arItems, int $iterations = 1000): array
    {
        return MonteCarloForecast::simulate($arItems, $iterations);
    }

    public function projectRevenue(array $series, int $months = 3): array
    {
        return LinearRegression::forecast($series, $months);
    }

    public function smooth(array $series, int $window = 3, float $alpha = 0.3): array
    {
        return [
            'sma' => MovingAverage::sma($series, $window),
            'ema' => MovingAverage::ema($series, $alpha),
        ];
    }

    public function detectAnomalies(array $series, float $threshold = 2.5): array
    {
        return ZScoreAnomaly::detect($series, $threshold);
    }

    public function arHealth(array $arItems): array
    {
        return ARHealthScore::calculate($arItems);
    }

    public function payrollEfficiency(float $totalRevenue, float $totalPayroll, int $headcount): array
    {
        return PayrollEfficiencyIndex::calculate($totalRevenue, $totalPayroll, $headcount);
    }
}
