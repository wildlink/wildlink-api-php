<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use WildlinkApi\WildlinkClient;

echo "testing auth token generation\n";

// OPTIONAL : instantiate a Wildlink Client with an explicit UUID
#$uuid = 32973973;
#$wf = new WildlinkClient($app_id, $secret, $uuid);

// instantiate a Wildlink Client and let it generate a v4 UUID
$app_id = 123456; // <-- replace with your app_id
$secret = 'foo'; // <-- replace with your secret
$wf = new WildlinkClient($app_id, $secret);

echo "\n\nUUID\n";
echo $wf->uuid;
echo "\n\ndevice token\n";
echo $wf->device_token;

echo "\n-------\n";

echo "testing getting merchants by ID\n";
$wf->getMerchants(array(5477615,5482877)) . "\n";
print_r($wf->merchants);

echo "\n-------\n";

echo "testing getting commission details\n";
$wf->getCommissionDetails() . "\n";
print_r($wf->commissions);

echo "\n-------\n";
