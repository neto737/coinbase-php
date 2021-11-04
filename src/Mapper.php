<?php

namespace Coinbase\Wallet;

use Carbon\Carbon;
use Coinbase\Wallet\Enum\ResourceType;
use Coinbase\Wallet\Exception\LogicException;
use Coinbase\Wallet\Exception\RuntimeException;
use Coinbase\Wallet\Resource\Account;
use Coinbase\Wallet\Resource\Address;
use Coinbase\Wallet\Resource\Application;
use Coinbase\Wallet\Resource\BitcoinAddress;
use Coinbase\Wallet\Resource\BitcoinCashAddress;
use Coinbase\Wallet\Resource\BitcoinCashNetwork;
use Coinbase\Wallet\Resource\BitcoinNetwork;
use Coinbase\Wallet\Resource\Buy;
use Coinbase\Wallet\Resource\CurrentUser;
use Coinbase\Wallet\Resource\Deposit;
use Coinbase\Wallet\Resource\Email;
use Coinbase\Wallet\Resource\EosioAddress;
use Coinbase\Wallet\Resource\EosioNetwork;
use Coinbase\Wallet\Resource\EthereumNetwork;
use Coinbase\Wallet\Resource\EthrereumAddress;
use Coinbase\Wallet\Resource\LitecoinAddress;
use Coinbase\Wallet\Resource\LitecoinNetwork;
use Coinbase\Wallet\Resource\Notification;
use Coinbase\Wallet\Resource\PaymentMethod;
use Coinbase\Wallet\Resource\Resource as BaseResource;
use Coinbase\Wallet\Resource\ResourceCollection as BaseResourceCollection;
use Coinbase\Wallet\Resource\RippleNetwork;
use Coinbase\Wallet\Resource\Sell;
use Coinbase\Wallet\Resource\Transaction;
use Coinbase\Wallet\Resource\User;
use Coinbase\Wallet\Resource\Withdrawal;
use Coinbase\Wallet\Resource\RippleAddress;
use Coinbase\Wallet\Resource\ZrxAddress;
use Coinbase\Wallet\Resource\ZrxNetwork;
use Coinbase\Wallet\Resource\USDCoinAddress;
use Coinbase\Wallet\Resource\USDCoinNetwork;
use Coinbase\Wallet\Value\Fee;
use Coinbase\Wallet\Value\Money;
use Coinbase\Wallet\Value\Network;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use ReflectionObject;
use ReflectionProperty;

class Mapper
{
    private $reflection = [];

    // users

    /**
     * @param ResponseInterface $response
     * @param User|null $user
     * @return User
     */
    public function toUser(ResponseInterface $response, User $user = null): User
    {
        return $this->injectUser($this->decode($response)['data'], $user);
    }

    /**
     * @param CurrentUser $user
     * @return array
     */
    public function fromCurrentUser(CurrentUser $user): array
    {
        return array_intersect_key(
            $this->extractData($user),
            array_flip(['name', 'time_zone', 'native_currency'])
        );
    }

    // accounts

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toAccounts(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectAccount');
    }

    /**
     * @param ResponseInterface $response
     * @param Account|null $account
     * @return Account
     */
    public function toAccount(ResponseInterface $response, Account $account = null): Account
    {
        return $this->injectAccount($this->decode($response)['data'], $account);
    }

    /**
     * @param Account $account
     * @return array
     */
    public function fromAccount(Account $account): array
    {
        return array_intersect_key(
            $this->extractData($account),
            array_flip(['name'])
        );
    }

    // addresses

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toAddresses(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectAddress');
    }

    /**
     * @param ResponseInterface $response
     * @param Address|null $address
     * @return Address
     */
    public function toAddress(ResponseInterface $response, Address $address = null): Address
    {
        return $this->injectAddress($this->decode($response)['data'], $address);
    }

    /**
     * @param Address $address
     * @return array
     */
    public function fromAddress(Address $address): array
    {
        return array_intersect_key(
            $this->extractData($address),
            array_flip(['name', 'callback_url'])
        );
    }

    // transactions

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toTransactions(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectTransaction');
    }

    /**
     * @param ResponseInterface $response
     * @param Transaction|null $transaction
     * @return Transaction
     */
    public function toTransaction(ResponseInterface $response, Transaction $transaction = null): Transaction
    {
        return $this->injectTransaction($this->decode($response)['data'], $transaction);
    }

    /**
     * @param Transaction $transaction
     * @return array
     */
    public function fromTransaction(Transaction $transaction): array
    {
        // validate
        $to = $transaction->getTo();
        if ($to && !($to instanceof Email) && !($to instanceof BitcoinAddress) && !$to instanceof LitecoinAddress && !$to instanceof EthrereumAddress && !$to instanceof BitcoinCashAddress && !$to instanceof USDCoinAddress && !$to instanceof ZrxAddress && !$to instanceof RippleAddress && !$to instanceof EosioAddress && !$to instanceof Account) {
            throw new LogicException(
                'The Coinbase API only accepts transactions to an account, email, bitcoin address, bitcoin cash address, litecoin address, ethereum address, usd coin address, zrx address, xrp address or eos address'
            );
        }

        // filter
        $data = array_intersect_key(
            $this->extractData($transaction),
            array_flip(['type', 'to', 'amount', 'description', 'fee'])
        );

        // to
        if (isset($data['to']['address'])) {
            $data['to'] = $data['to']['address'];
        } elseif (isset($data['to']['email'])) {
            $data['to'] = $data['to']['email'];
        } elseif (isset($data['to']['id'])) {
            $data['to'] = $data['to']['id'];
        }

        // currency
        if (isset($data['amount']['currency'])) {
            $data['currency'] = $data['amount']['currency'];
        }

        // amount
        if (isset($data['amount']['amount'])) {
            $data['amount'] = $data['amount']['amount'];
        }

        return $data;
    }

    // buys

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toBuys(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectBuy');
    }

    /**
     * @param ResponseInterface $response
     * @param Buy|null $buy
     * @return Buy
     */
    public function toBuy(ResponseInterface $response, Buy $buy = null): Buy
    {
        return $this->injectBuy($this->decode($response)['data'], $buy);
    }

    /**
     * @param Buy $buy
     * @return array
     */
    public function fromBuy(Buy $buy): array
    {
        // validate
        if ($buy->getAmount() && $buy->getTotal()) {
            throw new LogicException(
                'The Coinbase API accepts buys with either an amount or a total, but not both'
            );
        }

        // filter
        $data = array_intersect_key(
            $this->extractData($buy),
            array_flip(['amount', 'total', 'payment_method'])
        );

        // currency
        if (isset($data['amount']['currency'])) {
            $data['currency'] = $data['amount']['currency'];
        } elseif (isset($data['total']['currency'])) {
            $data['currency'] = $data['total']['currency'];
        }

        // amount
        if (isset($data['amount']['amount'])) {
            $data['amount'] = $data['amount']['amount'];
        }

        // total
        if (isset($data['total']['amount'])) {
            $data['total'] = $data['total']['amount'];
        }

        // payment method
        if (isset($data['payment_method']['id'])) {
            $data['payment_method'] = $data['payment_method']['id'];
        }

        return $data;
    }

    // sells

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toSells(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectSell');
    }

    /**
     * @param ResponseInterface $response
     * @param Sell|null $sell
     * @return Sell
     */
    public function toSell(ResponseInterface $response, Sell $sell = null): Sell
    {
        return $this->injectSell($this->decode($response)['data'], $sell);
    }

    /**
     * @param Sell $sell
     * @return array
     */
    public function fromSell(Sell $sell): array
    {
        // validate
        if ($sell->getAmount() && $sell->getTotal()) {
            throw new LogicException(
                'The Coinbase API accepts sells with either an amount or a total, but not both'
            );
        }

        // filter
        $data = array_intersect_key(
            $this->extractData($sell),
            array_flip(['amount', 'total', 'payment_method'])
        );

        // currency
        if (isset($data['amount']['currency'])) {
            $data['currency'] = $data['amount']['currency'];
        } elseif (isset($data['total']['currency'])) {
            $data['currency'] = $data['total']['currency'];
        }

        // amount
        if (isset($data['amount']['amount'])) {
            $data['amount'] = $data['amount']['amount'];
        }

        // total
        if (isset($data['total']['amount'])) {
            $data['total'] = $data['total']['amount'];
        }

        // payment method
        if (isset($data['payment_method']['id'])) {
            $data['payment_method'] = $data['payment_method']['id'];
        }

        return $data;
    }

    // deposits

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toDeposits(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectDeposit');
    }

    /**
     * @param ResponseInterface $response
     * @param Deposit|null $deposit
     * @return Deposit
     */
    public function toDeposit(ResponseInterface $response, Deposit $deposit = null): Deposit
    {
        return $this->injectDeposit($this->decode($response)['data'], $deposit);
    }

    /**
     * @param Deposit $deposit
     * @return array
     */
    public function fromDeposit(Deposit $deposit): array
    {
        // filter
        $data = array_intersect_key(
            $this->extractData($deposit),
            array_flip(['amount', 'payment_method'])
        );

        // currency
        if (isset($data['amount']['currency'])) {
            $data['currency'] = $data['amount']['currency'];
        }

        // amount
        if (isset($data['amount']['amount'])) {
            $data['amount'] = $data['amount']['amount'];
        }

        // payment method
        if (isset($data['payment_method']['id'])) {
            $data['payment_method'] = $data['payment_method']['id'];
        }

        return $data;
    }

    // withdrawals

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toWithdrawals(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectWithdrawal');
    }

    /**
     * @param ResponseInterface $response
     * @param Withdrawal|null $withdrawal
     * @return Withdrawal
     */
    public function toWithdrawal(ResponseInterface $response, Withdrawal $withdrawal = null): Withdrawal
    {
        return $this->injectWithdrawal($this->decode($response)['data'], $withdrawal);
    }

    /**
     * @param Withdrawal $withdrawal
     * @return array
     */
    public function fromWithdrawal(Withdrawal $withdrawal): array
    {
        // filter
        $data = array_intersect_key(
            $this->extractData($withdrawal),
            array_flip(['amount', 'payment_method'])
        );

        // currency
        if (isset($data['amount']['currency'])) {
            $data['currency'] = $data['amount']['currency'];
        }

        // amount
        if (isset($data['amount']['amount'])) {
            $data['amount'] = $data['amount']['amount'];
        }

        // payment method
        if (isset($data['payment_method']['id'])) {
            $data['payment_method'] = $data['payment_method']['id'];
        }

        return $data;
    }

    // payment methods

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toPaymentMethods(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectPaymentMethod');
    }

    /**
     * @param ResponseInterface $response
     * @param PaymentMethod|null $paymentMethod
     * @return PaymentMethod
     */
    public function toPaymentMethod(ResponseInterface $response, PaymentMethod $paymentMethod = null): PaymentMethod
    {
        return $this->injectPaymentMethod($this->decode($response)['data'], $paymentMethod);
    }

    // notifications

    /**
     * @param ResponseInterface $response
     * @return BaseResourceCollection
     */
    public function toNotifications(ResponseInterface $response): BaseResourceCollection
    {
        return $this->toCollection($response, 'injectNotification');
    }

    /**
     * @param ResponseInterface $response
     * @param Notification|null $notification
     * @return Notification
     */
    public function toNotification(ResponseInterface $response, Notification $notification = null): Notification
    {
        return $this->injectNotification($this->decode($response)['data'], $notification);
    }

    // misc

    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function toData(ResponseInterface $response): array
    {
        return $this->decode($response)['data'];
    }

    /**
     * @param ResponseInterface $response
     * @return Money|null
     */
    public function toMoney(ResponseInterface $response): ?Money
    {
        $data = $this->decode($response)['data'];

        return new Money($data['amount'], $data['currency']);
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function decode(ResponseInterface $response): array
    {
        return json_decode($response->getBody(), true);
    }

    // private

    private function toCollection(ResponseInterface $response, $method): BaseResourceCollection
    {
        $data = $this->decode($response);

        if (isset($data['pagination'])) {
            $coll = new BaseResourceCollection(
                $data['pagination']['previous_uri'],
                $data['pagination']['next_uri']
            );
        } else {
            $coll = new BaseResourceCollection();
        }

        foreach ($data['data'] as $resource) {
            $coll->add($this->$method($resource));
        }

        return $coll;
    }

    private function injectUser(array $data, User $user = null): BaseResource
    {
        return $this->injectResource($data, $user ?: new User());
    }

    private function injectAccount(array $data, Account $account = null): BaseResource
    {
        return $this->injectResource($data, $account ?: new Account());
    }

    private function injectAddress(array $data, Address $address = null): BaseResource
    {
        return $this->injectResource($data, $address ?: new Address());
    }

    private function injectApplication(array $data, Application $application = null): BaseResource
    {
        return $this->injectResource($data, $application ?: new Application());
    }

    private function injectTransaction(array $data, Transaction $transaction = null): BaseResource
    {
        return $this->injectResource($data, $transaction ?: new Transaction());
    }

    private function injectBuy(array $data, Buy $buy = null): BaseResource
    {
        return $this->injectResource($data, $buy ?: new Buy());
    }

    private function injectSell(array $data, Sell $sell = null): BaseResource
    {
        return $this->injectResource($data, $sell ?: new Sell());
    }

    private function injectDeposit(array $data, Deposit $deposit = null): BaseResource
    {
        return $this->injectResource($data, $deposit ?: new Deposit());
    }

    private function injectWithdrawal(array $data, Withdrawal $withdrawal = null): BaseResource
    {
        return $this->injectResource($data, $withdrawal ?: new Withdrawal());
    }

    private function injectPaymentMethod(array $data, PaymentMethod $paymentMethod = null): BaseResource
    {
        return $this->injectResource($data, $paymentMethod ?: new PaymentMethod());
    }

    public function injectNotification(array $data, Notification $notification = null): BaseResource
    {
        return $this->injectResource($data, $notification ?: new Notification());
    }

    private function injectResource(array $data, BaseResource $resource): BaseResource
    {
        $properties = $this->getReflectionProperties($resource);

        // add raw data to object
        $properties['raw_data']->setValue($resource, $data);

        foreach ($properties as $key => $property) {
            if (isset($data[$key])) {
                $property->setValue($resource, $this->toPhp($key, $data[$key]));
            }
        }

        return $resource;
    }

    private function extractData(BaseResource $resource): array
    {
        $data = [];
        foreach ($this->getReflectionProperties($resource) as $key => $property) {
            if (null !== $value = $this->fromPhp($property->getValue($resource))) {
                $data[$key] = $value;
            }
        }

        // remove raw data from array
        unset($data['raw_data']);

        return $data;
    }

    /**
     * @param BaseResource $resource
     * @return ReflectionProperty[]
     */
    private function getReflectionProperties(BaseResource $resource): array
    {
        $type = $resource->getResourceType();

        if (isset($this->reflection[$type])) {
            return $this->reflection[$type];
        }

        $class = new ReflectionObject($resource);
        $properties = [];
        do {
            foreach ($class->getProperties() as $property) {
                $property->setAccessible(true);
                $properties[self::snakeCase($property->getName())] = $property;
            }
        } while ($class = $class->getParentClass());

        return $this->reflection[$type] = $properties;
    }

    private function toPhp($key, $value)
    {
        if ('_at' === substr($key, -3)) {
            // timestamp
            return new Carbon($value);
        }

        if (is_scalar($value)) {
            // misc
            return $value;
        }

        if (is_integer(key($value))) {
            // list
            $list = [];
            foreach ($value as $k => $v) {
                $list[$k] = $this->toPhp($k, $v);
            }

            return $list;
        }

        if (isset($value['resource'])) {
            // resource
            return $this->createResource($value['resource'], $value);
        }

        if (isset($value['amount']) && isset($value['currency'])) {
            // money
            return new Money($value['amount'], $value['currency']);
        }

        if ('network' === $key && isset($value['status'])) {
            // network
            return new Network($value['status'], isset($value['hash']) ? $value['hash'] : null, isset($value['transaction_fee']) ? $value['transaction_fee'] : null);
        }

        if (isset($value['type']) && isset($value['amount']) && isset($value['amount']['amount']) && isset($value['amount']['currency'])) {
            // fee
            return new Fee($value['type'], new Money($value['amount']['amount'], $value['amount']['currency']));
        }

        return $value;
    }

    private function fromPhp($value)
    {
        if (is_scalar($value)) {
            // misc
            return $value;
        }

        if (is_array($value)) {
            // list
            $list = [];
            foreach ($value as $k => $v) {
                $list[$k] = $this->fromPhp($v);
            }

            return $list;
        }

        if ($value instanceof DateTime || $value instanceof Carbon) {
            // timestamp
            return $value->format(DateTime::ISO8601);
        }

        if ($value instanceof Email) {
            // email
            return [
                'resource' => ResourceType::EMAIL,
                'email' => $value->getEmail(),
            ];
        }

        if ($value instanceof BitcoinAddress) {
            // bitcoin address
            return [
                'resource' => ResourceType::BITCOIN_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }

        if($value instanceof BitcoinCashAddress){
            // bitcoin-cash address
            return [
                'resource' => ResourceType::BITCOIN_CASH_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }

        if($value instanceof LitecoinAddress){
            // litecoin address
            return [
                'resource' => ResourceType::LITECOIN_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }

        if($value instanceof EthrereumAddress){
            // ethereum address
            return [
                'resource' => ResourceType::ETHEREUM_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }
        if($value instanceof EosioAddress){
            // eos address
            return [
                'resource' => ResourceType::EOSIO_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }

        if($value instanceof RippleAddress){
            // xrp address
            return [
                'resource' => ResourceType::EOSIO_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }

        if($value instanceof ZrxAddress){
            // zrx address
            return [
                'resource' => ResourceType::ZRX_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }

        if($value instanceof USDCoinAddress){
            // usd coin address
            return [
                'resource' => ResourceType::USD_COIN_ADDRESS,
                'address' => $value->getAddress(),
            ];
        }

        if ($value instanceof BaseResource) {
            // resource
            return [
                'id' => $value->getId(),
                'resource' => $value->getResourceType(),
                'resource_path' => $value->getResourcePath(),
            ];
        }

        if ($value instanceof Money) {
            // money
            return [
                'amount' => $value->getAmount(),
                'currency' => $value->getCurrency(),
            ];
        }

        if ($value instanceof Network) {
            // network
            $data = ['status' => $value->getStatus()];
            if ($hash = $value->getHash()) {
                $data['hash'] = $hash;
            }

            return $data;
        }

        if ($value instanceof Fee) {
            // fee
            return [
                'type' => $value->getType(),
                'amount' => [
                    'amount' => $value->getAmount()->getAmount(),
                    'currency' => $value->getAmount()->getCurrency(),
                ],
            ];
        }

        // fail quietly
        return $value;
    }

    private static function snakeCase($word): string
    {
        // copied from doctrine/inflector
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $word));
    }

    private function createResource($type, array $data)
    {
        $expanded = $this->isExpanded($data);

        switch ($type) {
            case ResourceType::ACCOUNT:
                return $expanded ? $this->injectAccount($data) : new Account($data['resource_path']);
            case ResourceType::ZRX_ADDRESS:
            case ResourceType::RIPPLE_ADDRESS:
            case ResourceType::EOSIO_ADDRESS:
            case ResourceType::BITCOIN_CASH_ADDRESS:
            case ResourceType::USD_COIN_ADDRESS:
            case ResourceType::ETHEREUM_ADDRESS:
            case ResourceType::LITECOIN_ADDRESS:
            case ResourceType::BITCOIN_ADDRESS:
            case ResourceType::ADDRESS:
                return $expanded ? $this->injectAddress($data) : new Address($data['resource_path'], $type);
            case ResourceType::APPLICATION:
                return $expanded ? $this->injectApplication($data) : new Application($data['resource_path']);
            case ResourceType::BUY:
                return $expanded ? $this->injectBuy($data) : new Buy($data['resource_path']);
            case ResourceType::DEPOSIT:
                return $expanded ? $this->injectDeposit($data) : new Deposit($data['resource_path']);
            case ResourceType::EMAIL:
                return new Email($data['email']);
            case ResourceType::PAYMENT_METHOD:
                return $expanded ? $this->injectPaymentMethod($data) : new PaymentMethod($data['resource_path']);
            case ResourceType::SELL:
                return $expanded ? $this->injectSell($data) : new Sell($data['resource_path']);
            case ResourceType::TRANSACTION:
                return $expanded ? $this->injectTransaction($data) : new Transaction(null, $data['resource_path']);
            case ResourceType::USER:
                return $expanded ? $this->injectUser($data) : new User($data['resource_path']);
            case ResourceType::WITHDRAWAL:
                return $expanded ? $this->injectWithdrawal($data) : new Withdrawal($data['resource_path']);
            case ResourceType::NOTIFICATION:
                return $expanded ? $this->injectNotification($data) : new Notification($data['resource_path']);
            case ResourceType::BITCOIN_NETWORK:
                return new BitcoinNetwork();
            case ResourceType::BITCOIN_CASH_NETWORK:
                return new BitcoinCashNetwork();
            case ResourceType::LITECOIN_NETWORK:
                return new LitecoinNetwork();
            case ResourceType::ETHEREUM_NETWORK:
                return new EthereumNetwork();
            case ResourceType::EOSIO_NETWORK:
                return new EosioNetwork();
            case ResourceType::RIPPLE_NETWORK:
                return new RippleNetwork();
            case ResourceType::ZRX_NETWORK:
                return new ZrxNetwork();
            case ResourceType::USD_COIN_NETWORK:
                return new USDCoinNetwork();
            default:
                throw new RuntimeException('Unrecognized resource type: '.$type);
        }
    }

    /**
     * Checks if a data array represents an expanded resource.
     *
     * @param array $data
     * @return Boolean Whether the data array represents a complete resource
     */
    private function isExpanded(array $data): bool
    {
        return (Boolean) array_diff(array_keys($data), ['id', 'resource', 'resource_path']);
    }
}
