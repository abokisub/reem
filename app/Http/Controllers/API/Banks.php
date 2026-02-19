<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Banks extends Controller
{
    public function GetBanksArray(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->header('Origin');
        $authorization = $request->header('Authorization');
        if (!$origin || in_array($origin, $explode_url) || $origin === $request->getSchemeAndHttpHost() || config('app.habukhan_device_key') === $authorization) {
            if (!empty($request->id)) {
                $auth_user = DB::table('users')->where('status', 'active')->where(function ($query) use ($request) {
                    $query->orWhere('id', $this->verifytoken($request->id))
                        ->orWhere('id', $this->verifyapptoken($request->id));
                })->first();

                $setting = $this->core();
                if (!$auth_user) {
                    return response()->json(['message' => 'Unable to singin user', 'status' => 'fail'], 403);
                }
                // Use dynamic charges from settings
                $monnify_charge = isset($setting->monnify_charge) ? $setting->monnify_charge : 20;
                $paystack_charge = isset($setting->paystack_charge) ? $setting->paystack_charge : 0;
                $paymentpoint_charge = isset($setting->paymentpoint_charge) ? $setting->paymentpoint_charge : 60;
                $xixapay_charge = isset($setting->xixapay_charge) ? $setting->xixapay_charge : 60;

                // Determine active PalmPay provider charge
                $habukhan_key = DB::table('habukhan_key')->first();
                // If PaymentPoint credentials exist, prioritize its charge for PalmPay entries
                $palmpay_charge = (!empty($habukhan_key->plive)) ? $paymentpoint_charge : $xixapay_charge;

                // Fetch settings to check enabled providers
                try {
                    $settings = DB::table('settings')->select(
                        'palmpay_enabled',
                        'monnify_enabled',
                        'wema_enabled',
                        'xixapay_enabled'
                    )->first();

                    $monnify_enabled = $settings->monnify_enabled ?? true;
                    $wema_enabled = $settings->wema_enabled ?? true;
                    $xixapay_enabled = $settings->xixapay_enabled ?? true;
                    $palmpay_enabled = $settings->palmpay_enabled ?? true;
                } catch (\Exception $e) {
                    $monnify_enabled = true;
                    $wema_enabled = true;
                    $xixapay_enabled = true;
                    $palmpay_enabled = true;
                }

                $banks_array = [];

                // Fetch all active Virtual Accounts for the user (respecting soft deletes)
                $activeVirtualAccounts = DB::table('virtual_accounts')
                    ->where('user_id', $auth_user->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($activeVirtualAccounts as $va) {
                    $banks_array[] = [
                        "name" => $va->bank_name ?? $va->palmpay_bank_name ?? "PalmPay",
                        "account" => $va->account_number ?? $va->palmpay_account_number,
                        "accountType" => false,
                        'charges' => $palmpay_charge . ' NAIRA',
                    ];
                }

                // Check if PalmPay is already in the list (from virtual_accounts)
                $hasPalmPay = collect($banks_array)->contains(function ($bank) {
                    return stripos($bank['name'], 'PalmPay') !== false;
                });

                // If PalmPay is missing from virtual_accounts, add from legacy columns or company details
                if (!$hasPalmPay) {
                    $palmpay_account = $auth_user->palmpay_account_number;
                    $palmpay_bank_name = $auth_user->palmpay_bank_name ?? "PalmPay";

                    // If user doesn't have Palmpay details, check if they have a company with details
                    if (empty($palmpay_account)) {
                        $company = DB::table('companies')->where('user_id', $auth_user->id)->first();
                        if ($company && !empty($company->account_number)) {
                            $palmpay_account = $company->account_number;
                            $palmpay_bank_name = $company->bank_name ?? "PalmPay";
                        }
                    }

                    if (!empty($palmpay_account) && $palmpay_enabled) {
                        $banks_array[] = [
                            "name" => $palmpay_bank_name,
                            "account" => $palmpay_account,
                            "accountType" => false,
                            'charges' => $palmpay_charge . ' NAIRA',
                        ];
                    }
                }


                // 2. Wema (Secondary)
                if (!empty($auth_user->paystack_account) && $wema_enabled) {
                    $banks_array[] = [
                        "name" => $auth_user->paystack_bank ?? "WEMA BANK",
                        "account" => $auth_user->paystack_account,
                        "accountType" => false,
                        'charges' => $paystack_charge . ' NAIRA',
                    ];
                }

                return response()->json(['status' => 'success', 'banks' => $banks_array]);
            } else {
                return response()->json(['status' => 'fail', 'message' => 'Hey,Login To Continue'])->setStatusCode(403);
            }
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Cannot Retrieve Banks'])->setStatusCode(403);
        }
    }

    /**
     * Get Nigerian Banks List for Transfers
     * Fetches from Xixapay or Paystack API and caches for 24 hours
     * Optimized for large bank lists
     */
    public function GetBanksList(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->header('Origin');
        $authorization = $request->header('Authorization');

        if (!$origin || in_array($origin, $explode_url) || $origin === $request->getSchemeAndHttpHost() || config('app.habukhan_device_key') === $authorization) {
            if (!empty($request->id)) {
                // Verify user authentication
                $auth_user = DB::table('users')->where('status', 'active')->where(function ($query) use ($request) {
                    $query->orWhere('id', $this->verifytoken($request->id))
                        ->orWhere('id', $this->verifyapptoken($request->id));
                })->first();

                if (!$auth_user) {
                    return response()->json(['message' => 'Unable to signin user', 'status' => 'fail'], 403);
                }

                try {
                    // Use generic BankingService to fetch Unified Bank List
                    $service = new \App\Services\Banking\BankingService();
                    $banks = $service->getSupportedBanks();

                    // Safety: If sync hasn't been run yet (empty DB), use fallback
                    if ($banks->isEmpty()) {
                        $banks = $this->getFallbackBanks();
                    }

                    return response()->json([
                        'status' => 'success',
                        'data' => $banks,
                        'count' => count($banks)
                    ]);


                } catch (\Exception $e) {
                    Log::error('GetBanksList Error: ' . $e->getMessage());

                    // Return fallback banks if API fails
                    $fallbackBanks = $this->getFallbackBanks();

                    return response()->json([
                        'status' => 'success',
                        'data' => $fallbackBanks,
                        'fallback' => true,
                        'message' => 'Using cached bank list'
                    ]);
                }
            } else {
                return response()->json(['status' => 'fail', 'message' => 'Hey,Login To Continue'])->setStatusCode(403);
            }
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Cannot Retrieve Banks'])->setStatusCode(403);
        }
    }

    public function syncBanks()
    {
        try {
            $service = new \App\Services\Banking\BankingService();
            $count = $service->syncBanksFromProvider('paystack');
            return response()->json([
                'status' => 'success',
                'message' => "Successfully synced $count banks to the unified database."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fallback banks list for when API is unavailable
     * Complete list of all Nigerian banks
     */
    private function getFallbackBanks()
    {
        // Return from database if available
        $dbBanks = DB::table('banks')
            ->where('active', true)
            ->orderBy('name', 'asc')
            ->get(['name', 'code']);

        if ($dbBanks->isNotEmpty()) {
            return $dbBanks->map(function ($bank) {
                return [
                    'name' => $bank->name,
                    'bankName' => $bank->name,
                    'code' => $bank->code,
                    'institutionCode' => $bank->code,
                    'active' => true
                ];
            })->toArray();
        }

        // Static fallback if database is empty
        return [
            ['name' => 'Access Bank', 'bankName' => 'Access Bank', 'code' => '044', 'institutionCode' => '044', 'active' => true],
            ['name' => 'Citibank Nigeria', 'bankName' => 'Citibank Nigeria', 'code' => '023', 'institutionCode' => '023', 'active' => true],
            ['name' => 'Ecobank Nigeria', 'bankName' => 'Ecobank Nigeria', 'code' => '050', 'institutionCode' => '050', 'active' => true],
            ['name' => 'Fidelity Bank', 'bankName' => 'Fidelity Bank', 'code' => '070', 'institutionCode' => '070', 'active' => true],
            ['name' => 'First Bank of Nigeria', 'bankName' => 'First Bank of Nigeria', 'code' => '011', 'institutionCode' => '011', 'active' => true],
            ['name' => 'First City Monument Bank (FCMB)', 'bankName' => 'First City Monument Bank (FCMB)', 'code' => '214', 'institutionCode' => '214', 'active' => true],
            ['name' => 'Guaranty Trust Bank (GTBank)', 'bankName' => 'Guaranty Trust Bank (GTBank)', 'code' => '058', 'institutionCode' => '058', 'active' => true],
            ['name' => 'Heritage Bank', 'bankName' => 'Heritage Bank', 'code' => '030', 'institutionCode' => '030', 'active' => true],
            ['name' => 'Keystone Bank', 'bankName' => 'Keystone Bank', 'code' => '082', 'institutionCode' => '082', 'active' => true],
            ['name' => 'Kuda Bank', 'bankName' => 'Kuda Bank', 'code' => '50211', 'institutionCode' => '50211', 'active' => true],
            ['name' => 'Moniepoint MFB', 'bankName' => 'Moniepoint MFB', 'code' => '50515', 'institutionCode' => '50515', 'active' => true],
            ['name' => 'OPay', 'bankName' => 'OPay', 'code' => '999992', 'institutionCode' => '999992', 'active' => true],
            ['name' => 'PalmPay', 'bankName' => 'PalmPay', 'code' => '999991', 'institutionCode' => '999991', 'active' => true],
            ['name' => 'Polaris Bank', 'bankName' => 'Polaris Bank', 'code' => '076', 'institutionCode' => '076', 'active' => true],
            ['name' => 'Providus Bank', 'bankName' => 'Providus Bank', 'code' => '101', 'institutionCode' => '101', 'active' => true],
            ['name' => 'Stanbic IBTC Bank', 'bankName' => 'Stanbic IBTC Bank', 'code' => '221', 'institutionCode' => '221', 'active' => true],
            ['name' => 'Standard Chartered Bank', 'bankName' => 'Standard Chartered Bank', 'code' => '068', 'institutionCode' => '068', 'active' => true],
            ['name' => 'Sterling Bank', 'bankName' => 'Sterling Bank', 'code' => '232', 'institutionCode' => '232', 'active' => true],
            ['name' => 'Union Bank of Nigeria', 'bankName' => 'Union Bank of Nigeria', 'code' => '032', 'institutionCode' => '032', 'active' => true],
            ['name' => 'United Bank For Africa (UBA)', 'bankName' => 'United Bank For Africa (UBA)', 'code' => '033', 'institutionCode' => '033', 'active' => true],
            ['name' => 'Unity Bank', 'bankName' => 'Unity Bank', 'code' => '215', 'institutionCode' => '215', 'active' => true],
            ['name' => 'Wema Bank', 'bankName' => 'Wema Bank', 'code' => '035', 'institutionCode' => '035', 'active' => true],
            ['name' => 'Zenith Bank', 'bankName' => 'Zenith Bank', 'code' => '057', 'institutionCode' => '057', 'active' => true],
        ];
    }

    private function fetchXixapayBanks()
    {
        $response = Http::timeout(30)->get('https://api.xixapay.com/api/get/banks');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch banks from Xixapay');
        }

        $data = $response->json();

        // Xixapay format: { "status": "success", "data": [{"bankName": "Access Bank", "bankCode": "044"}] }
        if (!isset($data['status']) || $data['status'] !== 'success' || !isset($data['data'])) {
            // Fallback if structure is different
            throw new \Exception('Invalid response from Xixapay Banks API');
        }

        return collect($data['data'])->map(function ($bank) {
            return [
                'name' => $bank['bankName'],
                'bankName' => $bank['bankName'],
                'code' => $bank['bankCode'],
                'institutionCode' => $bank['bankCode'],
                'active' => true,
                'type' => 'nuban' // Default type
            ];
        })
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    private function fetchPaystackBanks()
    {
        $paystackKey = DB::table('paystack_key')->first();
        if (!$paystackKey || empty($paystackKey->live)) {
            throw new \Exception('Paystack API key not configured');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $paystackKey->live,
                'Content-Type' => 'application/json'
            ])
            ->get('https://api.paystack.co/bank', [
                'country' => 'nigeria',
                'perPage' => 100
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch banks from Paystack');
        }

        $data = $response->json();

        return collect($data['data'])->map(function ($bank) {
            return [
                'name' => $bank['name'],
                'bankName' => $bank['name'],
                'code' => $bank['code'],
                'institutionCode' => $bank['code'],
                'slug' => $bank['slug'] ?? strtolower(str_replace(' ', '-', $bank['name'])),
                'active' => $bank['active'] ?? true,
                'type' => $bank['type'] ?? 'nuban'
            ];
        })
            ->filter(function ($bank) {
                return $bank['active'] === true;
            })
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    /**
     * Get public bank list for onboarding (no authentication required)
     * Syncs from PalmPay API and caches locally
     */
    public function getPublicBanksList()
    {
        $palmPayBanks = [];
        try {
            // 1. Initial fetch from local cache
            $cachedBanks = DB::table('banks')
                ->where('active', true)
                ->orderBy('name', 'asc')
                ->select('id', 'name', 'code')
                ->get();

            Log::info('getPublicBanksList: Found ' . $cachedBanks->count() . ' banks in local cache.');

            // 2. Attempt Sync from PalmPay
            try {
                $transferService = app(\App\Services\PalmPay\TransferService::class);
                $palmPayBanks = $transferService->getBankList();

                if (!empty($palmPayBanks)) {
                    Log::info('getPublicBanksList: Syncing ' . count($palmPayBanks) . ' banks from PalmPay.');
                    foreach ($palmPayBanks as $bank) {
                        DB::table('banks')->updateOrInsert(
                            ['code' => $bank['bankCode']],
                            [
                                'name' => $bank['bankName'],
                                'active' => true,
                                'updated_at' => now()
                            ]
                        );
                    }

                    // Refresh cache after sync
                    $cachedBanks = DB::table('banks')
                        ->where('active', true)
                        ->orderBy('name', 'asc')
                        ->select('id', 'name', 'code')
                        ->get();
                    Log::info('getPublicBanksList: Cache refreshed with ' . $cachedBanks->count() . ' banks.');
                }
            } catch (\Exception $apiError) {
                Log::warning('PalmPay Bank Sync failed, using local cache: ' . $apiError->getMessage());
            }

            // 3. Fallback to static list if still empty
            if ($cachedBanks->isEmpty()) {
                Log::info('getPublicBanksList: Cache is empty, using fallback list.');
                $cachedBanks = collect($this->getFallbackBanks())->map(function ($bank) {
                    return (object) [
                        'id' => $bank['code'],
                        'name' => $bank['name'],
                        'code' => $bank['code']
                    ];
                });
            }

            return response()->json([
                'status' => 'success',
                'data' => $cachedBanks,
                'source' => empty($palmPayBanks) ? 'cache' : 'palmpay'
            ]);
        } catch (\Exception $e) {
            Log::error('getPublicBanksList Critical Error: ' . $e->getMessage());

            $fallbackBanks = collect($this->getFallbackBanks())->map(function ($bank) {
                return (object) [
                    'id' => $bank['code'],
                    'name' => $bank['name'],
                    'code' => $bank['code']
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $fallbackBanks,
                'source' => 'fallback_error'
            ]);
        }
    }
}