<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'description',
        'amount',
        'accounting_mode',
        'expense_date',
        'payable_due_date',
        'prepaid_start_date',
        'prepaid_months',
        'is_recurring',
        'recurrence',
        'next_due_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'payable_due_date' => 'date',
        'prepaid_start_date' => 'date',
        'prepaid_months' => 'integer',
        'next_due_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }
}
