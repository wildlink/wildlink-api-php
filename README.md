wildlink-api-php
==================

Wildlink API PHP client example.  See also:

* [Getting Started](https://blog.wildlink.me/developers/getting-started-php-library/)
* [SDK Reference](https://blog.wildlink.me/developers/wildlink-php-library-reference/)


Usage
------------------

## Instantiation
```
$wfClient = new WildlinkClient($app_id, $secret);
echo $wfClient->uuid;
echo $wfClient->device_token;
```

## Get Specific Merchant
```
$merchants = $wfClient->getMerchantsById(5477615);
var_dump($merchants);
```

## Get All Merchants (excludes disabled merchants)
```
$merchantList = new MerchantList($wfClient);
while ($merchant = $merchantList->getCurrentMerchant()){
    var_dump($merchant);
    if ($merchantList->hasNextMerchant()){
        $merchantList->getNextMerchant();
    } else {
        break;
    }
}
```

## Get Commissions Summary
```
$commissionSummary = $wfClient->getCommissionSummary();
var_dump($commissionSummary);
```

## Get Commissions Detail List
```
$commissions = $wfClient->getCommissionDetails();
var_dump($commissions);
```

## Get Click Stats
```
$clicks = $wfClient->getClickStatsByDay('2018-01-01');
var_dump($clicks);
```

## Create Vanity URL (i.e. http://wild.link/walmart/abc123)
```
$vanityUrl = $wfClient->getVanityUrl('https://www.walmart.com/ip/VIZIO-24-Class-HD-720P-LED-TV-D24hn-G9/782959488');
var_dump($vanityUrl);
```
