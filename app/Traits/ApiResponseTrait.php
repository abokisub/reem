<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a success response
     */
    protected function successResponse(string $message, array $data = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return an error response
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $data = []): JsonResponse
    {
        return response()->json([
            'status' => 'fail',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return a validation error response
     */
    protected function validationErrorResponse(string $message = 'Validation failed', array $errors = []): JsonResponse
    {
        return response()->json([
            'status' => 'fail',
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Return an insufficient balance error
     */
    protected function insufficientBalanceResponse(float $currentBalance, float $requiredAmount): JsonResponse
    {
        return $this->errorResponse(
            "Insufficient balance. Your current balance is ₦" . number_format($currentBalance, 2) . 
            " but you need ₦" . number_format($requiredAmount, 2) . " to complete this transaction.",
            403
        );
    }

    /**
     * Return a service unavailable error
     */
    protected function serviceUnavailableResponse(string $service): JsonResponse
    {
        return $this->errorResponse(
            "$service service is currently unavailable. Please try again later or contact support.",
            503
        );
    }

    /**
     * Return a transaction failed error
     */
    protected function transactionFailedResponse(string $reason = 'Transaction failed due to a system error'): JsonResponse
    {
        return $this->errorResponse(
            "$reason. Please try again in a few moments or contact support if the issue persists.",
            500
        );
    }

    /**
     * Return an invalid input error
     */
    protected function invalidInputResponse(string $field, string $requirement): JsonResponse
    {
        return $this->errorResponse(
            "Invalid $field. $requirement",
            400
        );
    }

    /**
     * Return an unauthorized error
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized access'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a not found error
     */
    protected function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return $this->errorResponse("$resource not found", 404);
    }
}