<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\KYC\DocumentApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Document Controller
 * Handles document-level approval/rejection
 */
class DocumentController extends Controller
{
    protected $documentApprovalService;

    public function __construct(DocumentApprovalService $documentApprovalService)
    {
        $this->documentApprovalService = $documentApprovalService;
    }

    /**
     * Get documents for a company
     * GET /api/admin/documents/company/{companyId}
     */
    public function getCompanyDocuments($companyId)
    {
        try {
            $result = $this->documentApprovalService->getCompanyDocuments($companyId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a document
     * POST /api/admin/documents/{documentId}/approve
     */
    public function approve(Request $request, $documentId)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $adminId = auth()->id();

            $result = $this->documentApprovalService->approveDocument(
                $documentId,
                $adminId,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['document'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a document
     * POST /api/admin/documents/{documentId}/reject
     */
    public function reject(Request $request, $documentId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $adminId = auth()->id();

            $result = $this->documentApprovalService->rejectDocument(
                $documentId,
                $adminId,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['document'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * View/Download a document
     * GET /api/admin/documents/{documentId}/view
     */
    public function view($documentId)
    {
        try {
            $document = \App\Models\CompanyDocument::findOrFail($documentId);
            
            // Check if file exists
            if (!$document->file_path || !\Storage::disk('local')->exists($document->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document file not found',
                ], 404);
            }

            $filePath = storage_path('app/' . $document->file_path);
            $mimeType = \Storage::disk('local')->mimeType($document->file_path);

            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
