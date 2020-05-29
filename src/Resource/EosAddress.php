<?php

namespace Coinbase\Wallet\Resource;
use Coinbase\Wallet\Enum\ResourceType;

class EosAddress extends Resource
{
    private $address;

    private $memo;

    public function __construct($address, $memo)
    {
        parent::__construct(ResourceType::EOS_ADDRESS);

        $this->address = $address;
        $this->memo = $memo;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getMemo()
    {
    	return $this->memo;
    }
}
