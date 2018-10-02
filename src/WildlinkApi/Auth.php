<?php

namespace WildlinkApi;

class Auth
{
    public static function makeDeviceToken($uuid)
    {
        $date_time = date('Y-m-d H:i:sZ', time());
        @$post_obj->UUID = $uuid;
        $post_obj->OS = "Linux";
        $response = Request::request('makeDeviceToken', array("post_obj"=>$post_obj));
        $device_token = $response->DeviceToken;

        return $device_token;
    }

    public static function getAuth($date_time, $device_token = '', $sender_token = '')
    {
        $api_credentials_file = dirname(__FILE__) . "/data/api_credentials";
        $api_credentials = file_get_contents($api_credentials_file);

        // evaluate the credentials file to get the app id and secret
        preg_match('/app_id\s?=\s?([0-9]+)/', $api_credentials, $matches);
        $app_id = $matches[1];
        if (!$app_id || !is_numeric($app_id)){
            die('no app_id specified in src/WildlinkApi/data/api_credentials');
        }

        preg_match('/secret\s?=\s?(.*)/', $api_credentials, $matches);
        $secret = $matches[1];
        if (!$secret){
            die('no secret specified in src/WildlinkApi/data/api_credentials');
        }

        $signature_payload = $date_time . "\n" . $device_token . "\n" . $sender_token . "\n";
        $signature = hash_hmac("sha256", utf8_encode($signature_payload), $secret);
        $authorization = "WFAV1" . " " . $app_id . ":" . $signature . ":" . $device_token . ":" . $sender_token;

        return $authorization;
    }
}
