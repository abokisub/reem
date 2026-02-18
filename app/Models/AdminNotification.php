<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'company_id',
        'transaction_id',
        'priority',
        'is_read',
        'read_by',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company associated with the notification.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the admin who read the notification.
     */
    public function reader()
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for high priority notifications.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    /**
     * Scope for specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($userId = null)
    {
        $this->update([
            'is_read' => true,
            'read_by' => $userId ?? auth()->id(),
            'read_at' => now(),
        ]);
    }

    /**
     * Create a new KYC submission notification.
     */
    public static function createKycSubmission(Company $company, $isResubmission = false)
    {
        return self::create([
            'type' => $isResubmission ? 'kyc_resubmission' : 'new_kyc_submission',
            'title' => $isResubmission ? 'KYC Resubmission' : 'New KYC Submission',
            'message' => "{$company->business_name} has " . ($isResubmission ? 'resubmitted' : 'submitted') . " KYC for review",
            'company_id' => $company->id,
            'priority' => $isResubmission ? 'high' : 'medium',
            'metadata' => [
                'company_name' => $company->business_name,
                'submitted_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a fraud alert notification.
     */
    public static function createFraudAlert($companyId, $transactionId, $description, $severity)
    {
        return self::create([
            'type' => 'fraud_alert',
            'title' => 'Fraud Alert',
            'message' => $description,
            'company_id' => $companyId,
            'transaction_id' => $transactionId,
            'priority' => $severity === 'critical' ? 'critical' : 'high',
            'metadata' => [
                'severity' => $severity,
                'detected_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get unread count.
     */
    public static function getUnreadCount()
    {
        return self::unread()->count();
    }

    /**
     * Get recent notifications.
     */
    public static function getRecent($limit = 10)
    {
        return self::with(['company', 'reader'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
