<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'payload',
        'signature',
        'verified',
        'processed',
        'processing_error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'verified' => 'boolean',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for unprocessed webhooks.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope for verified webhooks.
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope for specific provider.
     */
    public function scopeForProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Mark webhook as processed.
     */
    public function markAsProcessed($error = null)
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
            'processing_error' => $error,
        ]);
    }

    /**
     * Mark webhook as verified.
     */
    public function markAsVerified()
    {
        $this->update(['verified' => true]);
    }
}
