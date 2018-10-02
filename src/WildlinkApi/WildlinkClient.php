<?php

namespace WildlinkApi;

class WildlinkClient
{
    public $uuid;
    public $device_token;
    public $merchants;
    public $commissions;

    private function getUuid()
    {
        if ($this->uuid){
            return $this->uuid;
        } else {
            // look to see if we have a cached value for the uuid
            $cached_uuid_file = dirname(__FILE__) . "/data/uuid";
            if (file_exists($cached_uuid_file)){
                $cached_uuid = file_get_contents($cached_uuid_file);
            }
            if ($cached_uuid){
                $this->uuid = $cached_uuid;
            } else {
                // no cached uuid, so let's generate a new one
                $this->uuid = Uuid::makeUuid();

                // store it in cache
                file_put_contents($cached_uuid_file, $this->uuid);
            }

            return $this->uuid;
        }
    }

    public function __construct($uuid = '')
    {
        if ($uuid){
            $this->uuid = (string) $uuid;
        } else {
            $uuid = $this->getUuid();
        }
        $this->device_token = Auth::makeDeviceToken($this->uuid);
#        echo "device token is " . $this->device_token;
    }

    public function getMerchants($ids){
        $this->merchants = Merchant::getByIds($this->device_token, $ids);
    }

    public function getCommissionDetails(){
        $this->commissions = Commission::getCommissionDetails($this->device_token, $this->uuid);
    }

}
