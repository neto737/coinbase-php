<?php

namespace Coinbase\Wallet\Tests;

use Coinbase\Wallet\Client;
use Coinbase\Wallet\HttpClient;
use Coinbase\Wallet\Mapper;
use Coinbase\Wallet\Resource\Account;
use Coinbase\Wallet\Resource\Address;
use Coinbase\Wallet\Resource\Buy;
use Coinbase\Wallet\Resource\CurrentUser;
use Coinbase\Wallet\Resource\Deposit;
use Coinbase\Wallet\Resource\PaymentMethod;
use Coinbase\Wallet\Resource\ResourceCollection;
use Coinbase\Wallet\Resource\Sell;
use Coinbase\Wallet\Resource\Transaction;
use Coinbase\Wallet\Resource\User;
use Coinbase\Wallet\Resource\Withdrawal;
use Psr\Http\Message\ResponseInterface;

class ClientTest extends \PHPUnit\Framework\TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject|HttpClient */
    private $http;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Mapper */
    private $mapper;

    /** @var Client */
    private $client;

    public static function setUpBeforeClass(): void {
        date_default_timezone_set('America/New_York');
    }

    protected function setUp(): void {
        $this->http = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapper = $this->createMock(Mapper::class);
        $this->client = new Client($this->http, $this->mapper);
    }

    protected function tearDown(): void {
        $this->http = null;
        $this->mapper = null;
        $this->client = null;
    }

    public function testGetUser() {
        $expected = new User();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/users/USER_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toUser')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getUser('USER_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testGetCurrentUser() {
        $expected = new CurrentUser();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/user', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toUser')
            ->willReturn($expected);

        $actual = $this->client->getCurrentUser(['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testGetCurrentAuthorization() {
        $expected = ['key' => 'value'];
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/user/auth', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toData')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getCurrentAuthorization(['foo' => 'bar']);
        $this->assertEquals($expected, $actual);
    }

    public function testUpdateCurrentUser() {
        $user = new CurrentUser();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromCurrentUser')
            ->with($user)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('put')
            ->with('/v2/user', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toUser');

        $this->client->updateCurrentUser($user, ['foo' => 'bar']);
    }

    public function testGetAccounts() {
        $response = $this->createMock(ResponseInterface::class);
        $expected = new ResourceCollection();

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toAccounts')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccounts(['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testLoadNextAccounts() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $accounts */
        $accounts = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $accounts->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toAccounts')
            ->willReturn($nextPage);
        $accounts->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextAccounts($accounts, ['foo' => 'bar']);
    }

    public function testGetAccount() {
        $expected = new Account();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toAccount')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccount('ACCOUNT_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testCreateAccount() {
        $account = new Account();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromAccount')
            ->with($account)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toAccount')
            ->with($response, $account);

        $this->client->createAccount($account, ['foo' => 'bar']);
    }

    public function testSetPrimaryAccount() {
        $account = Account::reference('ACCOUNT_ID');
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/primary', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toAccount')
            ->with($response, $account);

        $this->client->setPrimaryAccount($account, ['foo' => 'bar']);
    }

    public function testUpdateAccount() {
        $account = Account::reference('ACCOUNT_ID');
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromAccount')
            ->with($account)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('put')
            ->with('/v2/accounts/ACCOUNT_ID', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toAccount')
            ->with($response, $account);

        $this->client->updateAccount($account, ['foo' => 'bar']);
    }

    public function testDeleteAccount() {
        $account = Account::reference('ACCOUNT_ID');

        $this->http->expects($this->once())
            ->method('delete')
            ->with('/v2/accounts/ACCOUNT_ID', ['foo' => 'bar']);

        $this->client->deleteAccount($account, ['foo' => 'bar']);
    }

    public function testGetAddresses() {
        $account = Account::reference('ACCOUNT_ID');
        $response = $this->createMock(ResponseInterface::class);
        $expected = new ResourceCollection();

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/addresses', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toAddresses')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountAddresses($account, ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testLoadNextAddresses() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $addresses */
        $addresses = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $addresses->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toAddresses')
            ->willReturn($nextPage);
        $addresses->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextAddresses($addresses, ['foo' => 'bar']);
    }

    public function testGetAddress() {
        $account = Account::reference('ACCOUNT_ID');
        $response = $this->createMock(ResponseInterface::class);
        $expected = new Address();

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/addresses/ADDRESS_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toAddress')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountAddress($account, 'ADDRESS_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testGetAddressTransactions() {
        $address = Address::reference('ACCOUNT_ID', 'ADDRESS_ID');
        $expected = new ResourceCollection();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/addresses/ADDRESS_ID/transactions', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toTransactions')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAddressTransactions($address, ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testCreateAddress() {
        $account = Account::reference('ACCOUNT_ID');
        $address = new Address();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromAddress')
            ->with($address)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/addresses', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toAddress')
            ->with($response, $address);

        $this->client->createAccountAddress($account, $address, ['foo' => 'bar']);
    }

    public function testGetTransactions() {
        $account = Account::reference('ACCOUNT_ID');
        $response = $this->createMock(ResponseInterface::class);
        $expected = new ResourceCollection();

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/transactions', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toTransactions')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountTransactions($account, ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testLoadNextTransactions() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $addresses */
        $addresses = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $addresses->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toTransactions')
            ->willReturn($nextPage);
        $addresses->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextTransactions($addresses, ['foo' => 'bar']);
    }

    public function testGetTransaction() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new Transaction();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/transactions/TRANSACTION_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toTransaction')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountTransaction($account, 'TRANSACTION_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testCreateTransaction() {
        $account = Account::reference('ACCOUNT_ID');
        $transaction = new Transaction();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromTransaction')
            ->with($transaction)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/transactions', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toTransaction')
            ->with($response, $transaction);

        $this->client->createAccountTransaction($account, $transaction, ['foo' => 'bar']);
    }

    public function testCompleteRequestTransaction() {
        $transaction = Transaction::reference('ACCOUNT_ID', 'TRANSACTION_ID');

        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/transactions/TRANSACTION_ID/complete', ['foo' => 'bar']);

        $this->client->completeTransaction($transaction, ['foo' => 'bar']);
    }

    public function testResendRequestTransaction() {
        $transaction = Transaction::reference('ACCOUNT_ID', 'TRANSACTION_ID');

        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/transactions/TRANSACTION_ID/resend', ['foo' => 'bar']);

        $this->client->resendTransaction($transaction, ['foo' => 'bar']);
    }

    public function testCancelRequestTransaction() {
        $transaction = Transaction::reference('ACCOUNT_ID', 'TRANSACTION_ID');

        $this->http->expects($this->once())
            ->method('delete')
            ->with('/v2/accounts/ACCOUNT_ID/transactions/TRANSACTION_ID', ['foo' => 'bar']);

        $this->client->cancelTransaction($transaction, ['foo' => 'bar']);
    }

    public function testGetBuys() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new ResourceCollection();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/buys', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toBuys')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountBuys($account, ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testLoadNextBuys() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $buys */
        $buys = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $buys->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toBuys')
            ->willReturn($nextPage);
        $buys->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextBuys($buys, ['foo' => 'bar']);
    }

    public function testGetBuy() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new Buy();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/buys/BUY_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toBuy')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountBuy($account, 'BUY_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testCreateBuy() {
        $account = Account::reference('ACCOUNT_ID');
        $buy = new Buy();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromBuy')
            ->with($buy)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/buys', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toBuy')
            ->with($response, $buy);

        $this->client->createAccountBuy($account, $buy, ['foo' => 'bar']);
    }

    public function testCommitBuy() {
        $buy = Buy::reference('ACCOUNT_ID', 'BUY_ID');
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/buys/BUY_ID/commit', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toBuy')
            ->with($response, $buy);

        $this->client->commitBuy($buy, ['foo' => 'bar']);
    }

    public function testLoadNextSells() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $sells */
        $sells = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $sells->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toSells')
            ->willReturn($nextPage);
        $sells->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextSells($sells, ['foo' => 'bar']);
    }

    public function testGetSell() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new Sell();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/sells/SELL_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toSell')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountSell($account, 'SELL_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testCreateSell() {
        $account = Account::reference('ACCOUNT_ID');
        $sell = new Sell();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromSell')
            ->with($sell)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/sells', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toSell')
            ->with($response, $sell);

        $this->client->createAccountSell($account, $sell, ['foo' => 'bar']);
    }

    public function testCommitSell() {
        $sell = Sell::reference('ACCOUNT_ID', 'SELL_ID');
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/sells/SELL_ID/commit', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toSell')
            ->with($response, $sell);

        $this->client->commitSell($sell, ['foo' => 'bar']);
    }

    public function testGetDeposits() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new ResourceCollection();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/deposits', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toDeposits')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountDeposits($account, ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testLoadNextDeposits() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $deposits */
        $deposits = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $deposits->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toDeposits')
            ->willReturn($nextPage);
        $deposits->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextDeposits($deposits, ['foo' => 'bar']);
    }

    public function testGetDeposit() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new Deposit();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/deposits/DEPOSIT_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toDeposit')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountDeposit($account, 'DEPOSIT_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testCreateDeposit() {
        $account = Account::reference('ACCOUNT_ID');
        $deposit = new Deposit();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromDeposit')
            ->with($deposit)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/deposits', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toDeposit')
            ->with($response, $deposit);

        $this->client->createAccountDeposit($account, $deposit, ['foo' => 'bar']);
    }

    public function testCommitDeposit() {
        $deposit = Deposit::reference('ACCOUNT_ID', 'DEPOSIT_ID');
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/deposits/DEPOSIT_ID/commit', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toDeposit')
            ->with($response, $deposit);

        $this->client->commitDeposit($deposit, ['foo' => 'bar']);
    }

    public function testGetWithdrawals() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new ResourceCollection();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/withdrawals', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toWithdrawals')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountWithdrawals($account, ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testLoadNextWithdrawals() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $withdrawals */
        $withdrawals = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $withdrawals->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toWithdrawals')
            ->willReturn($nextPage);
        $withdrawals->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextWithdrawals($withdrawals, ['foo' => 'bar']);
    }

    public function testGetWithdrawal() {
        $account = Account::reference('ACCOUNT_ID');
        $expected = new Withdrawal();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/accounts/ACCOUNT_ID/withdrawals/WITHDRAWAL_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toWithdrawal')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getAccountWithdrawal($account, 'WITHDRAWAL_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testCreateWithdrawal() {
        $account = Account::reference('ACCOUNT_ID');
        $withdrawal = new Withdrawal();
        $response = $this->createMock(ResponseInterface::class);

        $this->mapper->expects($this->any())
            ->method('fromWithdrawal')
            ->with($withdrawal)
            ->willReturn(['key' => 'value']);
        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/withdrawals', ['key' => 'value', 'foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toWithdrawal')
            ->with($response, $withdrawal);

        $this->client->createAccountWithdrawal($account, $withdrawal, ['foo' => 'bar']);
    }

    public function testCommitWithdrawal() {
        $withdrawal = Withdrawal::reference('ACCOUNT_ID', 'WITHDRAWAL_ID');
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->once())
            ->method('post')
            ->with('/v2/accounts/ACCOUNT_ID/withdrawals/WITHDRAWAL_ID/commit', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->once())
            ->method('toWithdrawal')
            ->with($response, $withdrawal);

        $this->client->commitWithdrawal($withdrawal, ['foo' => 'bar']);
    }

    public function testGetPaymentMethods() {
        $expected = new ResourceCollection();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/payment-methods', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toPaymentMethods')
            ->willReturn($expected);

        $actual = $this->client->getPaymentMethods(['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }

    public function testLoadNextPaymentMethods() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceCollection $paymentMethods */
        $paymentMethods = $this->createMock(ResourceCollection::class);
        $response = $this->createMock(ResponseInterface::class);
        $nextPage = new ResourceCollection();

        $paymentMethods->expects($this->any())
            ->method('getNextUri')
            ->willReturn('/test/next/uri');
        $this->http->expects($this->any())
            ->method('get')
            ->with('/test/next/uri', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toPaymentMethods')
            ->willReturn($nextPage);
        $paymentMethods->expects($this->once())
            ->method('mergeNextPage')
            ->with($nextPage);

        $this->client->loadNextPaymentMethods($paymentMethods, ['foo' => 'bar']);
    }

    public function testGetPaymentMethod() {
        $expected = new PaymentMethod();
        $response = $this->createMock(ResponseInterface::class);

        $this->http->expects($this->any())
            ->method('get')
            ->with('/v2/payment-methods/PAYMENT_METHOD_ID', ['foo' => 'bar'])
            ->willReturn($response);
        $this->mapper->expects($this->any())
            ->method('toPaymentMethod')
            ->with($response)
            ->willReturn($expected);

        $actual = $this->client->getPaymentMethod('PAYMENT_METHOD_ID', ['foo' => 'bar']);
        $this->assertSame($expected, $actual);
    }
}
