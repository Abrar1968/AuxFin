<?php

namespace App\Models;

use App\Models\MessageRead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'thread_id',
        'type',
        'subject',
        'body',
        'reference_date',
        'reference_month',
        'attachments',
        'status',
        'priority',
        'admin_reply',
        'replied_by',
        'replied_at',
        'action_taken',
        'resolved_at',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'reference_month' => 'date',
        'attachments' => 'array',
        'replied_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class, 'message_id');
    }
}
