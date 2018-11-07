<?php

namespace WildlinkApi;

class WildlinkClient
{
    public function __construct($app_id, $secret, $device_key = '', $device_token = '')
    {
        $this->app_id = $app_id;
        $this->secret = $secret;

        if ($device_key && $device_token){
            // device_key and device_token provided, so just store the values
            $this->device_key = $device_key;
            $this->device_token = $device_token;
        } else {
            // generate a device token, with the device_key if we have it
            $result = $this->makeDeviceToken($device_key);

            if ($result->DeviceToken){
                $this->device_token = $result->DeviceToken;
            }
            if ($result->DeviceKey){
                $this->uuid = $result->DeviceKey; // for legacy support... should remove documentation for this and retire it in the future
                $this->device_key = $result->DeviceKey;
                $this->device_id = $result->DeviceID;
            }
        }
    }

    public function makeDeviceToken($device_key = '')
    {
        $date_time = date('Y-m-d H:i:sZ', time());
        $post_obj = (object) [];
        if ($device_key){
            $post_obj->DeviceKey = $device_key;
            $post_obj->OS = "Linux";
        } else {
            $post_obj->OS = "Linux";
        }
        $response = $this->request('makeDeviceToken', array("post_obj"=>$post_obj));

        return $response;
    }

    public function getAuth($date_time, $device_token = '', $sender_token = '')
    {
        $signature_payload = $date_time . "\n" . $device_token . "\n" . $sender_token . "\n";
        $signature = hash_hmac("sha256", utf8_encode($signature_payload), $this->secret);
        $authorization = "WFAV1" . " " . $this->app_id . ":" . $signature . ":" . $device_token . ":" . $sender_token;

        return $authorization;
    }

    public function getEndpointInfo($function){
        $api_info = (object) [];

        if ($function == 'makeDeviceToken'){
            $api_info->endpoint = '/v2/device';
            $api_info->method = 'POST';
        }

        // MERCHANT functions
        if ($function == 'getMerchantsById'){
            $api_info->endpoint = '/v2/merchant/?id=:id';
            $api_info->method = 'GET';
        }

        if ($function == 'getEnabledMerchants'){
            $api_info->endpoint = '/v2/merchant/?disabled=false&cursor=:cursor&limit=:limit';
            $api_info->method = 'GET';
        }

        if ($function == 'getDisabledMerchants'){
            $api_info->endpoint = '/v2/merchant/?disabled=true&cursor=:cursor&limit=:limit';
            $api_info->method = 'GET';
        }

        // COMMISSION functions
        if ($function == 'getCommissionSummary'){
            $api_info->endpoint = '/v2/device/stats/commission-summary';
            $api_info->method = 'GET';
        }

        if ($function == 'getCommissionDetails'){
            $api_info->endpoint = '/v2/device/stats/commission-detail';
            $api_info->method = 'GET';
        }

        if ($function == 'getAppCommissionsSince'){
            $api_info->endpoint = '/v2/commission?start_modified_date=:modified_since&limit=:limit';
            $api_info->method = 'GET';
        }

        // CLICKS functions
        if ($function == 'getClickStats'){
            $api_info->endpoint = '/v2/device/stats/clicks?by=:by&start=:start&end=:end';
            $api_info->method = 'GET';
        }

        // VANITY URL functions
        if ($function == 'getVanityUrl'){
            $api_info->endpoint = '/v2/vanity';
            $api_info->method = 'POST';
        }

        return $api_info;
    }

    public function request($function, $vars = null)
    {
        #$vars['debug'] = true;

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

        if (isset($vars['debug'])){
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

        if (isset($vars['post_obj'])){
            $opts['http']['content'] = json_encode($vars['post_obj']);
        }

        if (isset($vars['debug'])){
            echo("\n\nauth token : " . print_r($auth_token, 1));
            echo("\n\npost data : " . print_r($opts, 1));
        }

        $context = stream_context_create($opts);
        $result_json = file_get_contents($api_url, false, $context);
        $result = json_decode($result_json);

        if (isset($vars['debug'])){
            echo "\n\nresult:\n\n";
            print_r($result_json);
        }

        return $result;
    }

    // MERCHANT functions
    public function getMerchantsById($ids)
    {
        $result = $this->request('getMerchantsById', array("id" => $ids));
        if ($result->Merchants){
            return $result->Merchants;
        } else {
            return $result;
        }
    }

    public function getAllEnabledMerchants()
    {
        $merchants = [];

        $result = $this->request('getEnabledMerchants', [
            'cursor' => ''
        ]);
        if ($result->Merchants){
            // request next pages until there are none left
            $merchants += $result->Merchants;
            while ($result->NextCursor){
                $result = $this->request('getEnabledMerchants', [
                    'cursor' => $result->NextCursor,
                    'limit' => 500
                ]);
                if ($result->Merchants){
                    $merchants = array_merge($merchants, $result->Merchants);
                }
            }
            return $merchants;
        } else {
            return $result;
        }
    }

    public function getAllDisabledMerchants()
    {
        $disabledMerchants = [];

        $result = $this->request('getDisabledMerchants', [
            'cursor' => ''
        ]);
        if ($result->Merchants){
            // request next pages until there are none left
            $disabledMerchants += $result->Merchants;
            while ($result->NextCursor){
                $result = $this->request('getDisabledMerchants', [
                    'cursor' => $result->NextCursor,
                    'limit' => 500
                ]);
                if ($result->Merchants){
                    $disabledMerchants = array_merge($disabledMerchants, $result->Merchants);
                }
            }
            return $disabledMerchants;
        } else {
            return $result;
        }
    }

    public function getPagedEnabledMerchants()
    {
        if (!isset($this->merchantListCursor)){
            $this->merchantListCursor = '';
        }

        $result = $this->request('getEnabledMerchants', [
            'cursor' => $this->merchantListCursor,
            'limit' => 500
        ]);
        if (!isset($result->Merchants)){
            return $result;
        } else {
            $this->merchantListCursor = $result->NextCursor;
            return $result->Merchants;
        }
    }

    public function getPagedDisabledMerchants()
    {
        if (!isset($this->merchantListCursor)){
            $this->merchantListCursor = '';
        }

        $result = $this->request('getDisabledMerchants', [
            'cursor' => $this->merchantListCursor,
            'limit' => 500
        ]);
        if (!isset($result->Merchants)){
            return $result;
        } else {
            $this->merchantListCursor = $result->NextCursor;
            return $result->Merchants;
        }
    }

    // COMMISSION functions
    public function getCommissionSummary()
    {
        $result = $this->request('getCommissionSummary');
        return $result;
    }

    public function getCommissionDetails()
    {
        $result = $this->request('getCommissionDetails');
        return $result;
    }

    public function getAppCommissionsSince($modified_since, $limit = '')
    {
        $result = $this->request('getAppCommissionsSince', [
            'modified_since' => $modified_since,
            'limit' => $limit
        ]);
        return $result;
    }

    // CLICKS functions
    public function getClickStats($start, $end = '')
    {
        $result = $this->request('getClickStats', [
            'by' => 'day', // assume day intervals
            'start' => $start,
            'end' => $end
        ]);
        return $result;
    }

    public function getClickStatsByDay($start, $end = '')
    {
        $result = $this->request('getClickStats', [
            'by' => 'day',
            'start' => $start,
            'end' => $end
        ]);
        return $result;
    }

    public function getClickStatsByMonth($start, $end = '')
    {
        $result = $this->request('getClickStats', [
            'by' => 'month',
            'start' => $start,
            'end' => $end
        ]);
        return $result;
    }

    public function getClickStatsByYear($start, $end = '')
    {
        $result = $this->request('getClickStats', [
            'by' => 'year',
            'start' => $start,
            'end' => $end
        ]);
        return $result;
    }

    // VANITY URL functions
    public function getVanityUrl($url)
    {
        $result = $this->request('getVanityUrl', [
            'post_obj' => (object) ['URL'=>$url]
        ]);
        return $result;
    }

}
