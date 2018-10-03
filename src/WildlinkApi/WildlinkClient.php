<?php

namespace WildlinkApi;

class WildlinkClient
{
    public $uuid;
    public $device_token;
    public $merchants;
    public $commissions;
    public $app_id;
    public $secret;

    private function getUuid()
    {
        if ($this->uuid){
            return $this->uuid;
        } else {
            // look to see if we have a cached value for the uuid
            $cached_uuid_dir = dirname(__FILE__) . "/data";
            $cached_uuid_file = $cached_uuid_dir . "/uuid";
            if (file_exists($cached_uuid_file)){
                $cached_uuid = file_get_contents($cached_uuid_file);
            }
            if (isset($cached_uuid)){
                $this->uuid = $cached_uuid;
            } else {
                // no cached uuid, so let's generate a new one
                $this->uuid = Uuid::makeUuid();

                // store it in cache
                if (!is_dir($cached_uuid_dir)){
                    mkdir($cached_uuid_dir, 0777, true);
                }
                file_put_contents($cached_uuid_file, $this->uuid);
            }

            return $this->uuid;
        }
    }

    public function __construct($app_id, $secret, $uuid = '')
    {
        $this->app_id = $app_id;
        $this->secret = $secret;

        if ($uuid){
            $this->uuid = (string) $uuid;
        } else {
            $uuid = $this->getUuid();
        }
        $this->device_token = $this->makeDeviceToken($this->uuid);
    }

    public function makeDeviceToken($uuid)
    {
        $date_time = date('Y-m-d H:i:sZ', time());
        @$post_obj->UUID = $uuid;
        $post_obj->OS = "Linux";
        $response = $this->request('makeDeviceToken', array("post_obj"=>$post_obj));
        $device_token = $response->DeviceToken;

        return $device_token;
    }

    public function getAuth($date_time, $device_token = '', $sender_token = '')
    {
        $signature_payload = $date_time . "\n" . $device_token . "\n" . $sender_token . "\n";
        $signature = hash_hmac("sha256", utf8_encode($signature_payload), $this->secret);
        $authorization = "WFAV1" . " " . $this->app_id . ":" . $signature . ":" . $device_token . ":" . $sender_token;

        return $authorization;
    }

    public function getEndpointInfo($function){
        if ($function == 'makeDeviceToken'){
            @$api_info->endpoint = '/v1/device';
            $api_info->method = 'POST';
        }

        if ($function == 'getMerchant'){
            @$api_info->endpoint = '/v2/merchant/?id=:id';
            $api_info->method = 'GET';
        }

        if ($function == 'getCommissionDetails'){
            @$api_info->endpoint = '/v1/device/:uuid/stats/commission-detail';
            $api_info->method = 'GET';
        }
        return $api_info;
    }

    public function request($function, $vars)
    {
        $api_url_base = "https://api.wfi.re";

        $api_info = $this->getEndpointInfo($function);
        $api_url = $api_url_base . $api_info->endpoint;

        if (is_array($vars)){
            foreach ($vars as $key => $val){
                if (is_string($val) || is_numeric($val)){
                    $api_url = str_replace(':'.$key, urlencode($val), $api_url);
                } elseif (is_array($val)){
                    unset($val_array);
                    foreach ($val as $value){
                        $val_array[] = urlencode($value);
                    }
                    $val_str = implode("&$key=", $val_array);
                    $api_url = str_replace(':'.$key, $val_str, $api_url);
                }
            }
        }

        if (@$vars['debug']){
            print_r($api_url);
        }

        $date_time = gmdate('Y-m-d\TH:i:s.v\Z', microtime(true));

        if ($function == 'makeDeviceToken'){
            $auth_token = $this->getAuth($date_time); // if the request is to gen a device token, then we don't pass one... since we don't have one yet
        } else {
            $auth_token = $this->getAuth($date_time, $this->device_token);
        }

        // get the composer.json data so we can use the version number in user-agent
        $composer_json = file_get_contents(dirname(__FILE__) . "/../../composer.json");
        $composer_data = json_decode($composer_json);

        $opts = array(
            'http' => array(
                'method'  => strtoupper($api_info->method),
                'ignore_errors' => true,
                'header'  => "Content-Type: application/json\r\n" .
                    "Authorization: " . $auth_token . "\r\n" .
                    "X-WF-DateTime: " . $date_time . "\r\n" .
                    "User-Agent: API-Client-PHP v" . $composer_data->version . "\r\n",
                'timeout' => 60
            )
        );

        if (@$vars['post_obj']){
            $opts['http']['content'] = json_encode($vars['post_obj']);
        }

        if (@$vars['debug']){
            echo("\n\nauth token : " . print_r($auth_token, 1));
            echo("\n\npost data : " . print_r($opts, 1));
        }

        $context = stream_context_create($opts);
        $result_json = file_get_contents($api_url, false, $context);
        $result = json_decode($result_json);

        if (@$vars['debug']){
            echo "\n\nresult:\n\n";
            print_r($result_json);
        }

        return $result;
    }

    public function getMerchants($ids)
    {
        $this->merchants = $this->request('getMerchant', array("id" => $ids));
    }

    public function getCommissionDetails()
    {
        $this->commissions = $this->request('getCommissionDetails', array("uuid" => $this->uuid));
    }

}
