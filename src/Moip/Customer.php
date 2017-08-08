<?php

namespace Potelo\MoPayment\Moip;


class Customer extends ApiResource
{
    protected static $resource_path = '/customers';

    /**
     * @param array $params
     * @param array $query_data
     *
     * @return \Potelo\MoPayment\Moip\Customer
     */
    public static function create($params, $query_data = null)
    {
        $query_data = [
            'new_vault' => key_exists('billing_info', $params) ? 'true' : 'false'
        ];

        $query = $query_data ? '?' . http_build_query($query_data) : '';

        $endpoint = Moip::getEndpoint() . static::$resource_path . $query;

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
        $endpoint = Moip::getEndpoint() . static::$resource_path . '/' . $code . '/billing_infos';

        parent::request('PUT', $endpoint, $params);

        return self::convertToMoipObject($params);
    }
}