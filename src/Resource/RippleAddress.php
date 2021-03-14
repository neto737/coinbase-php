<?php

namespace Coinbase\Wallet\Resource;
use Coinbase\Wallet\Enum\ResourceType;

class RippleAddress extends Resource
{
    private $address;

    public function __construct($address, $tag)
    {
        parent::__construct(ResourceType::RIPPLE_ADDRESS);

	    $this->address = $address . ':::ucl:::' . $tag;
    }

    public function getAddress()
    {
        return $this->address;
    }
}
