<?php

namespace Coinbase\Wallet\Tests\Exception;

use Coinbase\Wallet\Exception\HttpException;
use Coinbase\Wallet\Value\Error;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpExceptionTest extends \PHPUnit\Framework\TestCase {
    public function testWrapApiErrors() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|RequestException $exception */
        $exception = $this->getMockBuilder(RequestException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $exception->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $exception->expects($this->any())
            ->method('getResponse')
            ->willReturn($response);
        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($stream);
        $stream->expects($this->any())
            ->method('__toString')
            ->willReturn(json_encode(['errors' => [
                ['id' => 'ID1', 'message' => 'MESSAGE1'],
                ['id' => 'ID2', 'message' => 'MESSAGE2'],
            ]]));

        $actual = HttpException::wrap($exception);
        $this->assertCount(2, $actual->getErrors());
        $this->assertInstanceOf(Error::class, $actual->getError());
        $this->assertEquals('ID1', $actual->getError()->getId());
        $this->assertEquals('MESSAGE1', $actual->getError()->getMessage());
    }

    public function testWrapOAuthError() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|RequestException $exception */
        $exception = $this->getMockBuilder(RequestException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $exception->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $exception->expects($this->any())
            ->method('getResponse')
            ->willReturn($response);
        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($stream);
        $stream->expects($this->any())
            ->method('__toString')
            ->willReturn(json_encode(['error' => 'ID', 'error_description' => 'MESSAGE']));

        $actual = HttpException::wrap($exception);
        $this->assertCount(1, $actual->getErrors());
        $this->assertInstanceOf(Error::class, $actual->getError());
        $this->assertEquals('ID', $actual->getError()->getId());
        $this->assertEquals('MESSAGE', $actual->getError()->getMessage());
    }
}
