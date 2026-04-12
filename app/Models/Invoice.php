<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'invoice_number',
        'amount',
        'due_date',
        'status',
        'partial_amount',
        'payment_completed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'partial_amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeRecognized($query)
    {
        return $query->whereNotNull('payment_completed_at');
    }
}
