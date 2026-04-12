<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySnapshot extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'snapshot_month',
        'total_revenue',
        'total_payroll',
        'total_opex',
        'gross_profit',
        'net_profit',
        'burn_rate',
        'cash_runway_months',
        'headcount',
        'total_ar',
    ];

    protected $casts = [
        'snapshot_month' => 'date',
        'total_revenue' => 'decimal:2',
        'total_payroll' => 'decimal:2',
        'total_opex' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'burn_rate' => 'decimal:2',
        'cash_runway_months' => 'decimal:2',
        'total_ar' => 'decimal:2',
    ];
}
