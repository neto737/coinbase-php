<?php

namespace Coinbase\Wallet\ActiveRecord;

trait CheckoutActiveRecord
{
    use BaseActiveRecord;

    /**
     * Issues a refresh request to the API.
     */
    public function refresh(array $params = [])
    {
        $this->getClient()->refreshCheckout($this, $params);
    }
}
