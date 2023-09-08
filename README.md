# wildlink-api-php

PHP client for working with Wildfire/Wildlink APIs. Convert product and brand links into affiliate versions to generate revenue.  Learn more at https://www.wildlink.me/

See also:
* [Getting Started](https://blog.wildlink.me/developers/getting-started-php-library/)

## Installation
Installation through Composer is recommended.  See the Getting Started guide above if you're new to installing libraries via Composer.

```composer require wildlink/wildlink-api-php```


## Usage

### Instantiation

```php
// minimum auth, NOTE: this creates a new device
$wfClient = new WildlinkClient($app_id, $secret);

// create a new "session" for a previously created device (fetches a new deviceToken from Wildfire server)
$wfClient = new WildlinkClient($app_id, $secret, $device_key);

// prepare to make a new request with a previously stored device key and device auth token (no Wildfire server hit)
$wfClient = new WildlinkClient($app_id, $secret, $device_key, $device_token);

echo $wfClient->device_key; // Device Key is used for authing the device in the future - it doesn't expire
echo $wfClient->device_id; // Device ID is used for referencing device in reporting data
echo $wfClient->device_token; // Device Token is used for authing device - it expires
```


### Get App Commissions Detail List (Across all devices)
When you want to fetch all commissions, across all devices (i.e. for syncing your backend with Wildfire's) you can use this call to get all changes to commission records since a given date.

```php
$allCommissionsSince = $wfClient->getAppCommissionsSince('2018-07-01');
var_dump($allCommissionsSince);
```

#### Example return
```
Array
(
    [0] => stdClass Object
        (
            [ID] => 4015
            [ApplicationID] => 3
            [MerchantID] => 5478747
            [DeviceKey] => D9F3D3DF-E2C5-43A0-9BA5-5F47EEB964EF
            [SaleAmount] => 319.3
            [Amount] => 0
            [Status] => DISQUALIFIED
            [EventDate] => 2018-02-17T09:07:46Z
            [CreatedDate] => 2018-04-05T20:35:30.710591Z
            [ModifiedDate] => 2018-07-03T23:05:19.385467Z
        )

    [1] => stdClass Object
        (
            [ID] => 4013
            [ApplicationID] => 3
            [MerchantID] => 5478049
            [DeviceKey] => 95276881-d08f-44b3-b871-641485c719a9
            [SaleAmount] => 118.93
            [Amount] => 5.9465
            [Status] => PAID
            [EventDate] => 2018-02-10T01:24:40Z
            [CreatedDate] => 2018-04-05T20:35:29.265713Z
            [ModifiedDate] => 2018-07-03T23:05:19.385467Z
        )

    [2] => stdClass Object
        (
            [ID] => 4010
            [ApplicationID] => 3
            [MerchantID] => 5477615
            [DeviceKey] => 17489795-4a7f-4773-b8d0-5c90d86ce88b
            [SaleAmount] => 6309.95
            [Amount] => 0
            [Status] => DISQUALIFIED
            [EventDate] => 2017-12-21T09:00:00Z
            [CreatedDate] => 2018-04-05T20:35:25.994872Z
            [ModifiedDate] => 2018-07-03T23:05:19.385467Z
        )

)

```

The possible values for Status are:

1. **PENDING** – This is the default state for a commission record.  Wildlink has detected the commission but has not been paid yet.  This mostly reflects a return period policy (where a merchant won’t pay a commission until after a return period has passed.
1. **DISQUALIFIED** – For specific merchants, some purchases are marked disqualified based on their terms.  There are many cases and they vary widely by merchant.  Examples of this include Ticketmaster event pre-sales and Apple electronics at most retailers.
1. **READY** – Payment has been received by Wildlink from the merchant is available to be paid to the end user or partner company in the next payment cycle.  At the time of this writing, payments are made at the beginning of each month.
1. **PAID** – Payment has been made to either the individual user or partner company (in the case where the partner is handling payments between Wildlink and the end user).


#### Alternative Method for Paging Through Commissions
Alternatively to getting back a single array of commissions, you can instead use the CommissionList object to cycle through each record.  This has the advantage of having a smaller memory footprint as only a single "page" of results are loaded into memory at any given time.  The returned data is the same as the getAppCommissionsSince method call and it takes the same *since_modified_date* parameter.

```php
use WildlinkApi\CommissionList;

$commissionList = new CommissionList($wfClient, '2018-12-01');

while ($commission = $commissionList->getCurrentCommission()){
    var_dump($commission);
    if ($commissionList->hasNextCommission()){
        $commissionList->getNextCommission();
    } else {
        break;
    }
}
```


### Create Vanity URL (i.e. http://wild.link/walmart/abc123)
Convert a URL (to a product page, listing page, etc.) to a wild.link URL with embedded tracking for the authenticated device.


```php
$vanityUrl = $wfClient->getVanityUrl('https://www.walmart.com/ip/VIZIO-24-Class-HD-720P-LED-TV-D24hn-G9/782959488');
var_dump($vanityUrl);
```

#### Example return
```
stdClass Object
(
    [OriginalURL] => https://www.walmart.com/ip/VIZIO-24-Class-HD-720P-LED-TV-D24hn-G9/782959488
    [VanityURL] => http://wild.link/walmart/AMjFBg
)
```
