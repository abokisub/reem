<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyKycHistory extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only created_at timestamp

    protected $table = 'company_kyc_history';

    protected $fillable = [
        'company_id',
        'section',
        'action',
        'admin_id',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the company that owns the history record.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the admin who performed the action.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Log a KYC action.
     */
    public static function logAction($companyId, $section, $action, $adminId = null, $reason = null, $metadata = null)
    {
        return self::create([
            'company_id' => $companyId,
            'section' => $section,
            'action' => $action,
            'admin_id' => $adminId,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get history for a specific company.
     */
    public static function getCompanyHistory($companyId)
    {
        return self::where('company_id', $companyId)
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
