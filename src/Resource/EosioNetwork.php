<?php

namespace Coinbase\Wallet\Resource;

use Coinbase\Wallet\Enum\ResourceType;

class EosioNetwork extends Resource
{
    public function __construct()
    {
        parent::__construct(ResourceType::EOSIO_NETWORK);
    }
}
