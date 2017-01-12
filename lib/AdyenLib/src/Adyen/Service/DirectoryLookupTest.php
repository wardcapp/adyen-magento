<?php

namespace Adyen\Service;

class DirectoryLookupTest extends \Adyen\Service
{

    protected $_directoryLookup;

    public function __construct(\Adyen\Client $client)
    {
        parent::__construct($client);

//        $this->_directoryLookup = new \Adyen\Service\Resource\DirectoryLookup\Directory($this);
    }

    public function directoryLookup($params)
    {
//        $result =  $this->_directoryLookup->requestPost($params);
//        return $result;
    }

}