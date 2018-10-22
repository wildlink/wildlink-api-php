<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use WildlinkApi\WildlinkClient;

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

out("testing refreshing all enabled merchants");
$allMerchants = $wfClient->getAllEnabledMerchants();
#out($allMerchants);

out("total merchant count: " . count($allMerchants));
if (count($allMerchants) > 1000){
    out("PASS", 'success');
} else {
    out("FAIL", 'error');
}

out("testing getting first two pages of enabled merchants");

out("page 1 (first record):");
$pageOfMerchants1 = $wfClient->getPagedEnabledMerchants();
out($pageOfMerchants1[0]);
#out($pageOfMerchants1);

out("page 2 (first record):");
$pageOfMerchants2 = $wfClient->getPagedEnabledMerchants();
out($pageOfMerchants2[0]);
#out($pageOfMerchants1);


if (!$pageOfMerchants1[0]->Name){
    out("FAIL: no Name value for first record of first page of merchants", 'error');
} elseif (!$pageOfMerchants2[0]->Name){
    out("FAIL: no Name value for first record of second page of merchants", 'error');
} elseif ($pageOfMerchants1[0]->Name == $pageOfMerchants2[0]->Name){
    out("FAIL: first record from first and second page of merchants have the same name", 'error');
} else {
    out("PASS", 'success');
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
