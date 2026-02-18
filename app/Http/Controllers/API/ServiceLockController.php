<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceLockController extends Controller
{
    /**
     * Get all service lock statuses
     */
    public function index(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $locks = DB::table('service_lock')->first();
            return response()->json([
                'status' => 'success',
                'data' => $locks
            ]);
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    /**
     * Get filtered "Other" locks (KYC focus)
     */
    public function getOtherLocks(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $locks = DB::table('service_lock')->first();

            // Define KYC services to expose
            $kycServices = [
                'kyc_enhanced_bvn' => 'Enhanced BVN Verification',
                'kyc_enhanced_nin' => 'Enhanced NIN Verification',
                'kyc_basic_bvn' => 'Basic BVN Verification',
                'kyc_basic_nin' => 'Basic NIN Verification',
                'kyc_liveness' => 'Liveness Detection',
                'kyc_face_compare' => 'Face Comparison',
                'kyc_bank_verify' => 'BVN and Bank Account Verification',
                'kyc_credit_score' => 'Credit Score Services',
                'kyc_loan' => 'Loan Feature',
                'kyc_blacklist' => 'Blacklist Check'
            ];

            $data = [];
            foreach ($kycServices as $key => $name) {
                $data[$key] = [
                    'name' => $name,
                    'status' => !((bool) ($locks->$key ?? false)) // true if NOT locked (Enabled)
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    /**
     * Update service lock status
     */
    public function updateLock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('users')->where(['status' => 'active', 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $service = $request->service;
                    $locked = $request->locked; // true or false

                    // Validate service including new KYC ones
                    $validServices = [
                        'airtime',
                        'data',
                        'cable',
                        'bill',
                        'result',
                        'data_card',
                        'recharge_card',
                        'virtual_accounts',
                        'bulksms',
                        'cash',
                        'kyc_enhanced_bvn',
                        'kyc_enhanced_nin',
                        'kyc_basic_bvn',
                        'kyc_basic_nin',
                        'kyc_liveness',
                        'kyc_face_compare',
                        'kyc_bank_verify',
                        'kyc_credit_score',
                        'kyc_loan',
                        'kyc_blacklist'
                    ];

                    if (!in_array($service, $validServices)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid service'
                        ]);
                    }

                    // Update lock status
                    DB::table('service_lock')->where('id', 1)->update([
                        $service => $locked ? 1 : 0,
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => str_replace('_', ' ', ucfirst($service)) . ' service ' . ($locked ? 'locked' : 'unlocked') . ' successfully'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    /**
     * Check if a service is locked
     * Used by vending controllers
     */
    public static function isLocked($service)
    {
        $lock = DB::table('service_lock')->first();
        if ($lock && isset($lock->$service)) {
            return (bool) $lock->$service;
        }
        return false;
    }

    // Individual Lock Methods for clean routes
    public function lockAirtime(Request $request)
    {
        $request->merge(['service' => 'airtime']);
        return $this->updateLock($request);
    }
    public function lockData(Request $request)
    {
        $request->merge(['service' => 'data']);
        return $this->updateLock($request);
    }
    public function lockCable(Request $request)
    {
        $request->merge(['service' => 'cable']);
        return $this->updateLock($request);
    }
    public function lockResult(Request $request)
    {
        $request->merge(['service' => 'result']);
        return $this->updateLock($request);
    }
    public function lockDataCard(Request $request)
    {
        $request->merge(['service' => 'data_card']);
        return $this->updateLock($request);
    }
    public function lockRechargeCard(Request $request)
    {
        $request->merge(['service' => 'recharge_card']);
        return $this->updateLock($request);
    }
    public function lockVirtualAccounts(Request $request)
    {
        $request->merge(['service' => 'virtual_accounts']);
        return $this->updateLock($request);
    }

}
