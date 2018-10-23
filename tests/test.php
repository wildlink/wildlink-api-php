<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use WildlinkApi\WildlinkClient;
use WildlinkApi\MerchantList;

function out($str, $type = ''){
    $color_array['error'] = "\033[31m";
    $color_array['success'] = "\033[32m";
    echo $color_array[$type];

    if (!is_string($str)){
        print_r($str);
    } else {
        echo $str;
    }

    echo "\033[0m"; // reset any color that may be applied
    echo "\n-------\n";
}

out("starting tests...", 'success');

out("testing auth token generation");

// instantiate a Wildlink Client
$appID = @$argv[1]; // get app_id from cli
$secret = @$argv[2]; // get secret from cli
$uuid = @$argv[3]; // optional UUID from cli to start new session with previously created device
$deviceToken = @$argv[4]; // optional deviceToken from cli to start new session with previously created deviceToken

if (!$appID || !$secret){
    out("either app_id or secret not passed in.  Try invoking via 'php path/to/test.php [APP_ID] '[APP_SECRET]'", 'error'); exit;
}

out("connecting to Wildlink with app id $appID and secret $secret");

if ($uuid && $deviceToken){
    $wfClient = new WildlinkClient($appID, $secret, $uuid, $deviceToken);
} elseif ($uuid){
    // OPTIONAL : instantiate a Wildlink Client with a UUID to create a new session with an existing  device
    $wfClient = new WildlinkClient($appID, $secret, $uuid);
} else {
    $wfClient = new WildlinkClient($appID, $secret);
}

// Note: Wildlink web service will create and return a UUID unless one is passed in
out("UUID: " . $wfClient->uuid);

if ($wfClient->device_token){
    out("device token: " . $wfClient->device_token);
} else {
    out("FAIL. No device token generated, exiting", 'error'); exit;
}

out("testing getting single merchant by ID (5477615)");
$singleMerchant = $wfClient->getMerchantsById(5477615);
out($singleMerchant);

out("testing getting multiple merchants by ID (5482877,5478747)");
$multipleMerchants = $wfClient->getMerchantsById(array(5482877,5478747));
out($multipleMerchants);

out("stepping through merchants");
$merchantList = new MerchantList($wfClient);

// method 1
$merchantCounter = 0;
while ($merchant = $merchantList->getCurrentMerchant()){
    out($merchantCounter);
    out($merchant);
    $merchantCounter++;
    if ($merchantList->hasNextMerchant()){
        $merchantList->getNextMerchant();
    } else {
        break;
    }
}

// method 2
/*
$merchantCounter = 0;
$merchant = $merchantList->getCurrentMerchant();
out($merchant);
$merchantCounter++;
while ($merchantList->hasNextMerchant()){
    $merchant = $merchantList->getNextMerchant();
    out($merchant);
    $merchantCounter++;
}
*/

out("total merchant count: " . $merchantCounter);
if ($merchantCounter > 1000){
    out("PASS", 'success');
} else {
    out("FAIL", 'error');
}

out("testing getting commission details");
$commissionDetails = $wfClient->getCommissionDetails();
out($commissionDetails);

out("testing getting commission summary");
$commissionSummary = $wfClient->getCommissionSummary();
out($commissionSummary);

out("testing getting commission details");
$clicks = $wfClient->getClickStatsByDay('2018-01-01');
out($clicks);

out("generate a wild.link vanity URL");
$vanityUrl = $wfClient->getVanityUrl('https://www.walmart.com/ip/VIZIO-24-Class-HD-720P-LED-TV-D24hn-G9/782959488');
out($vanityUrl);

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
out($clicksAfter);

out("\nYou can re-run with UUID as\nphp tests/test.php $appID '$secret' '$wfClient->uuid'\n", 'success');

out("\nYou can re-run with UUID and device token as\nphp tests/test.php $appID '$secret' '$wfClient->uuid' '$wfClient->device_token'\n", 'success');

out("complete.", 'success'); exit;
