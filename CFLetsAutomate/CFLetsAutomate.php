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
    private $txtRecord;
    public  $domain;

    public function __construct(array $info)
    {
        if(preg_match('(\*+)', $info['domain'])) //is the input domain a wildcard?
            $this->domain = substr($info['domain'], 2);

        $this->adapter = new Cloudflare\API\Adapter\Guzzle(new Cloudflare\API\Auth\APIKey($info['apiEmail'], $info['apiKey']));
        $this->dns     = new Cloudflare\API\Endpoints\DNS($this->adapter);
        $this->zone    = new Cloudflare\API\Endpoints\Zones($this->adapter);
        $this->zoneID  = $this->zone->getZoneID($this->domain);

        foreach ($this->dns->listRecords($this->zoneID)->result as $record) {
            if ($record->type == 'TXT' && $record->name == '_acme-challenge.' . $this->domain) {
                print "_acme-challenge.{$this->domain} exists in DNS.\n";
                $this->txtRecord = $record;
                break; //stop at the first result, this is dumb
            }
        }
    }

    //return true if there is _acme-challenge in dns
    public function check()
    {
        if(!empty($this->txtRecord))
            return true;
        return false;
    }

    //this is a dumb function, that just willy nilly deletes any txt entries with _acme-challenge.domain
    public function delete()
    {
        if($this->dns->deleteRecord($this->zoneID, $this->txtRecord->id)){
            print "Deleted _acme-challenge.{$this->domain} TXT record.\n";
            return true;
        }
        return false;
    }

    public function install($dnsContent)
    {
         if($this->dns->addRecord($this->zoneID, 'TXT', '_acme-challenge.' . $this->domain, $dnsContent, 120, false)){
            print "Created _acme-challenge.{$this->domain} : {$dnsContent}\n";
            print "You should wait a few minutes before running finalize.\n";
            return true;
         }
         return false;
    }
}