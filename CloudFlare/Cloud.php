<?php

class Cloud
{
    private $apiKey;
    private $apiEmail;
    private $zoneKey;
    private $txtIdentifier;
    private $apiLink;
    public $domainName;
    public $errors;
    public $curlOptions;

    public function __construct($apiKey, $apiEmail, $zoneKey, $txtIdentifier, $domainName)
    {
        $this->apiKey = $apiKey;
        $this->apiEmail = $apiEmail;
        $this->zoneKey = $zoneKey;
        $this->txtIdentifier = $txtIdentifier;
        $this->domainName = $domainName;
        $this->apiLink = 'https://api.cloudflare.com/client/v4/zones/' . $this->zoneKey . '/dns_records/' . $this->txtIdentifier;
        $this->curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Auth-Email: ' . $this->apiEmail,
                'X-Auth-Key: ' . $this->apiKey,
                'Content-Type: application/json'
            ]
        ];
    }

    public function query()
    {
        return $this->curl();
    }

    public function replace($digest)
    {
        return $this->curl([
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => '{"type":"TXT","name":"_acme-challenge.'.$this->domainName.'","content":"'.$digest.'","ttl":1,"proxied":false}'
        ]);
    }

    public function curl($options = null)
    {
        $curlHandle = curl_init($this->apiLink);
        $curlOptions = $this->curlOptions;
        if($options)
            $curlOptions += $options;
        curl_setopt_array($curlHandle, $curlOptions);
        $curlData = curl_exec($curlHandle);
        $curlData = json_decode($curlData);
        if(curl_error($curlHandle))
            die('\ncURL ERROR!!! '. curl_error($curlHandle));
        curl_close($curlHandle);
        return $curlData;
    }


}