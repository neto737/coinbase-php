<?php

namespace Coinbase\Wallet\Resource;

use Coinbase\Wallet\Enum\ResourceType;

class EosNetwork extends Resource
{
    public function __construct()
    {
        parent::__construct(ResourceType::EOS_NETWORK);
    }
}
