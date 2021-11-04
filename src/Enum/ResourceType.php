<?php

namespace Coinbase\Wallet\Enum;

/**
 * Supported resource types.
 */
class ResourceType {
    const ACCOUNT = 'account';
    const ADDRESS = 'address';
    const APPLICATION = 'application';
    const BUY = 'buy';
    const DEPOSIT = 'deposit';
    const EMAIL = 'email';
    const PAYMENT_METHOD = 'payment_method';
    const SELL = 'sell';
    const TRANSACTION = 'transaction';
    const USER = 'user';
    const WITHDRAWAL = 'withdrawal';
    const NOTIFICATION = 'notification';
    const BITCOIN_ADDRESS = 'bitcoin_address';
    const BITCOIN_NETWORK = 'bitcoin_network';
    const BITCOIN_CASH_ADDRESS = 'bitcoin_cash_address';
    const BITCOIN_CASH_NETWORK = 'bitcoin_cash_network';
    const LITECOIN_ADDRESS = 'litecoin_address';
    const LITECOIN_NETWORK = 'litecoin_network';
    const ETHEREUM_ADDRESS = 'ethereum_address';
    const ETHEREUM_NETWORK = 'ethereum_network';
    const USD_COIN_ADDRESS = 'usd_coin_address';
    const USD_COIN_NETWORK = 'usd_coin_network';
    const EOSIO_ADDRESS = 'eos_address';
    const EOSIO_NETWORK = 'eos_network';
    const RIPPLE_ADDRESS = 'xrp_address';
    const RIPPLE_NETWORK = 'xrp_network';
    const ZRX_ADDRESS = '0x_address';
    const ZRX_NETWORK = '0x_network';
}
