<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Resource\Withdrawal;

class WithdrawalActiveRecordTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|Client */
    private $client;

    /** @var Withdrawal */
    private $withdrawal;

    protected function setUp(): void {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        ActiveRecordContext::setClient($this->client);

        $this->withdrawal = new Withdrawal();
    }

    protected function tearDown(): void {
        $this->client = null;
        $this->withdrawal = null;
    }

    /**
     * @dataProvider provideForMethodProxy
     */
    public function testMethodProxy($method, $clientMethod) {
        $this->client->expects($this->once())
            ->method($clientMethod)
            ->with($this->withdrawal, []);

        $this->withdrawal->$method();
    }

    public function provideForMethodProxy() {
        return [
            'refresh' => ['refresh', 'refreshWithdrawal'],
            'commit'  => ['commit', 'commitWithdrawal'],
        ];
    }
}
