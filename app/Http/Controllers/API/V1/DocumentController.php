<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\KYC\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Document Upload Controller
 * Handles KYC document uploads for companies
 */
class DocumentController extends Controller
{
    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Upload KYC document
     * POST /api/v1/documents/upload
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:cac_certificate,utility_bill,id_card,director_id,bank_statement,other',
            'file' => 'required|file|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = Auth::user()->company_id;

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $result = $this->documentService->uploadDocument(
                $companyId,
                $request->type,
                $request->file('file')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List documents for authenticated company
     * GET /api/v1/documents
     */
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $documents = $this->documentService->listDocuments($companyId);

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete document
     * DELETE /api/v1/documents/{path}
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = Auth::user()->company_id;
            $path = $request->path;

            // Verify the document belongs to the company
            if (!str_contains($path, "kyc_documents/{$companyId}/")) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to document',
                ], 403);
            }

            $deleted = $this->documentService->deleteDocument($path);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
