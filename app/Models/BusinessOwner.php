<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ownership_percentage',
        'initial_investment',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'ownership_percentage' => 'decimal:2',
        'initial_investment' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function equityEntries(): HasMany
    {
        return $this->hasMany(OwnerEquityEntry::class, 'business_owner_id');
    }
}
