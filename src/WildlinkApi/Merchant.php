<?php

namespace WildlinkApi;

class Merchant
{
    public static function getByIds($device_token, $id_array)
    {
        return Request::request('getMerchant', array("id" => $id_array), $device_token);
    }
}
