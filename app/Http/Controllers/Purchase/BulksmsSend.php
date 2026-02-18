<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BulksmsSend extends Controller
{
    public static function Habukhan1($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $accessToken = base64_encode($other_api->habukhan1_username . ":" . $other_api->habukhan1_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website1,
                'endpoint' => $other_api->habukhan_website1 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
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
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $accessToken = base64_encode($other_api->habukhan2_username . ":" . $other_api->habukhan2_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website2,
                'endpoint' => $other_api->habukhan_website2 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
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
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $accessToken = base64_encode($other_api->habukhan3_username . ":" . $other_api->habukhan3_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website3,
                'endpoint' => $other_api->habukhan_website3 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
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

        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $accessToken = base64_encode($other_api->habukhan4_username . ":" . $other_api->habukhan4_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website4,
                'endpoint' => $other_api->habukhan_website4 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
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
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $accessToken = base64_encode($other_api->habukhan5_username . ":" . $other_api->habukhan5_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $other_api->habukhan_website5,
                'endpoint' => $other_api->habukhan_website5 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
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
    public function Hollatag($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();

            $request = array(
                "user" => $other_api->hollatag_username,
                "pass" => $other_api->hollatag_password,
                "from" => $sendRequest->sender_name,
                "to" => $sendRequest->correct_number,
                "msg" => $sendRequest->message,
                "type" => 0,
            );

            $url = 'https://sms.hollatags.com/api/send/';  //this is the url of the gateway's interface

            $ch = curl_init(); //initialize curl handle
            curl_setopt($ch, CURLOPT_URL, $url); //set the url
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request)); //set the POST variables
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return as a variable
            curl_setopt($ch, CURLOPT_POST, 1); //set POST method

            $response_sms = curl_exec($ch);      // grab URL and pass it to the browser. Run the whole process and return the response
            curl_close($ch); //close the curl handle
            if (!empty($response_sms)) {
                if ($response_sms == "sent") {
                    return 'success';
                } else {
                    return 'fail';
                }
            } else {
                return 'proccess';
            }
        } else {
            return 'fail';
        }
    }
}
