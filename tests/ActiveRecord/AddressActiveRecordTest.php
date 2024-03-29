<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Resource\Address;

class AddressActiveRecordTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|Client */
    private $client;

    /** @var Address */
    private $address;

    protected function setUp(): void {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        ActiveRecordContext::setClient($this->client);

        $this->address = new Address();
    }

    protected function tearDown(): void {
        $this->client = null;
        $this->address = null;
    }

    /**
     * @dataProvider provideForMethodProxy
     */
    public function testMethodProxy($method, $clientMethod) {
        $this->client->expects($this->once())
            ->method($clientMethod)
            ->with($this->address, []);

        $this->address->$method();
    }

    public function provideForMethodProxy() {
        return [
            'refresh'         => ['refresh', 'refreshAddress'],
            'getTransactions' => ['getTransactions', 'getAddressTransactions'],
        ];
    }
}
