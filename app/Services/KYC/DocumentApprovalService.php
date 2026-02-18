<?php

namespace App\Services\KYC;

use App\Models\CompanyDocument;
use App\Models\CompanyKycHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Document Approval Service
 * Handles granular document-level approval/rejection
 */
class DocumentApprovalService
{
    /**
     * Approve a document
     */
    public function approveDocument(int $documentId, int $adminId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($documentId, $adminId, $notes) {
            $document = CompanyDocument::find($documentId);

            if (!$document) {
                throw new Exception("Document not found");
            }

            $document->update([
                'status' => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            // Log the approval
            CompanyKycHistory::logAction(
                $document->company_id,
                'documents',
                'document_approved',
                $adminId,
                $notes,
                [
                    'document_id' => $documentId,
                    'document_type' => $document->document_type,
                    'file_path' => $document->file_path,
                ]
            );

            Log::info('Document Approved', [
                'document_id' => $documentId,
                'company_id' => $document->company_id,
                'admin_id' => $adminId,
            ]);

            return [
                'success' => true,
                'message' => 'Document approved successfully',
                'document' => $document,
            ];
        });
    }

    /**
     * Reject a document
     */
    public function rejectDocument(int $documentId, int $adminId, string $reason): array
    {
        return DB::transaction(function () use ($documentId, $adminId, $reason) {
            $document = CompanyDocument::find($documentId);

            if (!$document) {
                throw new Exception("Document not found");
            }

            $document->update([
                'status' => 'rejected',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // Log the rejection
            CompanyKycHistory::logAction(
                $document->company_id,
                'documents',
                'document_rejected',
                $adminId,
                $reason,
                [
                    'document_id' => $documentId,
                    'document_type' => $document->document_type,
                    'file_path' => $document->file_path,
                ]
            );

            Log::info('Document Rejected', [
                'document_id' => $documentId,
                'company_id' => $document->company_id,
                'admin_id' => $adminId,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Document rejected',
                'document' => $document,
            ];
        });
    }

    /**
     * Get documents for a company
     */
    public function getCompanyDocuments(int $companyId): array
    {
        $documents = CompanyDocument::where('company_id', $companyId)
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total' => $documents->count(),
            'pending' => $documents->where('status', 'pending')->count(),
            'approved' => $documents->where('status', 'approved')->count(),
            'rejected' => $documents->where('status', 'rejected')->count(),
        ];

        return [
            'documents' => $documents,
            'summary' => $summary,
        ];
    }

    /**
     * Check if all documents are approved for a company
     */
    public function allDocumentsApproved(int $companyId): bool
    {
        $totalDocuments = CompanyDocument::where('company_id', $companyId)->count();

        if ($totalDocuments === 0) {
            return false;
        }

        $approvedDocuments = CompanyDocument::where('company_id', $companyId)
            ->where('status', 'approved')
            ->count();

        return $totalDocuments === $approvedDocuments;
    }
}
