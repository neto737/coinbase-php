<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;

class ActiveRecordContextTest extends \PHPUnit\Framework\TestCase {
    /**
     * @expectedException \Coinbase\Wallet\Exception\LogicException
     */    
    public function testGetClientException() { 
        ActiveRecordContext::setClient(null);
        ActiveRecordContext::getClient();
    }
}
