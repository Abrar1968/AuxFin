<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liability extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'principal_amount',
        'outstanding',
        'interest_rate',
        'monthly_payment',
        'start_date',
        'end_date',
        'next_due_date',
        'status',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_due_date' => 'date',
    ];
}
