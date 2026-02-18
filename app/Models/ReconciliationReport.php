<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'provider',
        'total_provider_count',
        'total_provider_amount',
        'matched_count',
        'mismatched_count',
        'discrepancies',
        'status',
    ];

    protected $casts = [
        'discrepancies' => 'array',
        'report_date' => 'date',
    ];
}
