<?php

namespace App\Models;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\EmployeeMessage;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\SalaryMonth;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = [
        'bank_account_number',
    ];

    protected $appends = [
        'masked_bank_account',
    ];

    protected $fillable = [
        'user_id',
        'employee_code',
        'department_id',
        'designation',
        'date_of_joining',
        'bank_account_number',
        'bank_name',
        'basic_salary',
        'house_rent',
        'conveyance',
        'medical_allowance',
        'pf_rate',
        'tds_rate',
        'professional_tax',
        'late_threshold_days',
        'late_penalty_type',
        'working_days_per_week',
        'weekly_off_days',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'weekly_off_days' => 'array',
        'basic_salary' => 'decimal:2',
        'house_rent' => 'decimal:2',
        'conveyance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'pf_rate' => 'decimal:2',
        'tds_rate' => 'decimal:2',
        'professional_tax' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function salaryMonths(): HasMany
    {
        return $this->hasMany(SalaryMonth::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmployeeMessage::class);
    }

    public function getMaskedBankAccountAttribute(): ?string
    {
        if (! $this->bank_account_number) {
            return null;
        }

        $len = strlen($this->bank_account_number);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4).substr($this->bank_account_number, -4);
    }
}
