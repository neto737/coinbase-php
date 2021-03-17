<?php

namespace Coinbase\Wallet\Resource;

use Coinbase\Wallet\ActiveRecord\AddressActiveRecord;
use Coinbase\Wallet\Enum\ResourceType;
use DateTime;

class Address extends Resource
{
    use AccountResource;
    use AddressActiveRecord;

    /** @var string */
    private $address;

    /** @var string */
    private $name;

    /** @var string */
    private $callbackUrl;

    /** @var DateTime */
    private $createdAt;

    /** @var DateTime */
    private $updatedAt;

    /** @var string */
    private $network;

    /** @var string */
    private $uriScheme;

    /** @var string */
    private $depositUri;

    /**
     * Creates an address reference.
     *
     * @param string $accountId The account id
     * @param string $addressId The address id
     *
     * @return Address An address reference
     */
    public static function reference(string $accountId, string $addressId): Address
    {
        $resourcePath = sprintf('/v2/accounts/%s/addresses/%s', $accountId, $addressId);

        return new static($resourcePath);
    }

    public function __construct($resourcePath = null, $resourceType = ResourceType::ADDRESS)
    {
        parent::__construct($resourceType, $resourcePath);
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function getUriScheme(): string
    {
        return $this->uriScheme;
    }

    public function getDepositUri(): string
    {
        return $this->depositUri;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}
