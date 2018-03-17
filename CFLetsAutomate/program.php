<?php
/**
 * Created by PhpStorm.
 * User: Developer
 * Date: 3/16/2018
 * Time: 8:20 AM
 */

require_once ('vendor/autoload.php');
require_once ('CFLetsAutomate.php');

use stonemax\acme2\Client;
use stonemax\acme2\constants\CommonConstant;

$helpText = "This program allows you to automate DNS entries for use with Let's Encrypt DNS-01.
You must set-up the program with your CloudFlare API key & email.
The program assumes you are trying to make wildcard certificates with Let's Encrypt, anything else hasn't been tested.
Usage: php program.php (order, finalize, renew) (domain)
Example: php program.php order *.example.com\n";

$actions = ['order', 'finalize', 'renew'];

$info = [
    'apiKey' => '', //CloudFlare API key
    'apiEmail' => '', //CloudFlare API email
    'letsEncryptEmail' => [''], //this is used for notifications from let's encrypt
    'storagePath' => '/home/wutno/somefolder/data/' //where the heck is everything going?
];

if($argc != 3 || empty($argv[1]) || empty($argv[2]))
    die($helpText);
else
    $info += [
        'action' => $argv[1],
        'domain' => $argv[2]
    ];

if(in_array($info['action'], $actions)) {
    $cfHandle = new CFLetsAutomate($info);
    $acme2Handle = new Client($info['letsEncryptEmail'], $info['storagePath'], true); //set false for live certs
} else {
    die($helpText);
}

if($info['action'] == 'order' || $info['action'] == 'finalize') {
    $order = $acme2Handle->getOrder([CommonConstant::CHALLENGE_TYPE_DNS => [$info['domain']]], CommonConstant::KEY_PAIR_TYPE_EC);
} else if($info['action'] == 'renew') {
    $order = $acme2Handle->getOrder([CommonConstant::CHALLENGE_TYPE_DNS => [$info['domain']]], CommonConstant::KEY_PAIR_TYPE_EC, true);
    $info['action'] = 'order';
}

$challengeList = $order->getPendingChallengeList();

if($info['action'] == 'order') {
    if ($cfHandle->check())
        $cfHandle->delete();

    foreach ($challengeList as $challenge) {
        $challengeType = $challenge->getType(); // http-01 or dns-01
        $credential = $challenge->getCredential();

        if($challengeType == CommonConstant::CHALLENGE_TYPE_DNS) {
            $cfHandle->install($credential['dnsContent']);
        }
    }
} else if($info['action'] == 'finalize') {
    foreach($challengeList as $challenge){
        print "We're going to attempt to verify.\n";
        $challenge->verify();
        print "Successfully verified with Let's Encrypt!\n";
    }
    $certificateInfo = $order->getCertificateFile();
    vprintf("\nPrivate Key: %-20s\nPublic Key : %-20s\nCertificate: %-20s\nFull Chain : %-20s\n\n", $certificateInfo);
    $cfHandle->delete();
}
