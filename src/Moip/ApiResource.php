<?php

namespace Potelo\MoPayment\Moip;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

/**
 * Class ApiResource
 *
 */
abstract class ApiResource extends MoipObject
{
    protected static function create($params, $query_data = null)
    {
        $endpoint_path = $query_data ? '?' . http_build_query($query_data) : '';

        $response = self::request('POST', $endpoint_path, $params);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }

    protected static function get($code)
    {
        $endpoint_path = '/' . $code;

        $response = self::request('GET', $endpoint_path);

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }

    protected static function update($code, $params)
    {
        $endpoint_path = '/' . $code;

        self::request('PUT', $endpoint_path, $params);

        return self::convertToMoipObject($params);
    }

    protected static function all()
    {
        $response = self::request('GET');

        $response = json_decode($response->getBody()->getContents());

        return self::convertToMoipObject($response);
    }

    protected static function request($method, $endpoint_path = '', $params = [])
    {
        $client = new GuzzleClient();

        $endpoint = Moip::getEndpoint() . static::$endpoint_path . $endpoint_path;
        $authorization = Moip::getApiAuthorization();

        $options = [
            'headers' => [
                'Authorization' => 'Basic '. $authorization
            ]
        ];

        if(!empty($params)) {
            $options['json'] = $params;
        }

        try {
            $response = $client->request($method, $endpoint, $options);
        } catch (RequestException $e) {
            if($e->getCode() == 404) {
                throw $e;
            }

            throw new \Exception($e->getResponse()->getBody()->getContents());
        }

        return $response;
    }
}