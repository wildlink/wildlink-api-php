wildlink-api-php
==================

Wildlink API PHP client example


Using
------------------

```
$wf = new WildlinkClient($app_id, $secret);
echo $wf->uuid;
echo $wf->device_token;
```

- merchants
```
$wf->getMerchants(5477615);
var_dump($wf->merchants);
```

- commissionDetails
```
$wf->getCommissionDetails();
var_dump($wf->commissions);
```