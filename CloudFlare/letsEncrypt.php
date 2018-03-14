<?php
/*  CloudFlare LetsEncrypt
 *  ------------------------------------------
 *  Author: wutno (#/g/tv - Rizon)
 *
 *  GNU License Agreement
 *  ---------------------
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 *  http://www.gnu.org/licenses/gpl-2.0.txt
 */
ini_set('max_execution_time', 120);
require_once 'Cloud.php';
require_once 'PATH TO LEClient.php';

$apiKey = '';
$apiEmail = 'wutno@example.com';
$zoneKey = '';
$txtIdentifier = ''; //hard linked, need to get this from cloudflare
$domainName = 'example.com';

$letsEncryptEmail = ['wutno@example.com']; //this can be different from $apiEmail

$cloud = new Cloud($apiKey, $apiEmail, $zoneKey, $txtIdentifier, $domainName);
$letsEncrypt = new LEClient($letsEncryptEmail, true /* true is staging */, LECLient::LOG_STATUS);

$order = $letsEncrypt->getOrCreateOrder($domainName, ['*.'.$domainName]);
if(!$order->allAuthorizationsValid())
{
    $pending = $order->getPendingAuthorizations(LEOrder::CHALLENGE_TYPE_DNS);
    if(!empty($pending))
    {
        foreach($pending as $challenge)
        {
            $dnsTXTContents = $cloud->query();
            if($dnsTXTContents->success)
            {
                if($challenge['DNSDigest'] != $dnsTXTContents->result->content) {
                    $replaceDNS = $cloud->replace($challenge['DNSDigest']);
                    if($replaceDNS->success)
                    {
                        print "\nReplaced current DNS TXT key.";
                    } else {
                        //should prob handle errors a bit better ;) maybe be more verbose
                        print "\nSomething went wrong while replacing the DNS TXT entry...";
                    }
                } else {
                    //ok they're the same, we should try to auth
                    $order->verifyPendingOrderAuthorization($challenge['identifier'], LEOrder::CHALLENGE_TYPE_DNS);
                }
            } else {
                //should prob handle errors a bit better ;) maybe be more verbose
                print "\nSomething went wrong while querying CloudFlare...";
            }
        }
    }
}

if($order->allAuthorizationsValid())
{
    if(!$order->isFinalized())
        $order->finalizeOrder();
    if($order->isFinalized())
        $order->getCertificate();
}