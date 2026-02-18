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
        'processed_at',
        'processing_error',
        'transaction_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'verified' => 'boolean',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the related transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}