<?php

namespace Coinbase\Wallet\Resource;

use Coinbase\Wallet\Enum\ResourceType;

class ZrxNetwork extends Resource
{
    public function __construct()
    {
        parent::__construct(ResourceType::ZRX_NETWORK);
    }
}
