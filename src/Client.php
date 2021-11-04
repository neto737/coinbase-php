<?php

namespace Coinbase\Wallet;

use Coinbase\Wallet\ActiveRecord\AccountActiveRecord;
use Coinbase\Wallet\ActiveRecord\ActiveRecordContext;
use Coinbase\Wallet\Enum\CurrencyCode;
use Coinbase\Wallet\Enum\Param;
use Coinbase\Wallet\Resource\Account;
use Coinbase\Wallet\Resource\Address;
use Coinbase\Wallet\Resource\Buy;
use Coinbase\Wallet\Resource\CurrentUser;
use Coinbase\Wallet\Resource\Deposit;
use Coinbase\Wallet\Resource\PaymentMethod;
use Coinbase\Wallet\Resource\Resource as BaseResource;
use Coinbase\Wallet\Resource\ResourceCollection as BaseResourceCollection;
use Coinbase\Wallet\Resource\Sell;
use Coinbase\Wallet\Resource\Transaction;
use Coinbase\Wallet\Resource\User;
use Coinbase\Wallet\Resource\Withdrawal;
use Coinbase\Wallet\Resource\Notification;

/**
 * A client for interacting with the Coinbase API.
 *
 * All methods marked as supporting pagination parameters support the following
 * parameters:
 *
 *  * limit (integer)
 *  * order (string)
 *  * starting_after (string)
 *  * ending_before (string)
 *  * fetch_all (Boolean)
 *
 * @link https://developers.coinbase.com/api/v2
 */
class Client
{
    const VERSION = '2.9.0';

    private $http;
    private $mapper;

    /**
     * Creates a new Coinbase client.
     *
     * @param Configuration $configuration
     * @return Client A new Coinbase client
     */
    public static function create(Configuration $configuration): self
    {
        return new static(
            $configuration->createHttpClient(),
            $configuration->createMapper()
        );
    }

    public function __construct(HttpClient $http, Mapper $mapper)
    {
        $this->http = $http;
        $this->mapper = $mapper;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->http;
    }

    public function getMapper(): Mapper
    {
        return $this->mapper;
    }

    /** @return array|null */
    public function decodeLastResponse(): ?array
    {
        if ($response = $this->http->getLastResponse()) {
            return $this->mapper->decode($response);
        }
        return null;
    }

    /**
     * Enables active record methods on resource objects.
     */
    public function enableActiveRecord()
    {
        ActiveRecordContext::setClient($this);
    }

    // data api

    public function getCurrencies(array $params = []): array
    {
        return $this->getAndMapData('/v2/currencies', $params);
    }

    public function getExchangeRates($currency = null, array $params = []): array
    {
        if ($currency) {
            $params['currency'] = $currency;
        }

        return $this->getAndMapData('/v2/exchange-rates', $params);
    }

    public function getBuyPrice($currency = null, array $params = []): ?Value\Money
    {
        // If AAA-BBB format, use it. If fiat only given, use BTC-XXX.
        // If undefined, use BTC-USD.
        if (strpos($currency, '-') !== false) {
            $pair = $currency;
        } else if ($currency) {
            $pair =  CurrencyCode::pair(CurrencyCode::BTC, $currency);
        } else {
            $pair = CurrencyCode::pair(CurrencyCode::BTC, CurrencyCode::USD);
        }

        return $this->getAndMapMoney('/v2/prices/' . $pair . '/buy', $params);
    }

    public function getSellPrice($currency = null, array $params = []): ?Value\Money
    {
        if (strpos($currency, '-') !== false) {
            $pair = $currency;
        } else if ($currency) {
            $pair = CurrencyCode::pair(CurrencyCode::BTC, $currency);
        } else {
            $pair = CurrencyCode::pair(CurrencyCode::BTC, CurrencyCode::USD);
        }

        return $this->getAndMapMoney('/v2/prices/' . $pair . '/sell', $params);
    }

    public function getSpotPrice($currency = null, array $params = []): ?Value\Money
    {
        if (strpos($currency, '-') !== false) {
            $pair = $currency;
        } else if ($currency) {
            $pair = CurrencyCode::pair(CurrencyCode::BTC, $currency);
        } else {
            $pair = CurrencyCode::pair(CurrencyCode::BTC, CurrencyCode::USD);
        }

        return $this->getAndMapMoney('/v2/prices/' . $pair . '/spot', $params);
    }

    public function getHistoricPrices($currency = null, array $params = []): array
    {
        if ($currency) {
            $params['currency'] = $currency;
        }

        return $this->getAndMapData('/v2/prices/historic', $params);
    }

    public function getTime(array $params = []): array
    {
        return $this->getAndMapData('/v2/time', $params);
    }

    // authentication

    public function refreshAuthentication(array $params = [])
    {
        $this->http->refreshAuthentication($params);
    }

    public function revokeAuthentication(array $params = [])
    {
        $this->http->revokeAuthentication($params);
    }

    // users

    /**
     * @param $userId
     * @param array $params
     * @return User
     */
    public function getUser($userId, array $params = []): User
    {
        return $this->getAndMap('/v2/users/'.$userId, $params, 'toUser');
    }

    public function refreshUser(User $user, array $params = [])
    {
        $this->getAndMap($user->getResourcePath(), $params, 'toUser', $user);
    }

    /**
     * @param array $params
     * @return CurrentUser
     */
    public function getCurrentUser(array $params = []): CurrentUser
    {
        return $this->getAndMap('/v2/user', $params, 'toUser', new CurrentUser());
    }

    public function getCurrentAuthorization(array $params = []): array
    {
        return $this->getAndMapData('/v2/user/auth', $params);
    }

    public function updateCurrentUser(CurrentUser $user, array $params = [])
    {
        $data = $this->mapper->fromCurrentUser($user);
        $response = $this->http->put('/v2/user', $data + $params);
        $this->mapper->toUser($response, $user);
    }

    // accounts

    /**
     * Lists the current user's accounts.
     *
     * Supports pagination parameters.
     *
     * @param array $params
     * @return BaseResourceCollection|Account[]
     */
    public function getAccounts(array $params = [])
    {
        return $this->getAndMapCollection('/v2/accounts', $params, 'toAccounts');
    }

    public function loadNextAccounts(BaseResourceCollection $accounts, array $params = [])
    {
        $this->loadNext($accounts, $params, 'toAccounts');
    }

    /**
     * @param string $accountId
     * @param array $params
     * @return Account
     */
    public function getAccount(string $accountId, array $params = []): Account
    {
        return $this->getAndMap('/v2/accounts/'.$accountId, $params, 'toAccount');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param array $params
     */
    public function refreshAccount($account, array $params = [])
    {
        $this->getAndMap($account->getResourcePath(), $params, 'toAccount', $account);
    }

    public function createAccount(Account $account, array $params = [])
    {
        $data = $this->mapper->fromAccount($account);
        $this->postAndMap('/v2/accounts', $data + $params, 'toAccount', $account);
    }

    /**
     * @param array $params
     * @return Account
     */
    public function getPrimaryAccount(array $params = []): Account
    {
        return $this->getAndMap('/v2/accounts/primary', $params, 'toAccount');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param array $params
     */
    public function setPrimaryAccount($account, array $params = [])
    {
        $this->postAndMap($account->getResourcePath().'/primary', $params, 'toAccount', $account);
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param array $params
     */
    public function updateAccount($account, array $params = [])
    {
        $data = $this->mapper->fromAccount($account);
        $response = $this->http->put($account->getResourcePath(), $data + $params);
        $this->mapper->toAccount($response, $account);
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param array $params
     */
    public function deleteAccount($account, array $params = [])
    {
        $this->http->delete($account->getResourcePath(), $params);
    }

    // addresses

    /**
     * Lists addresses for an account.
     *
     * Supports pagination parameters.
     *
     * @param Account|AccountActiveRecord $account
     * @param array $params
     * @return BaseResourceCollection|Address[]
     */
    public function getAccountAddresses($account, array $params = [])
    {
        return $this->getAndMapCollection($account->getResourcePath().'/addresses', $params, 'toAddresses');
    }

    public function loadNextAddresses(BaseResourceCollection $addresses, array $params = [])
    {
        $this->loadNext($addresses, $params, 'toAddresses');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param $addressId
     * @param array $params
     * @return Address
     */
    public function getAccountAddress($account, $addressId, array $params = []): Address
    {
        $path = sprintf('%s/addresses/%s', $account->getResourcePath(), $addressId);

        return $this->getAndMap($path, $params, 'toAddress');
    }

    public function refreshAddress(Address $address, array $params = []): BaseResource
    {
        return $this->getAndMap($address->getResourcePath(), $params, 'toAddress', $address);
    }

    /**
     * Lists transactions for an address.
     *
     * Supports pagination parameters.
     *
     * @param Address $address
     * @param array $params
     * @return BaseResourceCollection|Transaction[]
     */
    public function getAddressTransactions(Address $address, array $params = [])
    {
        return $this->getAndMapCollection($address->getResourcePath().'/transactions', $params, 'toTransactions');
    }

    public function loadNextAddressTransactions(BaseResourceCollection $transactions, array $params = [])
    {
        $this->loadNextTransactions($transactions, $params);
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param Address $address
     * @param array $params
     * @return mixed
     */
    public function createAccountAddress($account, Address $address, array $params = [])
    {
        $data = $this->mapper->fromAddress($address);
        return $this->postAndMap($account->getResourcePath().'/addresses', $data + $params, 'toAddress', $address);
    }

    // transactions

    /**
     * Lists transactions for an account.
     *
     * Supports pagination parameters.
     *
     * @param Account|AccountActiveRecord $account
     * @param array $params
     * @return BaseResourceCollection|Transaction[]
     */
    public function getAccountTransactions($account, array $params = [])
    {
        return $this->getAndMapCollection($account->getResourcePath().'/transactions', $params, 'toTransactions');
    }

    public function loadNextTransactions(BaseResourceCollection $transactions, array $params = [])
    {
        $this->loadNext($transactions, $params, 'toTransactions');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param $transactionId
     * @param array $params
     * @return Transaction
     */
    public function getAccountTransaction($account, $transactionId, array $params = []): Transaction
    {
        $path = sprintf('%s/transactions/%s', $account->getResourcePath(), $transactionId);

        return $this->getAndMap($path, $params, 'toTransaction');
    }

    public function refreshTransaction(Transaction $transaction, array $params = [])
    {
        $this->getAndMap($transaction->getResourcePath(), $params, 'toTransaction', $transaction);
    }

    /**
     * Creates a new transaction.
     *
     * Supported parameters include:
     *
     *  * skip_notifications (Boolean)
     *  * fee (float)
     *  * idem (string)
     * @param Account|AccountActiveRecord $account
     * @param Transaction $transaction
     * @param array $params
     */
    public function createAccountTransaction($account, Transaction $transaction, array $params = [])
    {
        $data = $this->mapper->fromTransaction($transaction);
        $this->postAndMap($account->getResourcePath().'/transactions', $data + $params, 'toTransaction', $transaction);
    }

    public function completeTransaction(Transaction $transaction, array $params = [])
    {
        $this->http->post($transaction->getResourcePath().'/complete', $params);
    }

    public function resendTransaction(Transaction $transaction, array $params = [])
    {
        $this->http->post($transaction->getResourcePath().'/resend', $params);
    }

    public function cancelTransaction(Transaction $transaction, array $params = [])
    {
        $this->http->delete($transaction->getResourcePath(), $params);
    }

    // buys

    /**
     * Lists buys for an account.
     *
     * Supports pagination parameters.
     *
     * @param Account|AccountActiveRecord $account
     * @param array $params
     * @return BaseResourceCollection|Buy[]
     */
    public function getAccountBuys($account, array $params = [])
    {
        return $this->getAndMapCollection($account->getResourcePath().'/buys', $params, 'toBuys');
    }

    public function loadNextBuys(BaseResourceCollection $buys, array $params = [])
    {
        $this->loadNext($buys, $params, 'toBuys');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param $buyId
     * @param array $params
     * @return Buy
     */
    public function getAccountBuy($account, $buyId, array $params = []): Buy
    {
        $path = sprintf('%s/buys/%s', $account->getResourcePath(), $buyId);

        return $this->getAndMap($path, $params, 'toBuy');
    }

    public function refreshBuy(Buy $buy, array $params = [])
    {
        $this->getAndMap($buy->getResourcePath(), $params, 'toBuy', $buy);
    }

    /**
     * Buys some amount of bitcoin.
     *
     * Supported parameters include:
     *
     *  * agree_btc_amount_varies (Boolean)
     *  * commit (Boolean)
     *  * quote (Boolean)
     * @param Account|AccountActiveRecord $account
     * @param Buy $buy
     * @param array $params
     */
    public function createAccountBuy($account, Buy $buy, array $params = [])
    {
        $data = $this->mapper->fromBuy($buy);
        $this->postAndMap($account->getResourcePath().'/buys', $data + $params, 'toBuy', $buy);
    }

    public function commitBuy(Buy $buy, array $params = [])
    {
        $this->postAndMap($buy->getResourcePath().'/commit', $params, 'toBuy', $buy);
    }

    // sells

    /**
     * Lists sells for an account.
     *
     * Supports pagination parameters.
     *
     * @param Account|AccountActiveRecord $account
     * @param array $params
     * @return BaseResourceCollection|Sell[]
     */
    public function getAccountSells($account, array $params = [])
    {
        return $this->getAndMapCollection($account->getResourcePath().'/sells', $params, 'toSells');
    }

    public function loadNextSells(BaseResourceCollection $sells, array $params = [])
    {
        $this->loadNext($sells, $params, 'toSells');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param $sellId
     * @param array $params
     * @return Sell
     */
    public function getAccountSell($account, $sellId, array $params = []): Sell
    {
        $path = sprintf('%s/sells/%s', $account->getResourcePath(), $sellId);

        return $this->getAndMap($path, $params, 'toSell');
    }

    public function refreshSell(Sell $sell, array $params = [])
    {
        $this->getAndMap($sell->getResourcePath(), $params, 'toSell', $sell);
    }

    /**
     * Sells some amount of bitcoin.
     *
     * Supported parameters include:
     *
     *  * agree_btc_amount_varies (Boolean)
     *  * commit (Boolean)
     *  * quote (Boolean)
     * @param Account|AccountActiveRecord $account
     * @param Sell $sell
     * @param array $params
     */
    public function createAccountSell($account, Sell $sell, array $params = [])
    {
        $data = $this->mapper->fromSell($sell);
        $this->postAndMap($account->getResourcePath().'/sells', $data + $params, 'toSell', $sell);
    }

    public function commitSell(Sell $sell, array $params = [])
    {
        $this->postAndMap($sell->getResourcePath().'/commit', $params, 'toSell', $sell);
    }

    // deposits

    /**
     * Lists deposits for an account.
     *
     * Supports pagination parameters.
     *
     * @param Account|AccountActiveRecord $account
     * @param array $params
     * @return BaseResourceCollection|Deposit[]
     */
    public function getAccountDeposits($account, array $params = [])
    {
        return $this->getAndMapCollection($account->getResourcePath().'/deposits', $params, 'toDeposits');
    }

    public function loadNextDeposits(BaseResourceCollection $deposits, array $params = [])
    {
        $this->loadNext($deposits, $params, 'toDeposits');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param $depositId
     * @param array $params
     * @return Deposit
     */
    public function getAccountDeposit($account, $depositId, array $params = []): Deposit
    {
        $path = sprintf('%s/deposits/%s', $account->getResourcePath(), $depositId);

        return $this->getAndMap($path, $params, 'toDeposit');
    }

    public function refreshDeposit(Deposit $deposit, array $params = [])
    {
        $this->getAndMap($deposit->getResourcePath(), $params, 'toDeposit', $deposit);
    }

    /**
     * Deposits some amount of funds.
     *
     * Supported parameters include:
     *
     *  * commit (Boolean)
     * @param Account|AccountActiveRecord $account
     * @param Deposit $deposit
     * @param array $params
     */
    public function createAccountDeposit($account, Deposit $deposit, array $params = [])
    {
        $data = $this->mapper->fromDeposit($deposit);
        $this->postAndMap($account->getResourcePath().'/deposits', $data + $params, 'toDeposit', $deposit);
    }

    public function commitDeposit(Deposit $deposit, array $params = [])
    {
        $this->postAndMap($deposit->getResourcePath().'/commit', $params, 'toDeposit', $deposit);
    }

    // withdrawals

    /**
     * Lists withdrawals for an account.
     *
     * Supports pagination parameters.
     *
     * @param Account|AccountActiveRecord $account
     * @param array $params
     * @return BaseResourceCollection|Withdrawal[]
     */
    public function getAccountWithdrawals($account, array $params = [])
    {
        return $this->getAndMapCollection($account->getResourcePath().'/withdrawals', $params, 'toWithdrawals');
    }

    public function loadNextWithdrawals(BaseResourceCollection $withdrawals, array $params = [])
    {
        $this->loadNext($withdrawals, $params, 'toWithdrawals');
    }

    /**
     * @param Account|AccountActiveRecord $account
     * @param $withdrawalId
     * @param array $params
     * @return Withdrawal
     */
    public function getAccountWithdrawal($account, $withdrawalId, array $params = []): Withdrawal
    {
        $path = sprintf('%s/withdrawals/%s', $account->getResourcePath(), $withdrawalId);

        return $this->getAndMap($path, $params, 'toWithdrawal');
    }

    public function refreshWithdrawal(Withdrawal $withdrawal, array $params = [])
    {
        $this->getAndMap($withdrawal->getResourcePath(), $params, 'toWithdrawal', $withdrawal);
    }

    /**
     * Withdraws some amount of funds.
     *
     * Supported parameters include:
     *
     *  * commit (Boolean)
     * @param Account|AccountActiveRecord $account
     * @param Withdrawal $withdrawal
     * @param array $params
     */
    public function createAccountWithdrawal($account, Withdrawal $withdrawal, array $params = [])
    {
        $data = $this->mapper->fromWithdrawal($withdrawal);
        $this->postAndMap($account->getResourcePath().'/withdrawals', $data + $params, 'toWithdrawal', $withdrawal);
    }

    public function commitWithdrawal(Withdrawal $withdrawal, array $params = [])
    {
        $this->postAndMap($withdrawal->getResourcePath().'/commit', $params, 'toWithdrawal', $withdrawal);
    }

    // payment methods

    /**
     * Lists payment methods for the current user.
     *
     * Supports pagination parameters.
     *
     * @param array $params
     * @return BaseResourceCollection|PaymentMethod[]
     */
    public function getPaymentMethods(array $params = [])
    {
        return $this->getAndMapCollection('/v2/payment-methods', $params, 'toPaymentMethods');
    }

    public function loadNextPaymentMethods(BaseResourceCollection $paymentMethods, array $params = [])
    {
        $this->loadNext($paymentMethods, $params, 'toPaymentMethods');
    }

    /**
     * @param $paymentMethodId
     * @param array $params
     * @return PaymentMethod
     */
    public function getPaymentMethod($paymentMethodId, array $params = []): PaymentMethod
    {
        return $this->getAndMap('/v2/payment-methods/'.$paymentMethodId, $params, 'toPaymentMethod');
    }

    public function refreshPaymentMethod(PaymentMethod $paymentMethod, array $params = [])
    {
        $this->getAndMap($paymentMethod->getResourcePath(), $params, 'toPaymentMethod', $paymentMethod);
    }

    /**
     * Lists notifications where the current user was the subscriber.
     *
     * Supports pagination parameters.
     *
     * @param array $params
     * @return BaseResourceCollection|Notification[]
     */
    public function getNotifications(array $params = [])
    {
        return $this->getAndMapCollection('/v2/notifications', $params, 'toNotifications');
    }

    public function loadNextNotifications(BaseResourceCollection $notifications, array $params = [])
    {
        $this->loadNext($notifications, $params, 'toNotifications');
    }

    /**
     * @param string $notificationId
     * @param array $params
     * @return Notification
     */
    public function getNotification(string $notificationId, array $params = []): Notification
    {
        return $this->getAndMap('/v2/notifications/'.$notificationId, $params, 'toNotification');
    }

    public function refreshNotification(Notification $notification, array $params = [])
    {
        $this->getAndMap($notification->getResourcePath(), $params, 'toNotification', $notification);
    }

    /**
     * Create a Notification object from the body of a notification webhook
     *
     * @param $webhook_body
     * @return BaseResource
     */
    public function parseNotification($webhook_body): BaseResource
    {
        $data = json_decode($webhook_body, true);
        return $this->mapper->injectNotification($data);
    }

    // private

    private function getAndMapData($path, array $params = []): array
    {
        $response = $this->http->get($path, $params);

        return $this->mapper->toData($response);
    }

    private function getAndMapMoney($path, array $params = []): ?Value\Money
    {
        $response = $this->http->get($path, $params);

        return $this->mapper->toMoney($response);
    }

    /**
     * @param $path
     * @param array $params
     * @param $mapperMethod
     * @return BaseResourceCollection|BaseResource[]
     */
    private function getAndMapCollection($path, array $params, $mapperMethod)
    {
        $fetchAll = isset($params[Param::FETCH_ALL]) ? $params[Param::FETCH_ALL] : false;
        unset($params[Param::FETCH_ALL]);

        $response = $this->http->get($path, $params);

        /** @var BaseResourceCollection $collection */
        $collection = $this->mapper->$mapperMethod($response);

        if ($fetchAll) {
            while ($collection->hasNextPage()) {
                $this->loadNext($collection, $params, $mapperMethod);
            }
        }

        return $collection;
    }

    /**
     * @param $path
     * @param array $params
     * @param $mapperMethod
     * @param BaseResource|null $resource
     * @return mixed
     */
    private function getAndMap($path, array $params, $mapperMethod, BaseResource $resource = null)
    {
        $response = $this->http->get($path, $params);

        return $this->mapper->$mapperMethod($response, $resource);
    }

    /**
     * @param $path
     * @param array $params
     * @param $mapperMethod
     * @param BaseResource|null $resource
     * @return mixed
     */
    private function postAndMap($path, array $params, $mapperMethod, BaseResource $resource = null)
    {
        $response = $this->http->post($path, $params);

        return $this->mapper->$mapperMethod($response, $resource);
    }

    /**
     * @param BaseResourceCollection $collection
     * @param array $params
     * @param $mapperMethod
     */
    private function loadNext(BaseResourceCollection $collection, array $params, $mapperMethod)
    {
        $response = $this->http->get($collection->getNextUri(), $params);
        $nextPage = $this->mapper->$mapperMethod($response);
        $collection->mergeNextPage($nextPage);
    }
}
