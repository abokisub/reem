<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RechargeCardSend extends Controller
{
    public static function Habukhan1($data)
    {
        if (DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan1_username . ":" . $other_api->habukhan1_password);
            $recharge_card_plan = DB::table('recharge_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $recharge_card_plan->habukhan1,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website1,
                'endpoint' => $other_api->habukhan_website1 . "/api/recharge_card/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = explode(',', $response['pin']);
                    $serial = explode(',', $response['serial']);
                    // store pin for dump
                    for ($i = 0; $i < count($pin); $i++) {
                        $load_pin = $pin[$i];
                        $j = $i;
                        for ($a = 0; $a < count($serial); $a++) {
                            $load_serial = $serial[$a];
                            if ($j == $a) {
                                if (DB::table('dump_recharge_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_recharge_card_pin')->insert($store_bulk);
                                    }
                                }
                            }
                        }
                    }

                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Habukhan2($data)
    {
        if (DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan2_username . ":" . $other_api->habukhan2_password);
            $recharge_card_plan = DB::table('recharge_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $recharge_card_plan->habukhan2,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website2,
                'endpoint' => $other_api->habukhan_website2 . "/api/recharge_card/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = explode(',', $response['pin']);
                    $serial = explode(',', $response['serial']);
                    // store pin for dump
                    for ($i = 0; $i < count($pin); $i++) {
                        $load_pin = $pin[$i];
                        $j = $i;
                        for ($a = 0; $a < count($serial); $a++) {
                            $load_serial = $serial[$a];
                            if ($j == $a) {
                                if (DB::table('dump_recharge_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_recharge_card_pin')->insert($store_bulk);
                                    }
                                }
                            }
                        }
                    }

                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }

    public static function Habukhan3($data)
    {
        if (DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan3_username . ":" . $other_api->habukhan3_password);
            $recharge_card_plan = DB::table('recharge_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $recharge_card_plan->habukhan3,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website3,
                'endpoint' => $other_api->habukhan_website3 . "/api/recharge_card/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = explode(',', $response['pin']);
                    $serial = explode(',', $response['serial']);
                    // store pin for dump
                    for ($i = 0; $i < count($pin); $i++) {
                        $load_pin = $pin[$i];
                        $j = $i;
                        for ($a = 0; $a < count($serial); $a++) {
                            $load_serial = $serial[$a];
                            if ($j == $a) {
                                if (DB::table('dump_recharge_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_recharge_card_pin')->insert($store_bulk);
                                    }
                                }
                            }
                        }
                    }

                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }

    public static function Habukhan4($data)
    {
        if (DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan4_username . ":" . $other_api->habukhan4_password);
            $recharge_card_plan = DB::table('recharge_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $recharge_card_plan->habukhan4,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website4,
                'endpoint' => $other_api->habukhan_website4 . "/api/recharge_card/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = explode(',', $response['pin']);
                    $serial = explode(',', $response['serial']);
                    // store pin for dump
                    for ($i = 0; $i < count($pin); $i++) {
                        $load_pin = $pin[$i];
                        $j = $i;
                        for ($a = 0; $a < count($serial); $a++) {
                            $load_serial = $serial[$a];
                            if ($j == $a) {
                                if (DB::table('dump_recharge_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_recharge_card_pin')->insert($store_bulk);
                                    }
                                }
                            }
                        }
                    }

                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }

    public static function Habukhan5($data)
    {
        if (DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan5_username . ":" . $other_api->habukhan5_password);
            $recharge_card_plan = DB::table('recharge_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $recharge_card_plan->habukhan5,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website5,
                'endpoint' => $other_api->habukhan_website5 . "/api/recharge_card/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = explode(',', $response['pin']);
                    $serial = explode(',', $response['serial']);
                    // store pin for dump
                    for ($i = 0; $i < count($pin); $i++) {
                        $load_pin = $pin[$i];
                        $j = $i;
                        for ($a = 0; $a < count($serial); $a++) {
                            $load_serial = $serial[$a];
                            if ($j == $a) {
                                if (DB::table('dump_recharge_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_recharge_card_pin')->insert($store_bulk);
                                    }
                                }
                            }
                        }
                    }

                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }

    public static function Self($data)
    {
        if (DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $data_card_plan = DB::table('recharge_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            if (DB::table('store_recharge_card')->where(['network' => $sendRequest->network, 'plan_status' => 0, 'recharge_card_id' => $data_card_plan->plan_id])->take($sendRequest->quantity)->count() >= $sendRequest->quantity) {
                $stock_pin = DB::table('store_recharge_card')->where(['network' => $sendRequest->network, 'plan_status' => 0, 'recharge_card_id' => $data_card_plan->plan_id])->take($sendRequest->quantity)->get();
                $pin_i = null;
                $serial_i = null;

                foreach ($stock_pin as $boss) {

                    $pin_i[] = $boss->pin;
                    $serial_i[] = $boss->serial;


                    DB::table('store_recharge_card')->where(['id' => $boss->id])->update(['plan_status' => 1, 'buyer_username' => $sendRequest->username, 'bought_date' => $sendRequest->plan_date]);
                }

                $pin_2 = implode(',', $pin_i);
                $serial_2 = implode(',', $serial_i);

                $pin = explode(',', $pin_2);
                $serial = explode(',', $serial_2);


                for ($i = 0; $i < count($pin); $i++) {
                    $load_pin = $pin[$i];
                    $j = $i;
                    for ($a = 0; $a < count($serial); $a++) {
                        $load_serial = $serial[$a];
                        if ($j == $a) {
                            if (DB::table('dump_recharge_card_pin')->where(['network' => $sendRequest->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                if ((!empty($load_pin)) and (!empty($load_serial))) {
                                    $store_bulk = [
                                        'username' => $sendRequest->username,
                                        'serial' => $load_serial,
                                        'pin' => $load_pin,
                                        'network' => $sendRequest->network,
                                        'date' => $sendRequest->plan_date,
                                        'transid' => $sendRequest->transid,
                                    ];
                                    DB::table('dump_recharge_card_pin')->insert($store_bulk);
                                }
                            }
                        }
                    }
                }

                return 'success';
            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }

    public static function Autopilot($data)
    {
        return 'fail';
    }

    public static function Boltnet($data)
    {
        return 'fail';
    }

    public static function Smeplug($data)
    {
        return 'fail';
    }

    public static function Msplug($data)
    {
        return null;
    }

    /**
     * KoboPoint Recharge Card Printing Integration
     * Complete implementation for KoboPoint API
     */
    public static function Kobopoint($data)
    {
        if (DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('recharge_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $card_plan = DB::table('recharge_card_plan')->where('plan_id', $data['plan_id'])->first();
            
            try {
                // Use KoboPoint Service
                $kobopointService = new \App\Services\KobopointService();
                
                // Get recharge card plans first
                $plansResponse = $kobopointService->getRechargeCardPlans();
                
                if (empty($plansResponse) || $plansResponse['status'] !== 'success') {
                    \Log::error('KoboPoint Recharge Card: Failed to get plans', [
                        'transid' => $data['transid'],
                        'response' => $plansResponse
                    ]);
                    return 'fail';
                }
                
                // Find matching plan by network and denomination
                $kobopointPlanId = null;
                $plans = $plansResponse['data'] ?? [];
                
                foreach ($plans as $plan) {
                    if ($plan['network'] === $card_plan->network && 
                        (int)$plan['denomination'] === (int)$card_plan->plan_amount) {
                        $kobopointPlanId = $plan['id'];
                        break;
                    }
                }
                
                if (!$kobopointPlanId) {
                    \Log::error('KoboPoint Recharge Card: No matching plan found', [
                        'transid' => $data['transid'],
                        'network' => $card_plan->network,
                        'denomination' => $card_plan->plan_amount
                    ]);
                    return 'fail';
                }
                
                // Map network names to KoboPoint network IDs
                $networkMap = [
                    'MTN' => '1',
                    'GLO' => '2', 
                    'AIRTEL' => '3',
                    '9MOBILE' => '4'
                ];
                
                $networkId = $networkMap[$card_plan->network] ?? '1';
                
                \Log::info('KoboPoint Recharge Card REQUEST:', [
                    'transid' => $data['transid'],
                    'network_id' => $networkId,
                    'plan_id' => $kobopointPlanId,
                    'quantity' => $sendRequest->quantity ?? 1
                ]);
                
                // Call KoboPoint API
                $response = $kobopointService->printRechargeCards(
                    $networkId,
                    $kobopointPlanId,
                    $sendRequest->quantity ?? 1
                );
                
                \Log::info('KoboPoint Recharge Card RESPONSE:', [
                    'transid' => $data['transid'],
                    'response' => $response
                ]);
                
                if (!empty($response)) {
                    $status = $response['status'] ?? '';
                    
                    if ($status === 'success') {
                        // Store KoboPoint reference if available
                        if (isset($response['reference'])) {
                            DB::table('recharge_card')->where('transid', $data['transid'])
                                ->update(['api_reference' => $response['reference']]);
                        }
                        
                        // Store card details
                        if (isset($response['cards']) && is_array($response['cards'])) {
                            $cardDetails = '';
                            foreach ($response['cards'] as $card) {
                                $cardDetails .= "Serial: " . ($card['serial'] ?? '') . " PIN: " . ($card['pin'] ?? '') . " Amount: ₦" . ($card['amount'] ?? '') . "\n";
                            }
                            DB::table('recharge_card')->where('transid', $data['transid'])
                                ->update(['token' => $cardDetails]);
                        }
                        
                        \Log::info('KoboPoint Recharge Card: Returning SUCCESS', ['transid' => $data['transid']]);
                        return 'success';
                    } else if ($status === 'fail') {
                        \Log::info('KoboPoint Recharge Card: Returning FAIL', [
                            'transid' => $data['transid'],
                            'message' => $response['message'] ?? 'Unknown error'
                        ]);
                        return 'fail';
                    } else {
                        \Log::info('KoboPoint Recharge Card: Returning PROCESS', ['transid' => $data['transid']]);
                        return 'process';
                    }
                } else {
                    \Log::warning('KoboPoint Recharge Card: Empty response', ['transid' => $data['transid']]);
                    return 'fail';
                }
                
            } catch (\Exception $e) {
                \Log::error('KoboPoint Recharge Card Error:', [
                    'transid' => $data['transid'],
                    'error' => $e->getMessage()
                ]);
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }
}
