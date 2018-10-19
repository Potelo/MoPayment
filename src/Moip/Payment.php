<?php

namespace Potelo\MoPayment\Moip;


class Payment extends ApiResource
{
    protected static $resourcePath = '/payments';

    /**
     * @param string $code The code of the payment to retrieve
     *
     * @return \Potelo\MoPayment\Moip\Invoice
     */
    public static function get($code)
    {
        return parent::get($code);
    }

    /**
     * Get a colletion of payments from a invoice
     *
     * @param $invoiceCode
     * @return array|MoipObject
     */
    public static function search($invoiceCode)
    {
        $endpoint = Moip::getEndpoint() . '/invoices/' . $invoiceCode . '/payments';

        $response = parent::request('GET', $endpoint);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }
}