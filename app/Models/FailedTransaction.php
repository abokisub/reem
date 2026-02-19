<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_reference',
        'company_id',
        'type',
        'amount',
        'payload',
        'failure_reason',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'resolved_at' => 'datetime',
        'amount' => 'float',
    ];
}
