<?php

namespace Coinbase\Wallet\Resource;
use Coinbase\Wallet\Enum\ResourceType;

class EosioAddress extends Resource
{
    private $address;

    public function __construct($address, $memo)
    {
        parent::__construct(ResourceType::EOSIO_ADDRESS);

	    $this->address = $address . ':::ucl:::' . $memo;
    }

    public function getAddress()
    {
        return $this->address;
    }
}
