<?php

/*
 * NOTES:
 *
 * 1: We're checking if DNS has the required keys locally with LEFunctions::checkDNSChallenge()
 *    it should never pass unless it's true, but when we run LEOrder::verifyPendingOrderAuthorization()
 *    it calls LEFunctions::checkDNSChallenge() and fails, at what i've found to be random so we wait..
 *    i've found that 3.5 minutes to be about long enough for it to catch it every time and not make
 *    Let's Encrypts API start spitting out 500 responses when we go to verify.
 *
 * 2: LEOrder::verifyPendingOrderAuthorization() expects to have all DNS entries active and does some.. odd
 *    things when they aren't so we do another for loop after we've verified with LEFunctions::checkDNSChallenge()
 */

require_once('vendor/autoload.php');
require_once('LEClient/LEClient/LEClient.php');
require_once('CFLetsAutomate.php');

$config = [
    'apiKey'    => '', //CloudFlare API key
    'apiEmail'  => '', //CloudFlare API email
    'LEEmail'   => ['webmaster@example.com'], //this is used for notifications from let's encrypt
    'basename'  => 'example.com',
    'domains'   => ['example.com', '*.example.com'],
    'keyType'   => 'ec-384', //rsa-4096
    'sleepTime' => 210 //NOTE 1
];

$cfHandler = new CFLetsAutomate($config);
$leHandler = new LEClient($config['LEEmail'], LEClient::LE_PRODUCTION, LECLient::LOG_STATUS);

$order = $leHandler->getOrCreateOrder($config['basename'], $config['domains'], 'ec-384');

if(!$order->allAuthorizationsValid()) {
    $pending = $order->getPendingAuthorizations(LEOrder::CHALLENGE_TYPE_DNS);
    if (!empty($pending)) {
        foreach ($pending as $challenge) {
            if(!$cfHandler->remoteCheck($challenge['identifier'], $challenge['DNSDigest']))
                $cfHandler->install($challenge['identifier'], $challenge['DNSDigest']);

            print "Checking for _acme-challenge.{$challenge['identifier']} with \"{$challenge['DNSDigest']}\".\n";
            while(!LEFunctions::checkDNSChallenge($challenge['identifier'], $challenge['DNSDigest'])){ //NOTE 1
                print "Not found, waiting 10 seconds and trying again.\n";
                sleep(10);
            }
            print "Found!\n";
        }
        print "Waiting " . ($config['sleepTime']/60) . " minutes before we attempt to verify with Let's Encrypt.\n";
        sleep($config['sleepTime']);
        foreach($pending as $challenge){ //NOTE 2
            $order->verifyPendingOrderAuthorization($challenge['identifier'], LEOrder::CHALLENGE_TYPE_DNS);
        }
    }
}

if($order->allAuthorizationsValid()) {
    if(!$order->isFinalized()) { $order->finalizeOrder(); }
    if($order->isFinalized()) { $order->getCertificate(); }
    $cfHandler->check();
    if(!empty($cfHandler->txtRecords)) { $cfHandler->delete(); }
}
