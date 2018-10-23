<?php

namespace WildlinkApi;

use WildlinkApi\WildlinkClient;

class MerchantList {
    private $merchants = [];
    private $merchantCount = 0;
    protected $currentMerchantIndex = 0;
    protected $wildlinkClient;

    public function __construct(WildlinkClient $wildlinkClient){
        $this->wildlinkClient = $wildlinkClient;
        $this->merchants = $wildlinkClient->getPagedEnabledMerchants();
        $this->merchantCount = count($this->merchants);
    }

    public function getMerchantCount(){
        return $this->merchantCount;
    }

    public function getCurrentMerchant(){
        return $this->merchants[$this->currentMerchantIndex];
    }

    public function getNextMerchant(){
        if ($this->hasNextMerchant()){
            // if we have the merchants in memory, just increase the index and return the record
            $this->currentMerchantIndex++;
            return $this->getCurrentMerchant();
        } else {
            return false;
        }
    }

    public function hasNextMerchant(){
        if ($this->currentMerchantIndex < $this->merchantCount - 1){
            return true;
        } else {
            // we don't have another record in memory, so let's fetch another page from the server
            $this->merchants = $this->wildlinkClient->getPagedEnabledMerchants();
            $this->merchantCount = count($this->merchants);

            // check to see if we got an empty page (the last page)
            if ($this->merchantCount == 0){
                return false;
            } else {
                $this->currentMerchantIndex = -1; // -1 since we know getNextMerchant is about to be called and we don't want to skip the 0th record from the newly returned page of merchants
                return true;
            }
        }
    }
}
