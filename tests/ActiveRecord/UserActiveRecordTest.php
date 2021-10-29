<?php

namespace Coinbase\Wallet\Tests\ActiveRecord;

use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Resource\User;

class UserActiveRecordTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|Client */
    private $client;

    /** @var User */
    private $user;

    protected function setUp(): void {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        ActiveRecordContext::setClient($this->client);

        $this->user = new User();
    }

    protected function tearDown(): void {
        $this->client = null;
        $this->user = null;
    }

    /**
     * @dataProvider provideForMethodProxy
     */
    public function testMethodProxy($method, $clientMethod) {
        $this->client->expects($this->once())
            ->method($clientMethod)
            ->with($this->user, []);

        $this->user->$method();
    }

    public function provideForMethodProxy() {
        return [
            'refresh' => ['refresh', 'refreshUser'],
        ];
    }
}
