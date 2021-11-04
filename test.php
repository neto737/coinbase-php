<?php

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Enum\Param;
use Coinbase\Wallet\Exception\HttpException;
use Coinbase\Wallet\Exception\TwoFactorRequiredException;
use Coinbase\Wallet\Resource\Transaction;

require 'vendor/autoload.php';

echo '<pre>';

$configuration = Configuration::apiKey('AT1X1vbCt2YW7nG7', 'MMfe9DgxaSDnqRmGYr95Si7J4WdyRVry');
$client = Client::create($configuration);

var_dump($client->getPaymentMethods());
exit;

$account = $client->getPrimaryAccount();

$transactions = $client->getAccountTransactions($account, [
    Param::FETCH_ALL => true,
]);

foreach ($transactions as $row) {
    var_dump($row->getRawData()) . PHP_EOL;
    echo '<hr>';
}

exit;

$transaction = Transaction::send([
    'toEmail' => 'tynhomello@gmail.com',
    'bitcoinAmount' => '0.000001'
]);



try {
    try {
        $client->createAccountTransaction($account, $transaction);
    } catch (HttpException $e) {
        var_dump($e->getMessage());
    }
} catch (TwoFactorRequiredException $e) {
    // show 2FA dialog to user and collect 2FA token

    // retry call with token
    $client->createAccountTransaction($account, $transaction, [
        Param::TWO_FACTOR_TOKEN => '123456',
    ]);
}

//var_dump($transaction);