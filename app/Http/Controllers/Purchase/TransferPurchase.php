<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Services\TransferRouter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Beneficiary;

class TransferPurchase extends Controller
{
    protected $transferRouter;

    public function __construct(TransferRouter $transferRouter)
    {
        $this->transferRouter = $transferRouter;
    }

    public function TransferRequest(Request $request, $id = null)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $authHeader = $request->header('Authorization');
        $deviceKey = config('app.habukhan_device_key');

        // --- AUTHENTICATION LOGIC ---
        // Recognizing App regardless of whether it sends raw Device Key or Bearer Token (interceptor adds X-Device-Key)
        if ($deviceKey == $authHeader || $request->header('X-Device-Key') == $deviceKey || str_starts_with($authHeader ?? '', 'Bearer ')) {
            // APP AUTH

            // If user_id is missing in request but id is present in route/URL (Mobile App Route)
            if (!$request->has('user_id') && $id) {
                $request->merge(['user_id' => $id]);
            }

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|gt:0',
                'account_number' => 'required|numeric|digits:10',
                'bank_code' => 'required',
                'account_name' => 'required',
                'user_id' => 'required', // App sends user_id or token in URL
                'pin' => 'required',
                'narration' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
            }

            $transid = $this->purchase_ref('TF_'); // Transfer Reference

            $verified_id = $this->verifyapptoken($request->user_id);
            $check = DB::table('users')->where(['id' => $verified_id, 'status' => 'active']);

            if ($check->count() == 1) {
                $user = $check->first();
                if (trim($user->pin) == trim($request->pin)) {
                    $accessToken = $user->api_key;
                } else {
                    return response()->json(['status' => 'fail', 'message' => 'Invalid Transaction Pin'])->setStatusCode(403);
                }
            } else {
                return response()->json(['status' => 'fail', 'message' => 'User not found or blocked'])->setStatusCode(403);
            }

            $system = "APP";

        } else if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            // WEB AUTH
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|gt:0',
                'account_number' => 'required|numeric|digits:10',
                'bank_code' => 'required',
                'account_name' => 'required',
                'token' => 'required', // Web sends token
                'pin' => 'required',
                'narration' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
            }

            $transid = $this->purchase_ref('TF_');
            $accessToken = null;

            if ($this->core()->allow_pin == 1) {
                $check = DB::table('users')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $user = $check->first();
                    if (trim($user->pin) == trim($request->pin)) {
                        $accessToken = $user->api_key;
                    } else {
                        return response()->json(['status' => 'fail', 'message' => 'Invalid Transaction Pin'])->setStatusCode(403);
                    }
                } else {
                    return response()->json(['status' => 'fail', 'message' => 'User not found'])->setStatusCode(403);
                }
            } else {
                // Pin not required config
                $check = DB::table('users')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $user = $check->first();
                    $accessToken = $user->api_key;
                }
            }
            $system = config('app.name');

        } else {
            // API AUTH
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|gt:0',
                'account_number' => 'required|numeric|digits:10',
                'bank_code' => 'required',
                'account_name' => 'required',
                'request-id' => 'required|unique:transfers,reference'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
            }

            $transid = $request->input('request-id');
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));

            $check = DB::table('users')->where(function ($query) use ($accessToken) {
                $query->where('api_key', $accessToken)
                    ->orWhere('app_key', $accessToken)
                    ->orWhere('habukhan_key', $accessToken);
            })->where('status', 1);

            if ($check->count() == 1) {
                $user = $check->first();
            } else {
                return response()->json(['status' => 'fail', 'message' => 'Invalid Authorization Token'])->setStatusCode(403);
            }
            $system = "API";
        }

        if (!isset($user) || !$user) {
            return response()->json(['status' => 'fail', 'message' => 'Authentication Failed'])->setStatusCode(403);
        }

        // Apply Tier Limits via LimitService
        $limitCheck = \App\Services\LimitService::checkLimit($user, $request->amount);
        if (!$limitCheck['allowed']) {
            return response()->json([
                'status' => 'fail',
                'message' => $limitCheck['message']
            ])->setStatusCode(403);
        }

        // 1. Calculate Charges (Prepare Data OUTSIDE transaction if possible to minimize lock time)
        $amount = $request->amount;

        // --- PROCESSING LOGIC ---

        // --- CRITICAL SECURITY: WRAP IN TRANSACTION & LOCK ROW ---
        // This prevents race conditions and guarantee atomic updates.

        try {
            // 1. DEDUCT BALANCE AND RECORD PENDING TRANSACTION
            $transactionResult = DB::transaction(function () use ($request, $user, $system, $amount, $transid) {
                // RE-FETCH USER WITH PESSIMISTIC LOCK
                $lockedUser = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();

                $charge = $this->calculateTransferCharge($amount, $lockedUser->active_company_id);
                $total_deduction = $amount + $charge;

                // Get company wallet with lock
                $companyWallet = \App\Models\CompanyWallet::where('company_id', $lockedUser->active_company_id)
                    ->lockForUpdate()
                    ->first();

                if (!$companyWallet) {
                    return ['status' => 'fail', 'message' => 'Company wallet not found', 'code' => 403];
                }

                // Convert to float to ensure proper comparison
                $currentBalance = (float) $companyWallet->balance;
                $totalDeduction = (float) $total_deduction;

                if ($currentBalance < $totalDeduction) {
                    return ['status' => 'fail', 'message' => 'Insufficient Funds. Your current wallet balance is ₦' . number_format($currentBalance, 2) . '. Required: ₦' . number_format($totalDeduction, 2), 'code' => 403];
                }

                // Store old balance for transaction record
                $old_balance = $companyWallet->balance;
                
                // Deduct from company wallet
                $companyWallet->debit($total_deduction);
                $new_wallet = $companyWallet->balance;

                // Record Transaction (PENDING)
                \App\Models\Transaction::create([
                    'user_id' => $lockedUser->id,
                    'company_id' => $lockedUser->active_company_id,
                    'reference' => $transid,
                    'type' => 'debit',
                    'category' => 'transfer_out',
                    'amount' => $amount,
                    'fee' => $charge,
                    'total_amount' => $total_deduction,
                    'recipient_bank_code' => $request->bank_code,
                    'recipient_account_number' => $request->account_number,
                    'recipient_account_name' => $request->account_name,
                    'description' => $request->narration,
                    'status' => 'pending',
                    'balance_before' => $old_balance,
                    'balance_after' => $new_wallet,
                    'metadata' => [
                        'system' => $system,
                        'narration' => $request->narration
                    ],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Log to message table
                DB::table('message')->insert([
                    'username' => $lockedUser->username,
                    'amount' => $total_deduction,
                    'message' => 'Bank Transfer to ' . $request->account_name . ' (' . $request->account_number . ') - ' . $request->narration,
                    'phone_account' => $request->account_number,
                    'oldbal' => $old_balance,
                    'newbal' => $new_wallet,
                    'habukhan_date' => $this->system_date(),
                    'plan_status' => 2, // Processing
                    'transid' => $transid,
                    'role' => 'transfer'
                ]);

                return ['status' => 'success', 'user_id' => $lockedUser->id, 'total_deduction' => $total_deduction];
            });

            if ($transactionResult['status'] !== 'success') {
                return response()->json(['status' => 'fail', 'message' => $transactionResult['message']])->setStatusCode($transactionResult['code']);
            }

            // 2. CALL ROUTER (OUTSIDE TRANSACTION - LOCK RELEASED)
            $transferDetails = [
                'amount' => $amount,
                'bank_code' => $request->bank_code,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'narration' => $request->narration,
                'reference' => $transid,
                'balance_already_deducted' => true,  // Signal that balance was already deducted
                'transaction_reference' => $transid  // Pass existing transaction reference
            ];

            try {
                $routerResponse = $this->transferRouter->processTransfer($transferDetails, $user->active_company_id);

                if ($routerResponse['status'] == 'success' || $routerResponse['status'] == 'pending') {
                    $finalStatus = ($routerResponse['status'] == 'success') ? 'success' : 'pending';

                    DB::table('transactions')->where('reference', $transid)->update([
                        'status' => $finalStatus,
                        'processed_at' => now(),
                        'updated_at' => now()
                    ]);

                    DB::table('message')->where('transid', $transid)->update([
                        'plan_status' => 1,
                        'message' => 'Successfully transferred ₦' . number_format($amount) . ' to ' . $request->account_name . ' (' . $request->account_number . ')'
                    ]);

                    // Record for Tier Limits
                    \App\Services\LimitService::recordTransaction($user, $amount);

                    // --- SAVE BENEFICIARY (SOURCE OF TRUTH) ---
                    // Only save on success or pending (provider accepted it)
                    try {
                        Beneficiary::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'service_type' => 'transfer_external',
                                'identifier' => $request->account_number
                            ],
                            [
                                'network_or_provider' => $routerResponse['bank_name'] ?? 'Bank',
                                'name' => $request->account_name,
                                'is_favorite' => $request->save_beneficiary ? 1 : 0,
                                'last_used_at' => Carbon::now(),
                            ]
                        );
                    } catch (\Exception $e) {
                        // Don't fail the transaction if saving beneficiary fails
                        Log::error('Failed to save beneficiary: ' . $e->getMessage());
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Transfer Successful',
                        'reference' => $transid
                    ]);

                } else {
                    throw new \Exception($routerResponse['message'] ?? 'Provider Error');
                }

            } catch (\Exception $e) {
                // 3. REFUND ON FAILURE
                Log::error("TransferPurchase: API Call Failed, refunding user. Error: " . $e->getMessage());

                DB::transaction(function () use ($user, $transactionResult, $transid, $e) {
                    // Refund to company wallet
                    $companyWallet = \App\Models\CompanyWallet::where('company_id', $user->active_company_id)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($companyWallet) {
                        $companyWallet->credit($transactionResult['total_deduction']);
                        $new_bal = $companyWallet->balance;
                    } else {
                        $new_bal = 0;
                    }

                    DB::table('transactions')->where('reference', $transid)->update([
                        'status' => 'failed',
                        'balance_after' => $new_bal,
                        'error_message' => substr($e->getMessage(), 0, 255),
                        'updated_at' => now()
                    ]);

                    DB::table('message')->where('transid', $transid)->update([
                        'plan_status' => 0, // Failed
                        'message' => 'Transfer FAILED and Refunded. Reason: ' . substr($e->getMessage(), 0, 100),
                        'newbal' => $new_bal
                    ]);
                });

                $msg = $e->getMessage();
                $userMsg = "Transfer Failed. Funds returned.";

                if (str_contains($msg, 'does not exist')) {
                    $userMsg = "Failed: The receiving bank rejected the transaction. Please checking the details.";
                } elseif (str_contains($msg, 'Insufficient Funds') || str_contains($msg, 'Low Liquidity')) {
                    $userMsg = "Service temporarily unavailable (Low Balance). Please try again later.";
                } elseif (str_contains($msg, 'Connection timed out') || str_contains($msg, 'resolve host')) {
                    $userMsg = "Network Error. Please try again.";
                } elseif (str_contains($msg, '{')) {
                    // Try to extract "message" field if it's a JSON string
                    $json = json_decode(substr($msg, strpos($msg, '{')), true);
                    if (isset($json['message'])) {
                        $userMsg = "Failed: " . $json['message'];
                    }
                } else {
                    $cleanMsg = strip_tags($msg);
                    if (strlen($cleanMsg) < 100)
                        $userMsg = "Failed: " . $cleanMsg;
                }

                return response()->json([
                    'status' => 'fail',
                    'message' => $userMsg
                ])->setStatusCode(400);
            }

        } catch (\Exception $e) {
            Log::error('TransferRequest Exception: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'company_id' => $user->active_company_id ?? null,
                'amount' => $amount ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'fail', 'message' => 'Internal Server Error: ' . $e->getMessage()])->setStatusCode(500);
        }

    }

    private function calculateTransferCharge($amount, $cid = null)
    {
        $settings = $this->core($cid);
        $type = $settings->transfer_charge_type ?? 'FLAT';
        $value = $settings->transfer_charge_value ?? 0;
        $cap = $settings->transfer_charge_cap ?? 0;

        if ($type == 'PERCENTAGE') {
            $charge = ($amount / 100) * $value;
            // Apply cap if it's set and charge exceeds it
            if ($cap > 0 && $charge > $cap) {
                $charge = $cap;
            }
            return $charge;
        }

        // Default to Flat Fee
        return $value;
    }
}
