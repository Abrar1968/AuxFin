<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'loan_id',
        'month',
        'amount_paid',
        'created_at',
    ];

    protected $casts = [
        'month' => 'date',
        'amount_paid' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
