<?php

namespace Coinbase\Wallet\Tests\Resource;

use Coinbase\Wallet\Resource\CurrentUser;

class CurrentUserTest extends \PHPUnit\Framework\TestCase {
    public function testSetName() {
        $user = new CurrentUser();
        $user->setName('NAME');
        $this->assertEquals('NAME', $user->getName());
    }
}
