<?php

namespace Coinbase\Wallet\Resource;
use Coinbase\Wallet\Enum\ResourceType;

class ZrxAddress extends EthrereumAddress
{
    private $address;

    public function __construct($address)
    {
        parent::__construct(ResourceType::ZRX_ADDRESS);

        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }
}
