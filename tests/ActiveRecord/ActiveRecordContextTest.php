<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Exception\LogicException;

class ActiveRecordContextTest extends \PHPUnit\Framework\TestCase {

    public function testGetClientException() {
        $this->expectException(LogicException::class);

        ActiveRecordContext::setClient(null);
        ActiveRecordContext::getClient();
    }
}
