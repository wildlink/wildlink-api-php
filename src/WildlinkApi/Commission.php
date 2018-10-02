<?php

namespace WildlinkApi;

class Commission
{
    public static function getCommissionDetails($device_token, $uuid)
    {
        return Request::request('getCommissionDetails', array("uuid" => $uuid), $device_token);
    }
}
