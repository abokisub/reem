<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccountVerification extends Controller
{
    /**
     * Verify bank account using the active transfer provider
     * Routes to Xixapay, Paystack, or Monnify based on settings
     */
    public function verifyBankAccount(Request $request)
    {
        // Rely on TokenAuthMiddleware for authentication
        $auth_user = $request->user();

        if (!$auth_user) {
            return response()->json(['message' => 'Unauthenticated', 'status' => 'fail'], 401);
        }

        // Validate input
        $bankCode = $request->input('bank_code') ?? $request->bank_code;
        $accountNumber = $request->input('account_number') ?? $request->account_number;

        if (empty($bankCode) || empty($accountNumber)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Bank code and account number are required'
            ], 400);
        }

        try {
            // Use the BankingService (PalmPay focused)
            $bankingService = new \App\Services\Banking\BankingService();
            $result = $bankingService->verifyAccount($accountNumber, $bankCode);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Account Verification Error: ' . $e->getMessage());

            $msg = $e->getMessage();

            // Sanitize HTML/System Errors
            if (str_contains($msg, '<!DOCTYPE') || str_contains($msg, '<html') || str_contains($msg, 'cURL error')) {
                $userMessage = "Temporary Service Error. Please try again later.";
            } elseif (str_contains($msg, 'Selected bank does not exist')) {
                $userMessage = "The selected bank does not appear to match this account number.";
            } elseif (str_contains($msg, 'resolve host')) {
                $userMessage = "Network Connection Error. Please verify your internet.";
            } else {
                // Strip any potential HTML tags just in case
                $userMessage = strip_tags($msg);
                // Limit length
                if (strlen($userMessage) > 100) {
                    $userMessage = "Verification failed. Please check account details.";
                }
            }

            return response()->json([
                'status' => 'fail',
                'message' => $userMessage
            ], 200); // Return 200 so app handles it as a "soft" failure
        }
    }
}
