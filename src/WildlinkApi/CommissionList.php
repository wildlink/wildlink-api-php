<?php

namespace WildlinkApi;

use WildlinkApi\WildlinkClient;

class CommissionList {
    private $commissions = [];
    private $commissionCount = 0;
    protected $currentCommissionIndex = 0;
    protected $wildlinkClient;

    public function __construct(WildlinkClient $wildlinkClient, $modified_start_date){
        $this->wildlinkClient = $wildlinkClient;
        $this->commissions = $wildlinkClient->getAppCommissionsSince($modified_start_date);
        $this->commissionCount = count($this->commissions);
    }

    public function getCommissionCount(){
        return $this->commissionCount;
    }

    public function getCurrentCommission(){
        return $this->commissions[$this->currentCommissionIndex];
    }

    public function getNextCommission(){
        if ($this->hasNextCommission()){
            // if we have the commissions in memory, just increase the index and return the record
            $this->currentCommissionIndex++;
            return $this->getCurrentCommission();
        } else {
            return false;
        }
    }

    public function hasNextCommission(){
        if ($this->currentCommissionIndex < $this->commissionCount - 1){
            return true;
        } else {
            // we don't have another record in memory, so let's fetch another page from the server
            $this->commissions = $this->wildlinkClient->getPagedCommissions();
            $this->commissionCount = count($this->commissions);

            // check to see if we got an empty page (the last page)
            if ($this->commissionCount == 0){
                return false;
            } else {
                $this->currentCommissionIndex = -1; // -1 since we know getNextCommission is about to be called and we don't want to skip the 0th record from the newly returned page of commissions
                return true;
            }
        }
    }
}
