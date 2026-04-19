<?php

namespace App\Algorithms;

class PayrollEfficiencyIndex
{
    public static function calculate(float $totalRevenue, float $totalPayroll, int $headcount): array
    {
        $revenuePerEmployee = $headcount > 0 ? $totalRevenue / $headcount : 0;
        $payrollRatio = $totalRevenue > 0 ? ($totalPayroll / $totalRevenue) * 100 : 0;

        return [
            'revenue_per_employee' => round($revenuePerEmployee, 2),
            'payroll_ratio' => round($payrollRatio, 2),
            'status' => self::status($payrollRatio),
        ];
    }

    private static function status(float $ratio): string
    {
        return match (true) {
            $ratio < 40 => 'target',
            $ratio <= 55 => 'watch',
            $ratio <= 70 => 'warning',
            default => 'critical',
        };
    }
}
