<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'invoice_number',
        'amount',
        'invoice_date',
        'due_date',
        'status',
        'partial_amount',
        'payment_completed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'partial_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectPayment::class);
    }

    public function scopeRecognized($query)
    {
        return $query->whereNotNull('invoice_date');
    }

    public function scopeAccrued($query)
    {
        return $query->whereNotNull('invoice_date');
    }
}
