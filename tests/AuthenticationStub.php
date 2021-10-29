<?php

namespace Coinbase\Wallet\Tests;

use Coinbase\Wallet\Authentication\Authentication;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthenticationStub implements Authentication {
    public function getRequestHeaders(string $method, string $path, string $body): array {
        return ['auth' => 'auth'];
    }

    public function createRefreshRequest($baseUrl): ?RequestInterface {
    }

    public function handleRefreshResponse(RequestInterface $request, ResponseInterface $response) {
    }

    public function createRevokeRequest($baseUrl): ?RequestInterface {
    }

    public function handleRevokeResponse(RequestInterface $request, ResponseInterface $response) {
    }
}
