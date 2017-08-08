<?php

namespace Potelo\MoPayment\Moip;


class Invoice extends ApiResource
{
    protected static $resource_path = '/invoices';

    /**
     * @param string $code The code of the invoice to retrieve
     *
     * @return \Potelo\MoPayment\Moip\Invoice
     */
    public static function get($code)
    {
        return parent::get($code);
    }

    /**
     * Get a colletion of invoices from a subscription
     *
     * @param $subscription_code
     * @return array|MoipObject
     */
    public static function search($subscription_code)
    {
        $endpoint = Moip::getEndpoint() . '/subscriptions/' . $subscription_code . '/invoices';

        $response = parent::request('GET', $endpoint);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }
}