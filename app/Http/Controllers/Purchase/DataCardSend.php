<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DataCardSend extends Controller
{
    public static function Habukhan1($data)
    {
        if (DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan1_username . ":" . $other_api->habukhan1_password);
            $data_card_plan = DB::table('data_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $data_card_plan->habukhan1,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website1,
                'endpoint' => $other_api->habukhan_website1 . "/api/data_card/",
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
                                if (DB::table('dump_data_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_data_card_pin')->insert($store_bulk);
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
        if (DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan2_username . ":" . $other_api->habukhan2_password);
            $data_card_plan = DB::table('data_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $data_card_plan->habukhan2,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website2,
                'endpoint' => $other_api->habukhan_website2 . "/api/data_card/",
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
                                if (DB::table('dump_data_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_data_card_pin')->insert($store_bulk);
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
        if (DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan3_username . ":" . $other_api->habukhan3_password);
            $data_card_plan = DB::table('data_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $data_card_plan->habukhan3,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website3,
                'endpoint' => $other_api->habukhan_website3 . "/api/data_card/",
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
                                if (DB::table('dump_data_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_data_card_pin')->insert($store_bulk);
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
        if (DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan4_username . ":" . $other_api->habukhan4_password);
            $data_card_plan = DB::table('data_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $data_card_plan->habukhan4,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website4,
                'endpoint' => $other_api->habukhan_website4 . "/api/data_card/",
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
                                if (DB::table('dump_data_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_data_card_pin')->insert($store_bulk);
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
        if (DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $network = DB::table('network')->where('network', $sendRequest->network)->first();
            $accessToken = base64_encode($other_api->habukhan5_username . ":" . $other_api->habukhan5_password);
            $data_card_plan = DB::table('data_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $paypload = array(
                'network' => $network->habukhan_id,
                'quantity' => $sendRequest->quantity,
                'plan_type' => $data_card_plan->habukhan5,
                'card_name' => $sendRequest->card_name,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website5,
                'endpoint' => $other_api->habukhan_website5 . "/api/data_card/",
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
                                if (DB::table('dump_data_card_pin')->where(['network' => $network->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                    if ((!empty($load_pin)) and (!empty($load_serial))) {
                                        $store_bulk = [
                                            'username' => $sendRequest->username,
                                            'serial' => $load_serial,
                                            'pin' => $load_pin,
                                            'network' => $sendRequest->network,
                                            'date' => $sendRequest->plan_date,
                                            'transid' => $sendRequest->transid,
                                        ];
                                        DB::table('dump_data_card_pin')->insert($store_bulk);
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
        if (DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $data_card_plan = DB::table('data_card_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            if (DB::table('store_data_card')->where(['network' => $sendRequest->network, 'plan_status' => 0, 'data_card_id' => $data_card_plan->plan_id])->take($sendRequest->quantity)->count() >= $sendRequest->quantity) {
                $habukhan_pin = DB::table('store_data_card')->where(['network' => $sendRequest->network, 'plan_status' => 0, 'data_card_id' => $data_card_plan->plan_id])->take($sendRequest->quantity)->get();
                $pin_i = null;
                $serial_i = null;

                foreach ($habukhan_pin as $boss) {

                    $pin_i[] = $boss->pin;
                    $serial_i[] = $boss->serial;


                    DB::table('store_data_card')->where(['id' => $boss->id])->update(['plan_status' => 1, 'buyer_username' => $sendRequest->username, 'bought_date' => $sendRequest->plan_date]);
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
                            if (DB::table('dump_data_card_pin')->where(['network' => $sendRequest->network, 'serial' => $load_serial, 'pin' => $load_pin, 'transid' => $sendRequest->transid])->count() == 0) {
                                if ((!empty($load_pin)) and (!empty($load_serial))) {
                                    $store_bulk = [
                                        'username' => $sendRequest->username,
                                        'serial' => $load_serial,
                                        'pin' => $load_pin,
                                        'network' => $sendRequest->network,
                                        'date' => $sendRequest->plan_date,
                                        'transid' => $sendRequest->transid,
                                    ];
                                    DB::table('dump_data_card_pin')->insert($store_bulk);
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
     * KoboPoint Data Card Printing Integration
     * Complete implementation for KoboPoint API
     */
    public static function Kobopoint($data)
    {
        if (DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data_card')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $card_plan = DB::table('data_card_plan')->where('plan_id', $data['plan_id'])->first();
            
            try {
                // Use KoboPoint Service
                $kobopointService = new \App\Services\KobopointService();
                
                // Get data card plans first
                $plansResponse = $kobopointService->getDataCardPlans();
                
                if (empty($plansResponse) || $plansResponse['status'] !== 'success') {
                    \Log::error('KoboPoint Data Card: Failed to get plans', [
                        'transid' => $data['transid'],
                        'response' => $plansResponse
                    ]);
                    return 'fail';
                }
                
                // Find matching plan by network and plan details
                $kobopointPlanId = null;
                $plans = $plansResponse['data'] ?? [];
                
                foreach ($plans as $plan) {
                    if ($plan['network'] === $card_plan->network && 
                        stripos($plan['plan'], $card_plan->plan_name) !== false) {
                        $kobopointPlanId = $plan['id'];
                        break;
                    }
                }
                
                if (!$kobopointPlanId) {
                    \Log::error('KoboPoint Data Card: No matching plan found', [
                        'transid' => $data['transid'],
                        'network' => $card_plan->network,
                        'plan_name' => $card_plan->plan_name
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
                
                \Log::info('KoboPoint Data Card REQUEST:', [
                    'transid' => $data['transid'],
                    'network_id' => $networkId,
                    'plan_id' => $kobopointPlanId,
                    'quantity' => $sendRequest->quantity ?? 1
                ]);
                
                // Call KoboPoint API
                $response = $kobopointService->printDataCards(
                    $networkId,
                    $kobopointPlanId,
                    $sendRequest->quantity ?? 1
                );
                
                \Log::info('KoboPoint Data Card RESPONSE:', [
                    'transid' => $data['transid'],
                    'response' => $response
                ]);
                
                if (!empty($response)) {
                    $status = $response['status'] ?? '';
                    
                    if ($status === 'success') {
                        // Store KoboPoint reference if available
                        if (isset($response['reference'])) {
                            DB::table('data_card')->where('transid', $data['transid'])
                                ->update(['api_reference' => $response['reference']]);
                        }
                        
                        // Store card details
                        if (isset($response['cards']) && is_array($response['cards'])) {
                            $cardDetails = '';
                            foreach ($response['cards'] as $card) {
                                $cardDetails .= "Serial: " . ($card['serial'] ?? '') . " PIN: " . ($card['pin'] ?? '') . " Plan: " . ($card['plan'] ?? '') . "\n";
                            }
                            DB::table('data_card')->where('transid', $data['transid'])
                                ->update(['token' => $cardDetails]);
                        }
                        
                        \Log::info('KoboPoint Data Card: Returning SUCCESS', ['transid' => $data['transid']]);
                        return 'success';
                    } else if ($status === 'fail') {
                        \Log::info('KoboPoint Data Card: Returning FAIL', [
                            'transid' => $data['transid'],
                            'message' => $response['message'] ?? 'Unknown error'
                        ]);
                        return 'fail';
                    } else {
                        \Log::info('KoboPoint Data Card: Returning PROCESS', ['transid' => $data['transid']]);
                        return 'process';
                    }
                } else {
                    \Log::warning('KoboPoint Data Card: Empty response', ['transid' => $data['transid']]);
                    return 'fail';
                }
                
            } catch (\Exception $e) {
                \Log::error('KoboPoint Data Card Error:', [
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
