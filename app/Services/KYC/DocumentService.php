<?php

namespace App\Services\KYC;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Exception;

/**
 * Document Service
 * Handles KYC document upload, storage, and retrieval
 */
class DocumentService
{
    protected $disk = 'local';
    protected $basePath = 'kyc_documents';

    /**
     * Allowed document types and their max sizes (in KB)
     */
    protected $allowedTypes = [
        'cac_certificate' => ['pdf', 'jpg', 'jpeg', 'png'],
        'utility_bill' => ['pdf', 'jpg', 'jpeg', 'png'],
        'id_card' => ['jpg', 'jpeg', 'png'],
        'director_id' => ['jpg', 'jpeg', 'png'],
        'bank_statement' => ['pdf'],
        'other' => ['pdf', 'jpg', 'jpeg', 'png'],
    ];

    protected $maxFileSize = 5120; // 5MB in KB

    /**
     * Upload KYC document
     */
    public function uploadDocument(int $companyId, string $type, UploadedFile $file): array
    {
        try {
            // Validate document type
            if (!isset($this->allowedTypes[$type])) {
                throw new Exception("Invalid document type: $type");
            }

            // Validate file extension
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $this->allowedTypes[$type])) {
                throw new Exception("Invalid file type for $type. Allowed: " . implode(', ', $this->allowedTypes[$type]));
            }

            // Validate file size
            $fileSizeKB = $file->getSize() / 1024;
            if ($fileSizeKB > $this->maxFileSize) {
                throw new Exception("File size exceeds maximum allowed size of {$this->maxFileSize}KB");
            }

            // Generate unique filename
            $filename = $this->generateFilename($companyId, $type, $extension);

            // Store file
            $path = $file->storeAs(
                "{$this->basePath}/{$companyId}",
                $filename,
                $this->disk
            );

            Log::info('Document Uploaded', [
                'company_id' => $companyId,
                'type' => $type,
                'filename' => $filename,
                'size_kb' => round($fileSizeKB, 2),
            ]);

            return [
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => [
                    'path' => $path,
                    'filename' => $filename,
                    'type' => $type,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ],
            ];

        } catch (Exception $e) {
            Log::error('Document Upload Failed', [
                'company_id' => $companyId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Get document
     */
    public function getDocument(string $path): ?string
    {
        try {
            if (!Storage::disk($this->disk)->exists($path)) {
                return null;
            }

            return Storage::disk($this->disk)->path($path);

        } catch (Exception $e) {
            Log::error('Document Retrieval Failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument(string $path): bool
    {
        try {
            if (Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);

                Log::info('Document Deleted', ['path' => $path]);
                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Document Deletion Failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * List documents for a company
     */
    public function listDocuments(int $companyId): array
    {
        try {
            $directory = "{$this->basePath}/{$companyId}";

            if (!Storage::disk($this->disk)->exists($directory)) {
                return [];
            }

            $files = Storage::disk($this->disk)->files($directory);

            $documents = [];
            foreach ($files as $file) {
                $documents[] = [
                    'path' => $file,
                    'filename' => basename($file),
                    'size' => Storage::disk($this->disk)->size($file),
                    'last_modified' => Storage::disk($this->disk)->lastModified($file),
                ];
            }

            return $documents;

        } catch (Exception $e) {
            Log::error('Document Listing Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(int $companyId, string $type, string $extension): string
    {
        $timestamp = time();
        $random = substr(md5(uniqid()), 0, 8);

        return "{$type}_{$companyId}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get file URL (for serving documents)
     */
    public function getDocumentUrl(string $path): ?string
    {
        try {
            if (!Storage::disk($this->disk)->exists($path)) {
                return null;
            }

            // For local disk, return storage path
            // In production, you might use a signed URL or CDN
            return Storage::disk($this->disk)->url($path);

        } catch (Exception $e) {
            Log::error('Document URL Generation Failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
