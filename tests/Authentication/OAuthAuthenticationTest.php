<?php

namespace Coinbase\Wallet\Tests\Authentication;

use Coinbase\Wallet\Authentication\OAuthAuthentication;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Exception\LogicException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class OAuthAuthenticationTest extends \PHPUnit\Framework\TestCase {
    public function testGetRequestHeaders() {
        $expected = [
            'Authorization' => 'Bearer ACCESS_TOKEN',
        ];

        $auth = new OAuthAuthentication('ACCESS_TOKEN');
        $actual = $auth->getRequestHeaders('POST', '/', '{"foo":"bar"}');
        $this->assertEquals($expected, $actual);
    }

    public function testCreateRefreshRequest() {
        $expected = [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'REFRESH_TOKEN',
        ];

        $auth = new OAuthAuthentication('ACCESS_TOKEN', 'REFRESH_TOKEN');
        $request = $auth->createRefreshRequest(Configuration::DEFAULT_API_URL);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/oauth/token', $request->getRequestTarget());
        $this->assertEquals($expected, json_decode($request->getBody(), true));
    }

    public function testCreateRefreshRequestNoToken() {
        $this->expectException(LogicException::class);

        $auth = new OAuthAuthentication('ACCESS_TOKEN');
        $auth->createRefreshRequest(Configuration::DEFAULT_API_URL);
    }

    public function testHandleRefreshResponse() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|RequestInterface $response */
        $request = $this->createMock(RequestInterface::class);
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResponseInterface $response */
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($stream);
        $stream->expects($this->any())
            ->method('__toString')
            ->willReturn('{"access_token":"NEW_ACCESS","refresh_token":"NEW_REFRESH"}');

        $auth = new OAuthAuthentication('OLD_ACCESS', 'OLD_REFRESH');
        $auth->handleRefreshResponse($request, $response);
        $this->assertEquals('NEW_ACCESS', $auth->getAccessToken());
        $this->assertEquals('NEW_REFRESH', $auth->getRefreshToken());
    }

    public function testCreateRevokeRequest() {
        $expected = [
            'token' => 'ACCESS_TOKEN',
        ];

        $auth = new OAuthAuthentication('ACCESS_TOKEN');
        $request = $auth->createRevokeRequest(Configuration::DEFAULT_API_URL);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/oauth/revoke', $request->getRequestTarget());
        $this->assertEquals($expected, json_decode($request->getBody(), true));
    }
}
