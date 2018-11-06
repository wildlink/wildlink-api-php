# wildlink-api-php

PHP client for working with Wildfire/Wildlink APIs. Convert product and brand links into affiliate versions to generate revenue.  Learn more at https://www.wildlink.me/

See also:
* [Getting Started](https://blog.wildlink.me/developers/getting-started-php-library/)


## Instantiation
```php
// minimum auth, NOTE: this creates a new device
$wfClient = new WildlinkClient($app_id, $secret);

// create a new "session" for a previously created device (fetches a new deviceToken from Wildfire server)
$wfClient = new WildlinkClient($app_id, $secret, $uuid);

// prepare to make a new request with a previously stored device ID and auth token (no Wildfire server hit)
$wfClient = new WildlinkClient($app_id, $secret, $uuid, $deviceToken);

echo $wfClient->uuid;
echo $wfClient->device_token;
```

## Get Specific Merchant(s) Metadata
The getMerchants call can be used to fetch metadata (including images) for a given merchant.  This call can take either a single integer or an array of integers.

```php
// single merchant request
$singleMerchant = $wfClient->getMerchantsById(5477615);
var_dump($singleMerchant);

// multiple merchant request
$multipleMerchants = $wfClient->getMerchantsById(array(5482877,5478747));
var_dump($multipleMerchants);
```

### Example return
```
Array
(
    [0] => stdClass Object
        (
            [ID] => 5478747
            [Name] => Hotels.com
            [ShortCode] => mPEE27LOAhI
            [ShortURL] => http://wild.link/mPEE27LOAhI
            [Images] => Array
                (
                    [0] => stdClass Object
                        (
                            [ID] => 3
                            [Kind] => FEATURED
                            [Ordinal] => 1
                            [ImageID] => 3
                            [URL] => https://images.wildlink.me/wl-image/a3a4df7b12404451fe3f2099122847e5dc3ed6b5.jpeg
                            [Width] => 660
                            [Height] => 380
                        )
                    [1] => stdClass Object
                        (
                            [ID] => 808
                            [Kind] => LOGO
                            [Ordinal] => 1
                            [ImageID] => 809
                            [URL] => https://images.wildlink.me/wl-image/2fe289045e0794f3bcf7f3ae03d7850f5b51cf6c.jpeg
                            [Width] => 200
                            [Height] => 200
                        )
                )
        )
    [1] => stdClass Object
        (
            [ID] => 5482877
            [Name] => Target
            [ShortCode] => mPEE_dLOAhE
            [ShortURL] => http://wild.link/mPEE_dLOAhE
            [Images] => Array
                (
                    [0] => stdClass Object
                        (
                            [ID] => 29
                            [Kind] => FEATURED
                            [Ordinal] => 1
                            [ImageID] => 29
                            [URL] => https://images.wildlink.me/wl-image/480b5c95d607854052a9f583c51ee1b8f168e640.jpeg
                            [Width] => 660
                            [Height] => 380
                        )
                    [1] => stdClass Object
                        (
                            [ID] => 713
                            [Kind] => LOGO
                            [Ordinal] => 10
                            [ImageID] => 714
                            [URL] => https://images.wildlink.me/wl-image/15b28fdfc04006589492a620fca2a4290547b394.jpeg
                            [Width] => 200
                            [Height] => 200
                        )
                )
        )
)
```

## Get All Merchants (excludes disabled merchants) metadata

```php
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

### Example return
```
stdClass Object
(
    [ID] => 5520185
    [Name] => Prioritytireoutlet.com
    [Disabled] =>
    [Featured] =>
    [ShortCode] => 95gFufbQAgQ
    [ShortURL] => http://wild.link/95gFufbQAgQ
    [Images] => Array
        (
            [0] => stdClass Object
                (
                    [ID] => 3
                    [Kind] => FEATURED
                    [Ordinal] => 1
                    [ImageID] => 3
                    [URL] => https://images.wildlink.me/wl-image/a3a4df7b12404451fe3f2099122847e5dc3ed6b5.jpeg
                    [Width] => 660
                    [Height] => 380
                )
            [1] => stdClass Object
                (
                    [ID] => 808
                    [Kind] => LOGO
                    [Ordinal] => 1
                    [ImageID] => 809
                    [URL] => https://images.wildlink.me/wl-image/2fe289045e0794f3bcf7f3ae03d7850f5b51cf6c.jpeg
                    [Width] => 200
                    [Height] => 200
                )
        )
)
```

## Get App Commissions Detail List (Across all devices)
When you want to fetch all commissions, across all devices (i.e. for syncing your backend with Wildfire's) you can use this call to get all changes to commission records since a given time.

```php
$allCommissionsSince = $wfClient->getAppCommissionsSince('2018-07-01');
var_dump($allCommissionsSince);
```

### Example return
```
stdClass Object
(
    [Commissions] => Array
        (
            [0] => stdClass Object
                (
                    [ID] => 4015
                    [ApplicationID] => 3
                    [MerchantID] => 5478747
                    [DeviceUUID] => D9F3D3DF-E2C5-43A0-9BA5-5F47EEB964EF
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
                    [DeviceUUID] => 95276881-d08f-44b3-b871-641485c719a9
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
                    [DeviceUUID] => 17489795-4a7f-4773-b8d0-5c90d86ce88b
                    [SaleAmount] => 6309.95
                    [Amount] => 0
                    [Status] => DISQUALIFIED
                    [EventDate] => 2017-12-21T09:00:00Z
                    [CreatedDate] => 2018-04-05T20:35:25.994872Z
                    [ModifiedDate] => 2018-07-03T23:05:19.385467Z
                )

        )

    [PrevCursor] =>
    [NextCursor] =>
)
```

The possible values for Status are:

1. **PENDING** – This is the default state for a commission record.  Wildlink has detected the commission but has not been paid yet.  This mostly reflects a return period policy (where a merchant won’t pay a commission until after a return period has passed.
1. **DISQUALIFIED** – For specific merchants, some purchases are marked disqualified based on their terms.  There are many cases and they vary widely by merchant.  Examples of this include Ticketmaster event pre-sales and Apple electronics at most retailers.
1. **READY** – Payment has been received by Wildlink from the merchant is available to be paid to the end user or partner company in the next payment cycle.  At the time of this writing, payments are made at the beginning of each month.
1. **PAID** – Payment has been made to either the individual user or partner company (in the case where the partner is handling payments between Wildlink and the end user).


## Get Commissions Summary
When you want to display a high level summary of the earnings to a user (assuming you are passing compensation along to your end users) you can call getCommissionSummary.

```php
$commissionSummary = $wfClient->getCommissionSummary();
var_dump($commissionSummary);
```

### Example return
```
stdClass Object
(
    [PendingAmount] => 0.00
    [ReadyAmount] => 0.00
    [PaidAmount] => 0.00
)
```

## Get Commissions Detail List
If you’re including your end users in Wildlink compensation, you can use this call to fetch a detailed record of every commission that Wildlink is aware of for the client/device (including each commission’s status).


```php
$commissions = $wfClient->getCommissionDetails();
var_dump($commissions);
```

### Example return
```
Array (
    [0] => stdClass Object
        (
        "ID": 7455,
        "CommissionIDs": Array(
            7455,
            7456
        ],
        "Date": "2018-09-18T00:00:00Z",
        "Amount": "0.12",
        "Status": "PENDING",
        "Merchant": "AliExpress"
    ),
    [1] => stdClass Object
        (
        "ID": 7336,
        "CommissionIDs": Array(
            7336
        ),
        "Date": "2018-09-05T14:24:28Z",
        "Amount": "0.04",
        "Status": "DISQUALIFIED",
        "Merchant": "DressLily"
    )
)
```

## Get Click Stats
If appropriate, you can display total clicks for a given device for a time range (by day, month or year).  This method takes a start and end date value (the end value is optional).


```php
$clicks = $wfClient->getClickStatsByDay('2018-01-01');
var_dump($clicks);
```

### Example return
```
stdClass Object
(
   [2018-10-15T00:00:00Z] => 3
   [2018-10-16T00:00:00Z] => 12
)
```


## Create Vanity URL (i.e. http://wild.link/walmart/abc123)
Convert a URL (to a product page, listing page, etc.) to a wild.link URL with embedded tracking for the authenticated device.


```php
$vanityUrl = $wfClient->getVanityUrl('https://www.walmart.com/ip/VIZIO-24-Class-HD-720P-LED-TV-D24hn-G9/782959488');
var_dump($vanityUrl);
```

### Example return
```
stdClass Object
(
    [OriginalURL] => https://www.walmart.com/ip/VIZIO-24-Class-HD-720P-LED-TV-D24hn-G9/782959488
    [VanityURL] => http://wild.link/walmart/AMjFBg
)
```
