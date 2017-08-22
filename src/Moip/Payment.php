<?php

namespace Potelo\MoPayment\Moip;


class Payment extends ApiResource
{
    protected static $resource_path = '/payments';

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
     * @param $invoice_code
     * @return array|MoipObject
     */
    public static function search($invoice_code)
    {
        $endpoint = Moip::getEndpoint() . '/invoices/' . $invoice_code . '/payments';

        $response = parent::request('GET', $endpoint);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }
}