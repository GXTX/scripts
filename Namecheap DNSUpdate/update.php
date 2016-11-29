<?php
/*  Namecheap DNSUpdate
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

$rateLimit = 5; //How many seconds should we wait before the next CURL request?
$jsonLocation = "hosts.json"; //Where is the host data located?

if(!file_exists($jsonLocation))
	die("Host data not found!\n");

$hosts = json_decode(file_get_contents($jsonLocation), TRUE);

foreach($hosts as $domain => $details){
	foreach($details['subdomains'] as $subdomain){
		$updateLink = "https://dynamicdns.park-your-domain.com/update?host=" . $subdomain . "&domain=" . $domain . "&password=" . $details['password'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $updateLink);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$update = curl_exec($ch);
		curl_close($ch);

		#http://stackoverflow.com/a/19391553
		$xml = simplexml_load_string($update);
		$json = json_encode($xml);
		$update = json_decode($json, TRUE);

		if($update['ErrCount'] != 0){
			print "Something went wrong..\n";
			foreach($update['errors'] as $error){
				print $error . "\n";
			}
			print $subdomain . "." . $domain . "\n";
		}
		else{
			print "Successfully updated DNS for " . $subdomain . "." . $domain . "\n";
		}
		sleep($rateLimit);
	}
}