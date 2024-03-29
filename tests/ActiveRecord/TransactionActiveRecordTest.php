<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Resource\Transaction;

class TransactionActiveRecordTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|Client */
    private $client;

    /** @var Transaction */
    private $transaction;

    protected function setUp(): void {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        ActiveRecordContext::setClient($this->client);

        $this->transaction = new Transaction();
    }

    protected function tearDown(): void {
        $this->client = null;
        $this->transaction = null;
    }

    /**
     * @dataProvider provideForMethodProxy
     */
    public function testMethodProxy($method, $clientMethod) {
        $this->client->expects($this->once())
            ->method($clientMethod)
            ->with($this->transaction, []);

        $this->transaction->$method();
    }

    public function provideForMethodProxy() {
        return [
            'refresh'  => ['refresh', 'refreshTransaction'],
            'complete' => ['complete', 'completeTransaction'],
            'resend'   => ['resend', 'resendTransaction'],
            'cancel'   => ['cancel', 'cancelTransaction'],
        ];
    }
}
