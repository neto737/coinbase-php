<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Resource\Sell;

class SellActiveRecordTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|Client|null */
    private $client;

    /** @var Sell */
    private $sell;

    protected function setUp(): void {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        ActiveRecordContext::setClient($this->client);

        $this->sell = new Sell();
    }

    protected function tearDown(): void {
        $this->client = null;
        $this->sell = null;
    }

    /**
     * @dataProvider provideForMethodProxy
     */
    public function testMethodProxy($method, $clientMethod) {
        $this->client->expects($this->once())
            ->method($clientMethod)
            ->with($this->sell, []);

        $this->sell->$method();
    }

    public function provideForMethodProxy() {
        return [
            'refresh' => ['refresh', 'refreshSell'],
            'commit'  => ['commit', 'commitSell'],
        ];
    }
}
