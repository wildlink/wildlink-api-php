<?php

namespace WildlinkApi;

class WildlinkClient
{
    public function __construct($app_id, $secret, $device_key = '', $device_token = '', $debug = 0, $dev = 0)
    {
        $this->app_id = $app_id;
        $this->secret = $secret;
        $this->debug = $debug;
        $this->dev = $dev;

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

        if ($function == 'getMerchantCommissionRates'){
            $api_info->endpoint = '/v2/merchant/:id/commission';
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

        if ($function == 'getAppCommissionsByCursor'){
            $api_info->endpoint = '/v2/commission?cursor=:cursor&limit=:limit';
            $api_info->method = 'GET';
        }

        if ($function == 'resendCommissionCallback'){
            $api_info->endpoint = '/v2/commission/:id/send-callback';
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

        // CONCEPTS functions
        if ($function == 'getAvailableConcepts'){
            $api_info->endpoint = '/v2/concept/:kind?addable=1&limit=:limit&cursor=:cursor&sort_by=:sort_by&min_rank=:min_rank';
            $api_info->method = 'GET';
        }

        if ($function == 'getCurrentConcepts'){
            $api_info->endpoint = '/v2/concept/:kind?addable=0&limit=:limit&cursor=:cursor&sort_by=:sort_by&min_rank=:min_rank';
            $api_info->method = 'GET';
        }

        if ($function == 'getConcepts'){
            $api_info->endpoint = '/v2/concept/:kind?limit=:limit&cursor=:cursor&sort_by=:sort_by&min_rank=:min_rank';
            $api_info->method = 'GET';
        }

        if ($function == 'addConceptToList'){
            $api_info->endpoint = '/v2/application_concept/application/:application_id?';
            $api_info->method = 'POST';
        }

        if ($function == 'removeConceptFromList'){
            $api_info->endpoint = '/v2/application_concept/application/:application_id/concept/:concept_id';
            $api_info->method = 'DELETE';
        }

        // NLP functions
        if ($function == 'markupNlp'){
            $api_info->endpoint = '/v1/nlp/analyze';
            $api_info->method = 'POST';
        }

        if ($function == 'markupNlpV2'){
            $api_info->endpoint = '/v2/nlp/analyze';
            $api_info->method = 'POST';
        }

        // COUPONS functions
        if ($function == 'getCouponsByNetworkMerchant'){
            // get all coupons for a NM ID
            $api_info->endpoint = '/v2/network_merchant/:network_merchant_id/coupon';
            $api_info->method = 'GET';
        }

        if ($function == 'updateCoupon'){
            // update a single coupon by coupon ID
            $api_info->endpoint = '/v2/network_merchant_coupon/:coupon_id';
            $api_info->method = 'POST';
        }
        
        return $api_info;
    }

    public function request($function, $vars = null)
    {
        if ($this->dev){
            $api_url_base = "https://dev-api.wfi.re";
        } else {
            $api_url_base = "https://api.wfi.re";
        }

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

        if ($this->debug){
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

        if ($this->debug){
            echo("\n\nauth token : " . print_r($auth_token, 1));
            echo("\n\npost data : " . print_r($opts, 1));
        }

        $context = stream_context_create($opts);
        $result_json = file_get_contents($api_url, false, $context);
        $result = json_decode($result_json);

        if ($this->debug){
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
                    'limit' => 500 // max limit = 500
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
                    'limit' => 500 // max limit = 500
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
            'limit' => 500 // max limit = 500
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
            'limit' => 500 // max limit = 500
        ]);
        if (!isset($result->Merchants)){
            return $result;
        } else {
            $this->merchantListCursor = $result->NextCursor;
            return $result->Merchants;
        }
    }

    public function getMerchantCommissionRates($id)
    {
        $result = $this->request('getMerchantCommissionRates', array("id" => $id));
        return $result;
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
        if (!isset($this->commissionListCursor)){
            $this->commissionListCursor = '';
        }

        $result = $this->request('getAppCommissionsSince', [
            'modified_since' => $modified_since,
            'limit' => $limit
        ]);
        if (!isset($result->Commissions)){
            return $result;
        } else {
            $this->commissionListCursor = $result->NextCursor;
            return $result->Commissions;
        }
    }

    public function resendCommissionCallback($commission_id)
    {
        $result = $this->request('resendCommissionCallback', [
            'id' => $commission_id
        ]);
        return $result;
    }

    public function getPagedCommissions()
    {
        if (!isset($this->commissionListCursor)){
            return "getPagedCommissions cannot be called directly as it needs a cursor reference.  Instead use 'new CommissionList(\$wfClient, \$commissions_since_date)' and cycle through commission records using \$commissionList->getCurrentCommission(), \$commissionList->hasNextCommission() and \$commissionList->getNextCommission()";
        }

        $result = $this->request('getAppCommissionsByCursor', [
            'cursor' => $this->commissionListCursor,
            'limit' => 100 // max limit = 100
        ]);
        if (!isset($result->Commissions)){
            return $result;
        } else {
            $this->commissionListCursor = $result->NextCursor;
            return $result->Commissions;
        }
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

    // KEYWORD functions
    public function getAvailableConcepts($kind = '', $limit = '', $cursor = '', $sort_by = '', $min_rank = '')
    {
        $result = $this->request('getAvailableConcepts', [
            'kind' => $kind,
            'limit' => $limit,
            'cursor' => $cursor,
            'sort_by' => $sort_by,
            'min_rank' => $min_rank,
        ]);
        return $result;
    }

    public function getCurrentConcepts($kind = '', $limit = '', $cursor = '', $sort_by = '', $min_rank = '')
    {
        $result = $this->request('getCurrentConcepts', [
            'kind' => $kind,
            'limit' => $limit,
            'cursor' => $cursor,
            'sort_by' => $sort_by,
            'min_rank' => $min_rank,
        ]);
        return $result;
    }

    public function getConcepts($kind = '', $limit = '', $cursor = '', $sort_by = '', $min_rank = '')
    {
        $result = $this->request('getConcepts', [
            'kind' => $kind,
            'limit' => $limit,
            'cursor' => $cursor,
            'sort_by' => $sort_by,
            'min_rank' => $min_rank,
        ]);
        return $result;
    }

    // depends on whether the application is set to use white-list or black-list but this function will add it to whatever list the application is configured to use
    public function addConceptToList($application_id = '', $concept_id = '')
    {
        $result = $this->request('addConceptToList', [
            'application_id' => $application_id,
            'post_obj' => (object) ['ApplicationID' => $application_id, 'ConceptID' => $concept_id]
        ]);
        return $result;
    }

    public function removeConceptFromList($application_id = '', $concept_id = '')
    {
        $result = $this->request('removeConceptFromList', [
            'application_id' => $application_id,
            'concept_id' => $concept_id,
        ]);
        return $result;
    }
    
    // NLP functions
    public function markupNlp($text)
    {
        $result = $this->request('markupNlp', [
            'text' => $text
        ]);
        return $result;
    }
    
    public function markupNlpV2($text)
    {
        $result = $this->request('markupNlpV2', [
            'post_obj' => (object) [
                'Type' => 'text',
                'Language' => 'en-US',
                'Content' => $text,
                ]
        ]);
        return $result;
    }
    
    public function makeInsertionContext($text_without_links, $text_with_links)
    {
        $result = $this->request('makeInsertionContext', [
            'post_obj' => (object) [
                'context' => $text_without_links,
                'context_with_link' => $text_with_links,
                'sent_time' => date('Y-m-d\TH:i:s.v', time()) . 'Z',
                'destination_app' => 'fireball.app',
                ]
        ]);
        return $result;
    }

    // NLP functions
    public function getCouponsByNetworkMerchant($network_merchant_id)
    {
        $result = $this->request('getCouponsByNetworkMerchant', [
            'network_merchant_id' => $network_merchant_id
        ]);
        return $result;
    }
    
    public function updateCoupon($id, $xid, $network_merchant_id, $name, $description, $code, $url, $disabled, $start_date, $end_date, $exclusions)
    {
        $result = $this->request('updateCoupon', [
            'post_obj' => (object) [
                'ID' => (int) $id,
                'XID' => $xid,
                'NetworkMerchantID' => (int) $network_merchant_id,
                'Name' => $name,
                'Description' => $description,
                'Code' => $code,
                'Url' => $url,
                'Disabled' => (boolean) $disabled,
                'StartDate' => $start_date,
                'EndDate' => $end_date,
                'Exclusions' => $exclusions,
                ]
        ]);
        return $result;
    }    
}
