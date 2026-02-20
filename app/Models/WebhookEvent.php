<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'transaction_id',
        'direction',
        'company_id',
        'provider_name',
        'endpoint_url',
        'event_type',
        'payload',
        'status',
        'attempt_count',
        'last_attempt_at',
        'next_retry_at',
        'http_status',
        'response_body',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt_count' => 'integer',
        'http_status' => 'integer',
        'last_attempt_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Get the transaction associated with this webhook event
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the company associated with this webhook event
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if webhook is incoming
     */
    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    /**
     * Check if webhook is outgoing
     */
    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    /**
     * Check if webhook can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' 
            && $this->attempt_count < 5 
            && $this->direction === 'outgoing';
    }

    /**
     * Calculate next retry time using exponential backoff
     */
    public function calculateNextRetry(): ?\Carbon\Carbon
    {
        if (!$this->canRetry()) {
            return null;
        }

        // Exponential backoff: 1min, 5min, 15min, 1hour, 6hours
        $delays = [60, 300, 900, 3600, 21600];
        $delaySeconds = $delays[$this->attempt_count] ?? 21600;

        return now()->addSeconds($delaySeconds);
    }
}
