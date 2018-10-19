<?php

namespace Potelo\MoPayment\Moip;


class Invoice extends ApiResource
{
    protected static $resourcePath = '/invoices';

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
     * @param $subscriptionCode
     * @return array|MoipObject
     */
    public static function search($subscriptionCode)
    {
        $endpoint = Moip::getEndpoint() . '/subscriptions/' . $subscriptionCode . '/invoices';

        $response = parent::request('GET', $endpoint);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }
}