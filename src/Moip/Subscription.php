<?php

namespace Potelo\MoPayment\Moip;


class Subscription extends ApiResource
{
    protected static $resource_path = '/subscriptions';

    /**
     * @param array $params
     * @param array $query_data
     *
     * @return \Potelo\MoPayment\Moip\Subscription
     */
    public static function create($params, $query_data = null)
    {
        $new_customer = !(count($params['customer']) == 1 && key_exists('code', $params['customer'])) ? 'true' : 'false';

        $query_data = [
            'new_customer' => $new_customer
        ];

        return parent::create($params, $query_data);
    }

    /**
     * @param string $code The code of the subscription to retrieve
     *
     * @return \Potelo\MoPayment\Moip\Subscription
     */
    public static function get($code)
    {
        return parent::get($code);
    }

    public static function all()
    {
        return parent::all();
    }

    public function suspend()
    {
        $endpoint = Moip::getEndpoint() . static::$resource_path . '/' . $this->code . '/suspend';

        $params['code'] = $this->code;

        parent::request('PUT', $endpoint, $params);

        return true;
    }

    public function activate()
    {
        $endpoint = Moip::getEndpoint() . static::$resource_path . '/' . $this->code . '/activate';

        $params['code'] = $this->code;

        parent::request('PUT', $endpoint, $params);

        return true;
    }

    /**
     * Get a colletion of invoices
     */
    public function invoices()
    {
        $endpoint = Moip::getEndpoint() . static::$resource_path . '/' . $this->code . '/invoices';

        $response = parent::request('GET', $endpoint);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }
}