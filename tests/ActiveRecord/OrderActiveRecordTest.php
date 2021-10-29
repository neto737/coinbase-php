<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Resource\Order;

class OrderActiveRecordTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|Client */
    private $client;

    /** @var Order */
    private $order;

    protected function setUp(): void {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        ActiveRecordContext::setClient($this->client);

        $this->order = new Order();
    }

    protected function tearDown(): void {
        $this->client = null;
        $this->order = null;
    }

    /**
     * @dataProvider provideForMethodProxy
     */
    public function testMethodProxy($method, $clientMethod) {
        $this->client->expects($this->once())
            ->method($clientMethod)
            ->with($this->order, []);

        $this->order->$method();
    }

    public function provideForMethodProxy() {
        return [
            'refresh' => ['refresh', 'refreshOrder'],
            'refund'  => ['refund', 'refundOrder'],
        ];
    }
}
