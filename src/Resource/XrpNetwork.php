<?php

namespace Coinbase\Wallet\Resource;

use Coinbase\Wallet\Enum\ResourceType;

class XrpNetwork extends Resource
{
    public function __construct()
    {
        parent::__construct(ResourceType::XRP_NETWORK);
    }
}
