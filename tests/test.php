<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use WildlinkApi\WildlinkClient;

function out($str, $type = ''){
    if ($type == 'error'){
        echo "\033[31m";
    }
    if ($type == 'success'){
        echo "\033[32m";
    }
    echo $str;
    echo "\033[0m"; // reset any color that may be applied
    echo "\n-------\n";
}

out("testing auth token generation");

// instantiate a Wildlink Client
$appID = @$argv[1]; // get app_id from cli
$secret = @$argv[2]; // get secret from cli
$uuid = @$argv[3]; // optional UUID from cli to start new session with previously created device

if (!$appID || !$secret){
    out("either app_id or secret not passed in.  Try invoking via 'php path/to/test.php [APP_ID] '[APP_SECRET]'", 'error'); exit;
}

out("connecting to Wildlink with app id $appID and secret $secret");

// OPTIONAL : instantiate a Wildlink Client with a UUID to create a new session with an existing  device
if ($uuid){
    $wfClient = new WildlinkClient($appID, $secret, $uuid);
} else {
    $wfClient = new WildlinkClient($appID, $secret);
}

// Note: Wildlink web service will create and return a UUID unless one is passed in
out("UUID: " . $wfClient->uuid);

if ($wfClient->device_token){
  out("device token: " . $wfClient->device_token);
} else {
  out("no device token generated, exiting", 'error'); exit;
}

out("testing getting single merchant by ID (5477615)");
$singleMerchant = $wfClient->getMerchantsById(5477615);
print_r($singleMerchant);

out("testing getting multiple merchants by ID (5482877,5478747)");
$multipleMerchants = $wfClient->getMerchantsById(array(5482877,5478747));
print_r($multipleMerchants);

/* TODO: coming soon...
out("testing refreshing all enabled merchants");
$allMerchants = $wfClient->getAllEnabledMerchants();
out("total merchant count: " . count($allMerchants));
*/

out("testing getting commission details");
$commissionDetails = $wfClient->getCommissionDetails();
print_r($commissionDetails);

out("testing getting commission summary");
$commissionSummary = $wfClient->getCommissionSummary();
print_r($commissionSummary);

out("testing getting commission details");
$clicks = $wfClient->getClickStatsByDay('2018-01-01');
print_r($clicks);

out("generate a wild.link vanity URL");
$vanityUrl = $wfClient->getVanityUrl('https://www.walmart.com/ip/VIZIO-24-Class-HD-720P-LED-TV-D24hn-G9/782959488');
print_r($vanityUrl);

out("follow the wild.link we just created to increment the click stats");
if ($vanityUrl->VanityURL){
    out("hitting " . $vanityUrl->VanityURL);

    // user agent required since we ignore clicks that appear to come from bots
    $options = array(
        'http'=>array(
            'method'=>"GET",
            'header'=>"Accept-language: en\r\n" .
                "Cookie: foo=bar\r\n" .
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36\r\n"
        )
    );

    $context = stream_context_create($options);
    $file = file_get_contents($vanityUrl->VanityURL, false, $context);
} else {
    out("no wild.link generated, exiting.", 'error'); exit;
}

out("wait a couple seconds for stats to process (should be instant but just in case...)");
sleep(2);
out("re-request click stats");
$clicksAfter = $wfClient->getClickStatsByDay('2018-01-01');
print_r($clicksAfter);

out("complete.", 'success'); exit;
