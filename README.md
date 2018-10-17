wildlink-api-php
==================

Wildlink API PHP client example.  See also:

* [Getting Started](https://blog.wildlink.me/developers/getting-started-php-library/)
* [SDK Reference](https://blog.wildlink.me/developers/wildlink-php-library-reference/)


Usage
------------------

```
$wf = new WildlinkClient($app_id, $secret);
echo $wf->uuid;
echo $wf->device_token;
```

- merchants
```
$merchants = $wf->getMerchantsById(5477615);
var_dump($merchants);
```

- commissionDetails
```
$commissions = $wf->getCommissionDetails();
var_dump($commissions);
```
