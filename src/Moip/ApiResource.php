<?php

namespace Potelo\MoPayment\Moip;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Class ApiResource
 *
 */
abstract class ApiResource extends MoipObject
{
    protected static function create($params, $queryData = null)
    {
        $query = $queryData ? '?' . http_build_query($queryData) : '';

        $endpoint = Moip::getEndpoint() . static::$resourcePath . $query;

        $response = self::request('POST', $endpoint, $params);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }

    protected static function get($code)
    {
        $endpoint = Moip::getEndpoint() . static::$resourcePath . '/' . $code;

        $response = self::request('GET', $endpoint);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }

    protected static function update($code, $params)
    {
        $endpoint = Moip::getEndpoint() . static::$resourcePath . '/' . $code;

        self::request('PUT', $endpoint, $params);

        return self::convertToMoipObject($params);
    }

    protected static function all()
    {
        $endpoint = Moip::getEndpoint() . static::$resourcePath;

        $response = self::request('GET', $endpoint);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }

    protected static function request($method, $endpoint = '', $params = [])
    {
        $client = new GuzzleClient();

        $authorization = Moip::getApiAuthorization();

        $options = [
            'headers' => [
                'Authorization' => 'Basic '. $authorization
            ]
        ];

        if(!empty($params)) {
            $options['json'] = $params;
        }

        $response = $client->request($method, $endpoint, $options);

        return $response;
    }
}