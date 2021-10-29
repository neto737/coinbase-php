<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Resource\Buy;

class BuyActiveRecordTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|Client */
    private $client;

    /** @var Buy */
    private $buy;

    protected function setUp(): void {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        ActiveRecordContext::setClient($this->client);

        $this->buy = new Buy();
    }

    protected function tearDown(): void {
        $this->client = null;
        $this->buy = null;
    }

    /**
     * @dataProvider provideForMethodProxy
     */
    public function testMethodProxy($method, $clientMethod) {
        $this->client->expects($this->once())
            ->method($clientMethod)
            ->with($this->buy, []);

        $this->buy->$method();
    }

    public function provideForMethodProxy() {
        return [
            'refresh' => ['refresh', 'refreshBuy'],
            'commit'  => ['commit', 'commitBuy'],
        ];
    }
}
