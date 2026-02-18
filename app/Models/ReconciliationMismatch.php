<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationMismatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'provider_reference',
        'internal_reference',
        'amount_provider',
        'amount_internal',
        'type',
        'status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'report_date' => 'date',
    ];
}
