<?php

namespace Coinbase\Wallet\Resource;
use Coinbase\Wallet\Enum\ResourceType;

class XrpAddress extends Resource
{
    private $address;

    private $tag;

    public function __construct($address, $tag)
    {
        parent::__construct(ResourceType::XRP_ADDRESS);

        $this->address = $address;
        $this->tag = $tag;
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
