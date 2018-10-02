<?php

namespace WildlinkApi;

class Request
{
    public static function getEndpointInfo($function){
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

    public static function request($function, $vars, $device_token = '')
    {
        $api_url_base = "https://api.wfi.re";

        $api_info = Request::getEndpointInfo($function);
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
            $auth_token = Auth::getAuth($date_time); // if the request is to gen a device token, then we don't pass one... since we don't have one yet
        } else {
            $auth_token = Auth::getAuth($date_time, $device_token);
        }

        $opts = array(
            'http' => array(
                'method'  => strtoupper($api_info->method),
                'ignore_errors' => true,
                'header'  => "Content-Type: application/json\r\n" .
                    "Authorization: " . $auth_token . "\r\n" .
                    "X-WF-DateTime: " . $date_time . "\r\n" .
                    "User-Agent: Demo-Client-PHP\r\n",
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
}
