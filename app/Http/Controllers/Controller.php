<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function core($cid = null)
    {
        $query = DB::table('settings');
        if ($cid) {
            $query->where('company_id', $cid);
        }
        $sets = $query->first();

        // Fallback to global defaults if no tenant settings found
        if ($cid && !$sets) {
            $sets = DB::table('settings')->whereNull('company_id')->first();
        }

        if ($sets) {
            $cardSets = DB::table('card_settings')->where('id', 1)->first();
            if ($cardSets) {
                // Map DB snake_case fields to frontend expected vcard_* fields
                $sets->vcard_ngn_fee = $cardSets->ngn_creation_fee;
                $sets->vcard_usd_fee = $cardSets->usd_creation_fee;
                $sets->vcard_usd_rate = $cardSets->ngn_rate;
                $sets->vcard_fund_fee = $cardSets->funding_fee_percent; // Legacy
                $sets->vcard_usd_failed_fee = $cardSets->usd_failed_tx_fee;
                $sets->vcard_ngn_fund_fee = $cardSets->ngn_funding_fee_percent;
                $sets->vcard_usd_fund_fee = $cardSets->usd_funding_fee_percent;
                $sets->vcard_ngn_failed_fee = $cardSets->ngn_failed_tx_fee;
            }
            return $sets;
        }
        return null;
    }

    public function habukhan_key($cid = null)
    {
        $query = DB::table('habukhan_key');
        if ($cid) {
            // Future-proofing: Assuming habukhan_key might eventually have company_id
            // For now, it stays global or maps to master settings
            // $query->where('company_id', $cid); 
        }

        if ($query->count() >= 1) {
            return $query->first();
        } else {
            return null;
        }
    }

    public function autopilot_request($endpoint, $payload)
    {
        $settings = $this->habukhan_key();
        if (!$settings || !isset($settings->autopilot_key)) {
            \Log::error('Autopilot API Error: Settings not configured');
            return ['success' => false, 'message' => 'Autopilot settings not configured'];
        }
        
        $key = str_replace(' ', '', $settings->autopilot_key);
        // Determine if we should use test or live based on key prefix
        $baseUrl = 'https://autopilotng.com/api/live';
        if (str_starts_with($key, 'test_')) {
            $baseUrl = 'https://autopilotng.com/api/test';
        }

        // Log API key info (first 10 chars only for security)
        \Log::info('Autopilot Request', [
            'endpoint' => $endpoint,
            'baseUrl' => $baseUrl,
            'key_preview' => substr($key, 0, 10) . '...',
            'key_type' => str_starts_with($key, 'test_') ? 'TEST' : 'LIVE'
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($baseUrl . $endpoint, $payload);

        if (!$response->successful()) {
            \Log::error('Autopilot API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'payload' => $payload,
                'response' => $response->body()
            ]);
        }

        return $response->json();
    }

    public function generateAutopilotReference()
    {
        $date = Carbon::now('Africa/Lagos')->format('YmdHi');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 15));
        return $date . $random; // 12 (date) + 15 (random) = 27 chars (min 25, max 30)
    }

    public function general()
    {
        $sets = DB::table('general');
        if ($sets->count() == 1) {
            return $sets->first();
        } else {
            return null;
        }
    }

    public function feature()
    {
        return DB::table('feature')->get();
    }


    public function updateData($data, $tablename, $tableid)
    {
        DB::table($tablename)
            ->where($tableid)
            ->update($data);
        return true;
    }


    public function generatetoken($req)
    {
        $user = \App\Models\User::find($req);
        if ($user) {
            $token = $user->createToken('auth-token')->plainTextToken;
            $user->update(['habukhan_key' => $token]);
            return $token;
        } else {
            return null;
        }
    }

    public function generateapptoken($key)
    {
        if (DB::table('users')->where('id', $key)->count() == 1) {
            $secure_key = bin2hex(random_bytes(32));
            DB::table('users')->where('id', $key)->update(['app_key' => $secure_key]);
            return $secure_key;
        } else {
            return null;
        }
    }
    public function verifyapptoken($key)
    {
        if (empty($key)) {
            return null;
        }

        // Strip Bearer prefix if present
        if (str_starts_with($key, 'Bearer ')) {
            $key = substr($key, 7);
        }

        $originalKey = $key; // Preserve original key

        $id = null;

        // 1. Check for Sanctum Token (ID|SECRET)
        if (strpos($key, '|') !== false) {
            $parts = explode('|', $key, 2);
            $tokenId = $parts[0];
            $tokenPlainText = $parts[1];

            // Safety: Handle URL encoded pipes if they sneak in
            if (strpos($tokenId, '%7C') !== false) {
                $parts = explode('%7C', $key, 2);
                $tokenId = $parts[0];
                $tokenPlainText = $parts[1];
            }

            $sanctumToken = DB::table('personal_access_tokens')
                ->where('id', $tokenId)
                ->first();

            if ($sanctumToken && hash_equals($sanctumToken->token, hash('sha256', $tokenPlainText))) {
                $id = $sanctumToken->tokenable_id;
            }

            // 1.5 Fallback for Legacy ID|KEY format
            if (!$id) {
                $key = $tokenPlainText; // Use only the secret part for legacy check
            }
        }

        // 2. Fallback to Legacy Columns
        if (!$id) {
            $check = DB::table('users')->where(function ($query) use ($key, $originalKey) {
                $query->where('app_key', $key)
                    ->orWhere('habukhan_key', $key)
                    ->orWhere('api_key', $key)
                    ->orWhere('habukhan_key', $originalKey)
                    ->orWhere('app_key', $originalKey)
                    ->orWhere('api_key', $originalKey);
            })->first();

            if ($check) {
                $id = $check->id;
            }
        }

        return $id;
    }

    public function verifytoken($request)
    {
        return $this->verifyapptoken($request);
    }

    /**
     * Verify the request origin against allowed app keys.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function verifyOrigin(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        return !$origin || in_array($origin, $explode_url) || $origin === $request->getSchemeAndHttpHost();
    }


    public function generate_ref($title)
    {
        $code = random_int(100000, 999999);
        $me = random_int(1000, 9999);
        $app_name = config('app.name');
        $ref = "|$app_name|$title|$code|habukhan-dev-$me";
        return $ref;
    }
    public function purchase_ref($d)
    {
        return uniqid($d);
    }
    public function insert_stock($username)
    {
        $check_first = DB::table('wallet_funding')->where('username', $username);
        if ($check_first->count() == 0) {
            $values = array('username' => $username);
            DB::table('wallet_funding')->insert($values);
        }
    }
    public function inserting_data($table, $data)
    {
        return DB::table($table)->insert($data);
    }
    public function xixapay_account($username)
    {
        // DISABLED: Remove unwanted integration
        return;
    }

    public function monnify_account($username)
    {
        // DISABLED: Remove unwanted integration
        return;
    }

    public function paymentpoint_account($username)
    {
        // DISABLED: Remove unwanted integration
        return;
    }
    public function system_date()
    {
        return Carbon::now("Africa/Lagos")->toDateTimeString();
    }

    public function paystack_account($username)
    {
        // DISABLED: Remove unwanted integration
        return;
    }
}
