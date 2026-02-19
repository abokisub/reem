<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PalmPayWebhook extends Model
{
    protected $table = 'palmpay_webhooks';

    protected $fillable = [
        'event_type',
        'palmpay_reference',
        'payload',
        'signature',
        'verified',
        'processed',
        'retry_count',
        'next_retry_at',
        'status',
        'processed_at',
        'processing_error',
        'transaction_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'verified' => 'boolean',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Get the related transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}