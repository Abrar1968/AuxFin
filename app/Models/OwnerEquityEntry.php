<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerEquityEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_owner_id',
        'entry_date',
        'entry_type',
        'amount',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'business_owner_id' => 'integer',
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class, 'business_owner_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
