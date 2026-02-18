<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class KYCController extends Controller
{
    public function __construct()
    {
        // Provider removed
    }

    /**
     * Check KYC Status and Return Pre-fill Data
     * GET /api/user/kyc/check
     */
    public function checkKycStatus(Request $request)
    {
        $user = DB::table('users')->where('id', $request->user()->id)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        $missingFields = $this->getMissingFields($user);

        // Split name into first and last
        $nameParts = explode(' ', $user->name ?? '');
        $firstName = $nameParts[0] ?? '';
        $lastName = implode(' ', array_slice($nameParts, 1)) ?: $firstName;

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_customer_id' => !empty($user->customer_id),
                'kyc_status' => $user->kyc_status ?? 'pending',
                'missing_fields' => $missingFields,
                'is_complete' => empty($missingFields),
                'prefill_data' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'phone_number' => $user->username,
                    'bvn' => $user->bvn ?? '',
                    'nin' => $user->nin ?? '',
                    'date_of_birth' => $user->dob ?? '',
                    'address' => $user->address ?? '',
                    'city' => '', // These are consolidated in address usually
                    'state' => '',
                    'postal_code' => '',
                ]
            ]
        ]);
    }

    /**
     * Determine Missing KYC Fields
     */
    private function getMissingFields($user): array
    {
        $missing = [];

        // At least one ID type required
        if (empty($user->bvn) && empty($user->nin)) {
            $missing[] = 'id_number';
        }

        // Required document uploads
        if (empty($user->id_card_path)) {
            $missing[] = 'id_card';
        }
        if (empty($user->utility_bill_path)) {
            $missing[] = 'utility_bill';
        }

        // Required fields
        $requiredFields = ['dob', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                $missing[] = $field === 'dob' ? 'date_of_birth' : $field;
            }
        }

        return $missing;
    }

    /**
     * Submit KYC
     * POST /api/user/kyc/submit
     */
    public function submitKyc(Request $request)
    {
        set_time_limit(300);
        $user = DB::table('users')->where('id', $request->user()->id)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        // Check if already has customer_id
        if (!empty($user->customer_id)) {
            return response()->json([
                'status' => 'success',
                'message' => 'KYC already completed',
                'data' => ['customer_id' => $user->customer_id]
            ]);
        }

        // Base Validation
        $rules = [
            'id_type' => 'required|in:bvn,nin',
            'id_number' => 'required|digits:11',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'id_card' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'utility_bill' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            // Both DOB and phone are optional, but at least one is recommended
            'date_of_birth' => 'nullable|date|before:14 years ago',
            'phone' => 'nullable|string|regex:/^[0-9]+$/|min:10|max:15',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            // Upload Files
            $idCardPath = $request->file('id_card')->store("kyc/{$user->id}", 'public');
            $utilityBillPath = $request->file('utility_bill')->store("kyc/{$user->id}", 'public');

            // Use provided values or fallback to user data
            $phoneForVerification = $request->phone ?? $user->username;
            $dobForVerification = $request->date_of_birth ?? $user->dob;

            // Update User Table First
            DB::table('users')->where('id', $user->id)->update([
                $request->id_type => $request->id_number,
                'dob' => $dobForVerification,
                'address' => $request->address . ', ' . $request->city . ', ' . $request->state,
                'id_card_path' => $idCardPath,
                'utility_bill_path' => $utilityBillPath,
                'kyc_documents' => json_encode([
                    'id_card' => $idCardPath,
                    'utility_bill' => $utilityBillPath,
                    'id_type' => $request->id_type,
                    'id_number' => $request->id_number,
                    'submitted_metadata' => [
                        'address' => $request->address,
                        'city' => $request->city,
                        'state' => $request->state,
                        'postal_code' => $request->postal_code,
                        'phone' => $phoneForVerification,
                        'dob' => $dobForVerification,
                    ]
                ]),
                'kyc_status' => 'submitted',
                'kyc_submitted_at' => now()
            ]);

            // MOCK SUCCESS for now since Xixapay is gone
            // In future, implement PalmPay KYC or manual review
            // Real Customer ID Format (40 chars hex)
            $customerId = bin2hex(random_bytes(20));

            DB::table('users')->where('id', $user->id)->update([
                'customer_id' => $customerId,
                'kyc_status' => 'verified',
                'kyc' => '1' // Sync with admin setting
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'KYC submitted successfully (Mock)!',
                'data' => ['customer_id' => $customerId]
            ]);

        } catch (\Exception $e) {
            Log::error("KYC Submission Error: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}