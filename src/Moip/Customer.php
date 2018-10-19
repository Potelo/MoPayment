<?php

namespace Potelo\MoPayment\Moip;


class Customer extends ApiResource
{
    protected static $resourcePath = '/customers';

    /**
     * @param array $params
     * @param array $queryData
     *
     * @return \Potelo\MoPayment\Moip\Customer
     */
    public static function create($params, $queryData = null)
    {
        $queryData = [
            'new_vault' => key_exists('billing_info', $params) ? 'true' : 'false'
        ];

        $query = $queryData ? '?' . http_build_query($queryData) : '';

        $endpoint = Moip::getEndpoint() . static::$resourcePath . $query;

        self::request('POST', $endpoint, $params);

        return self::convertToMoipObject($params);
    }

    /**
     * @param string $code The code of the customer to retrieve
     *
     * @return \Potelo\MoPayment\Moip\Customer
     */
    public static function get($code)
    {
        return parent::get($code);
    }

    public static function update($code, $params)
    {
        return parent::update($code, $params);
    }

    public static function all()
    {
        return parent::all();
    }

    public static function updateCard($code, $params)
    {
        $endpoint = Moip::getEndpoint() . static::$resourcePath . '/' . $code . '/billing_infos';

        parent::request('PUT', $endpoint, $params);

        return self::convertToMoipObject($params);
    }
}