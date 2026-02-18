<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Beneficiary;

class InternalTransferController extends Controller
{
    /**
     * Verify a user exists by Email or Username (for transfer recipient)
     */
    public function verifyUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|min:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
        }

        $identifier = trim($request->identifier);
        $phone_variant1 = null;
        $phone_variant2 = null;

        // If numeric and length 10/11, handle variants
        if (is_numeric($identifier)) {
            if (strlen($identifier) == 10) {
                $phone_variant1 = '0' . $identifier; // 11-digit version
                $phone_variant2 = $identifier;       // 10-digit version
            } elseif (strlen($identifier) == 11) {
                $phone_variant1 = $identifier;
                $phone_variant2 = substr($identifier, 1); // 10-digit version
            }
        }

        // Search by username, email, or phone variants
        $user = DB::table('users')
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->when($phone_variant1, function ($q) use ($phone_variant1, $phone_variant2) {
                return $q->orWhere('phone', $phone_variant1)->orWhere('phone', $phone_variant2);
            })
            ->select('id', 'username', 'name')
            ->first();

        if ($user) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'name' => $user->name ?? $user->username,
                    'username' => $user->username,
                    'id' => $user->id
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'User not found'
            ])->setStatusCode(404);
        }
    }

    /**
     * Execute Internal Wallet Transfer
     */
    public function transfer(Request $request)
    {
        $user_id = $request->header('id') ?? $request->user_id;

        // AUTHENTICATION (Consistent with other controllers)
        $verified_id = $this->verifyapptoken($user_id);
        if (!$verified_id) {
            return response()->json(['status' => 'fail', 'message' => 'Authentication Failed'])->setStatusCode(403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'recipient_identifier' => 'required|string',
            'pin' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
        }

        $amount = $request->amount;
        $recipient_identifier = $request->recipient_identifier;
        $pin = $request->pin;

        // Apply Tier Limits via LimitService
        $sender_temp = DB::table('users')->where('id', $verified_id)->first();
        $limitCheck = \App\Services\LimitService::checkLimit($sender_temp, $amount);
        if (!$limitCheck['allowed']) {
            return response()->json([
                'status' => 'fail',
                'message' => $limitCheck['message']
            ])->setStatusCode(403);
        }

        try {
            // ATOMIC TRANSACTION
            $result = DB::transaction(function () use ($verified_id, $amount, $recipient_identifier, $pin, $request) {

                // 1. Lock Sender
                $sender = DB::table('users')->where('id', $verified_id)->lockForUpdate()->first();
                if (!$sender)
                    throw new \Exception("Sender not found");

                // 2. Validate PIN
                if (trim($sender->pin) != trim($pin)) {
                    throw new \Exception("Invalid Transaction PIN");
                }

                // 3. Calculate Charges
                $charge = $this->calculateWalletCharge($amount, $sender->active_company_id);
                $total_deduction = $amount + $charge;

                // 4. Balance Check
                if ($sender->balance < $total_deduction) {
                    throw new \Exception("Insufficient Balance");
                }

                $recipient_variant1 = null;
                $recipient_variant2 = null;
                if (is_numeric($recipient_identifier)) {
                    if (strlen($recipient_identifier) == 10) {
                        $recipient_variant1 = '0' . $recipient_identifier;
                        $recipient_variant2 = $recipient_identifier;
                    } elseif (strlen($recipient_identifier) == 11) {
                        $recipient_variant1 = $recipient_identifier;
                        $recipient_variant2 = substr($recipient_identifier, 1);
                    }
                }

                // 4. Lock Recipient
                $recipient = DB::table('users')
                    ->where('username', $recipient_identifier)
                    ->orWhere('email', $recipient_identifier)
                    ->when($recipient_variant1, function ($q) use ($recipient_variant1, $recipient_variant2) {
                        return $q->orWhere('phone', $recipient_variant1)->orWhere('phone', $recipient_variant2);
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$recipient)
                    throw new \Exception("Recipient not found");
                if ($recipient->id == $sender->id)
                    throw new \Exception("Cannot transfer to self");

                // 5. Calculate New Balances
                $sender_new_bal = $sender->balance - $total_deduction;
                $recipient_new_bal = $recipient->balance + $amount;

                // 6. Execute Updates
                DB::table('users')->where('id', $sender->id)->update(['balance' => $sender_new_bal]);
                DB::table('users')->where('id', $recipient->id)->update(['balance' => $recipient_new_bal]);

                // 7. Log Transactions (Message Table)
                $transid = $this->purchase_ref('INT_');
                $date = $this->system_date();

                // Sender Log
                DB::table('message')->insert([
                    'username' => $sender->username,
                    'amount' => $total_deduction,
                    'message' => 'Internal Transfer sent to ' . $recipient->username . ($charge > 0 ? " (Charge: ₦$charge)" : ""),
                    'oldbal' => $sender->balance,
                    'newbal' => $sender_new_bal,
                    'habukhan_date' => $date,
                    'plan_status' => 1, // Success
                    'transid' => $transid,
                    'role' => 'transfer_sent'
                ]);

                // Recipient Log
                DB::table('message')->insert([
                    'username' => $recipient->username,
                    'amount' => $amount,
                    'message' => 'Internal Transfer received from ' . $sender->username,
                    'oldbal' => $recipient->balance,
                    'newbal' => $recipient_new_bal,
                    'habukhan_date' => $date,
                    'plan_status' => 1, // Success
                    'transid' => $transid . '_R',
                    'role' => 'transfer_received' // Or 'deposit' depending on how existing history works
                ]);

                // Notification for Recipient
                DB::table('notif')->insert([
                    'username' => $recipient->username,
                    'message' => 'You received ₦' . number_format($amount) . ' from ' . $sender->username,
                    'date' => $date,
                    'habukhan' => 0
                ]);

                // --- SAVE BENEFICIARY ---
                try {
                    Beneficiary::updateOrCreate(
                        [
                            'user_id' => $sender->id,
                            'service_type' => 'transfer_internal',
                            'identifier' => $recipient->username
                        ],
                        [
                            'name' => $recipient->name ?? $recipient->username,
                            'is_favorite' => $request->save_beneficiary ? 1 : 0,
                            'last_used_at' => Carbon::now(),
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error('Internal Beneficiary Save Failed: ' . $e->getMessage());
                }

                return ['status' => 'success', 'ref' => $transid];
            });

            // Record for Tier Limits 
            \App\Services\LimitService::recordTransaction($sender_temp, $amount);

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer Successful',
                'reference' => $result['ref']
            ]);

        } catch (\Exception $e) {
            // Determine if it's a validation error or system error
            $msg = $e->getMessage();
            $code = 400;
            if (!in_array($msg, ['Invalid Transaction PIN', 'Insufficient Balance', 'Recipient not found', 'Cannot transfer to self'])) {
                Log::error("Internal Transfer Error: " . $msg);
                $msg = "Transfer Failed";
                $code = 500;
            }

            return response()->json(['status' => 'fail', 'message' => $msg])->setStatusCode($code);
        }
    }

    private function calculateWalletCharge($amount, $cid = null)
    {
        $settings = $this->core($cid);
        $type = $settings->wallet_charge_type ?? 'FLAT';
        $value = $settings->wallet_charge_value ?? 0;
        $cap = $settings->wallet_charge_cap ?? 0;

        if ($type == 'PERCENTAGE') {
            $charge = ($amount / 100) * $value;
            if ($cap > 0 && $charge > $cap) {
                $charge = $cap;
            }
            return $charge;
        }

        return $value;
    }
}
