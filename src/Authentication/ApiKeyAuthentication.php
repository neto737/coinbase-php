<?php

namespace Coinbase\Wallet\Authentication;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiKeyAuthentication implements Authentication {
    private $apiKey;
    private $apiSecret;

    public function __construct($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getApiSecret() {
        return $this->apiSecret;
    }

    public function setApiSecret($apiSecret) {
        $this->apiSecret = $apiSecret;
    }

    public function getRequestHeaders(string $method, string $path, string $body): array {
        $timestamp = $this->getTimestamp();
        $signature = $this->getHash('sha256', $timestamp . $method . $path . $body, $this->apiSecret);

        return [
            'CB-ACCESS-KEY'       => $this->apiKey,
            'CB-ACCESS-SIGN'      => $signature,
            'CB-ACCESS-TIMESTAMP' => $timestamp,
        ];
    }

    public function createRefreshRequest($baseUrl): ?RequestInterface {
        return null;
    }

    public function handleRefreshResponse(RequestInterface $request, ResponseInterface $response) {
    }

    public function createRevokeRequest($baseUrl): ?RequestInterface {
        return null;
    }

    public function handleRevokeResponse(RequestInterface $request, ResponseInterface $response) {
    }

    // protected
    protected function getTimestamp(): int {
        return time();
    }

    protected function getHash($algo, $data, $key): string {
        return hash_hmac($algo, $data, $key);
    }
}
