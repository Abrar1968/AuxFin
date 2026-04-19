<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'purchase_date',
        'purchase_cost',
        'current_book_value',
        'useful_life_months',
        'monthly_depreciation',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_book_value' => 'decimal:2',
        'monthly_depreciation' => 'decimal:2',
    ];
}
