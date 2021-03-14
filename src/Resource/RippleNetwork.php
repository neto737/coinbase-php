<?php

namespace Coinbase\Wallet\Resource;

use Coinbase\Wallet\Enum\ResourceType;

class RippleNetwork extends Resource
{
    public function __construct()
    {
        parent::__construct(ResourceType::RIPPLE_NETWORK);
    }
}
