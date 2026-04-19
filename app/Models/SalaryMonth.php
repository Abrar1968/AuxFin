<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryMonth extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'basic_salary',
        'house_rent',
        'conveyance',
        'medical_allowance',
        'performance_bonus',
        'festival_bonus',
        'overtime_pay',
        'other_bonus',
        'gross_earnings',
        'tds_deduction',
        'pf_deduction',
        'professional_tax',
        'unpaid_leave_deduction',
        'late_penalty_deduction',
        'loan_emi_deduction',
        'total_deductions',
        'net_payable',
        'days_present',
        'unpaid_leave_days',
        'late_entries',
        'expected_working_days',
        'status',
        'processed_at',
        'paid_at',
        'processed_by',
    ];

    protected $casts = [
        'month' => 'date',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'basic_salary' => 'decimal:2',
        'house_rent' => 'decimal:2',
        'conveyance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'performance_bonus' => 'decimal:2',
        'festival_bonus' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'other_bonus' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'tds_deduction' => 'decimal:2',
        'pf_deduction' => 'decimal:2',
        'professional_tax' => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'late_penalty_deduction' => 'decimal:2',
        'loan_emi_deduction' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_payable' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
