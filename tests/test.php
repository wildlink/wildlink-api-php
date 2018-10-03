<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use WildlinkApi\WildlinkClient;

echo "testing auth token generation\n";

// OPTIONAL : instantiate a Wildlink Client with an explicit UUID
#$uuid = 32973973;
#$wf = new WildlinkClient($app_id, $secret, $uuid);

// instantiate a Wildlink Client and let it generate a v4 UUID
$app_id = @$argv[1]; // get app_id from cli
$secret = @$argv[2]; // get secret from cli

$wf = new WildlinkClient($app_id, $secret);

echo "\n\nUUID\n";
echo $wf->uuid;
echo "\n\ndevice token\n";
echo $wf->device_token;

echo "\n-------\n";

echo "testing getting single merchant by ID (5477615)\n";
$wf->getMerchants(5477615) . "\n";
print_r($wf->merchants);

echo "\n-------\n";

echo "testing getting multiple merchants by ID (5482877,5478747)\n";
$wf->getMerchants(array(5482877,5478747)) . "\n";
print_r($wf->merchants);

echo "\n-------\n";

echo "testing getting commission details\n";
$wf->getCommissionDetails() . "\n";
print_r($wf->commissions);

echo "\n-------\n";
