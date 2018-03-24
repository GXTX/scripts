<?php
/**
 * Created by PhpStorm.
 * User: Developer
 * Date: 3/16/2018
 * Time: 12:06 PM
 */

class CFLetsAutomate
{
    private $adapter;
    private $zone;
    private $dns;
    private $zoneID;
    public $txtRecords;
    public $domain;
    public $domains;

    public function __construct(array $config)
    {
        $this->domain = $config['basename'];
        $this->domains = $config['domains'];
        $this->adapter = new Cloudflare\API\Adapter\Guzzle(new Cloudflare\API\Auth\APIKey($config['apiEmail'], $config['apiKey']));
        $this->dns = new Cloudflare\API\Endpoints\DNS($this->adapter);
        $this->zone = new Cloudflare\API\Endpoints\Zones($this->adapter);
        $this->zoneID = $this->zone->getZoneID($this->domain);
    }

    //return true if there is _acme-challenge in dns
    //this is broken.. check remote for retrun
    public function check()
    {
        foreach ($this->dns->listRecords($this->zoneID, 'TXT')->result as $record) {
            if(strpos($record->name, 'acme-challenge')) {
                $this->txtRecords[] = [
                    'id' => $record->id,
                    'name' => $record->name,
                    'content' => $record->content,
                ];
                print $record->name." exists in DNS.\n";
            }
        }

        if(!empty($this->txtRecord))
            return true;
        return false;
    }

    //deletes every dns entry in $this->txtRecords
    public function delete()
    {
        foreach ($this->txtRecords as $record) {
            if ($this->dns->deleteRecord($this->zoneID, $record['id'])) {
                print "Successfully deleted {$record['name']} DNS entry. :)\n";
            } else {
                print "Problem deleting {$record['name']}. :(\n";
                return false;
            }
        }
        return true;
    }

    public function install($identifier, $DNSDigest)
    {
        if ($this->dns->addRecord(
            $this->zoneID,
            'TXT',
            '_acme-challenge.'.$identifier,
            $DNSDigest,
            120,
            false
        )) {
            print "Created _acme-challenge.{$identifier} with \"{$DNSDigest}\".\n";
            return true;
        }
        return false;
    }

    public function remoteCheck($identifier, $DNSDigest)
    {
        if(!empty($this->dns->listRecords($this->zoneID, 'TXT', '_acme-challenge.'.$identifier, $DNSDigest)->result))
            return true;
        return false;
    }

}