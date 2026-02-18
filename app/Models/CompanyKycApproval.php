<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyKycApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'section',
        'status',
        'reviewed_by',
        'rejection_reason',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the company that owns the KYC approval.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the admin who reviewed this section.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved sections.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected sections.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if all sections are approved for a company.
     */
    public static function allSectionsApproved($companyId)
    {
        $sections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];

        $approvals = self::where('company_id', $companyId)
            ->whereIn('section', $sections)
            ->get();

        return $approvals->count() === 5 && $approvals->every(fn($a) => $a->status === 'approved');
    }

    /**
     * Get approval status summary for a company.
     */
    public static function getApprovalSummary($companyId)
    {
        $sections = ['business_info', 'account_info', 'bvn_info', 'documents', 'board_members'];
        $approvals = self::where('company_id', $companyId)->get()->keyBy('section');

        $summary = [];
        foreach ($sections as $section) {
            $summary[$section] = [
                'status' => $approvals->has($section) ? $approvals[$section]->status : 'pending',
                'reviewed_at' => $approvals->has($section) ? $approvals[$section]->reviewed_at : null,
                'reviewed_by' => $approvals->has($section) ? $approvals[$section]->reviewer : null,
                'rejection_reason' => $approvals->has($section) ? $approvals[$section]->rejection_reason : null,
            ];
        }

        return $summary;
    }
}
