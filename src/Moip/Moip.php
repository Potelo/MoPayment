<?php

namespace Potelo\MoPayment\Moip;

/**
 * Class Moip.
 */
abstract class Moip
{
    /**
     * Version of API.
     *
     * @const string
     */
    const VERSION = 'v1';

    /**
     * endpoint of production.
     *
     * @const string
     */
    const ENDPOINT_PRODUCTION = 'https://api.moip.com.br/assinaturas/';

    /**
     * endpoint of sandbox.
     *
     * @const string
     */
    const ENDPOINT_SANDBOX = 'https://sandbox.moip.com.br/assinaturas/';

    private static $endpoint = null;

    private static $apiToken = null;
    private static $apiKey = null;

    /**
     * Authorization hash
     *
     * @var
     */
    private static $api_authorization;

    public static function setApiToken($apiToken)
    {
        self::$apiKey = $apiToken;
    }

    public static function getApiToken()
    {
        return self::$apiToken;
    }

    public static function setapiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    public static function getapiKey()
    {
        return self::$apiKey;
    }

    public static function setApiAuthorization($apiToken, $apiKey)
    {
        self::$apiToken = $apiToken;
        self::$apiKey = $apiKey;
        self::$api_authorization = base64_encode($apiToken.':'.$apiKey);
    }

    public static function getApiAuthorization()
    {
        return self::$api_authorization;
    }

    public static function setEndpoint($env)
    {
        if( $env == 'production' ) {
            self::$endpoint = self::ENDPOINT_PRODUCTION;
        }

        if( $env == 'sandbox' ) {
            self::$endpoint = self::ENDPOINT_SANDBOX;
        }
    }

    public static function getEndpoint()
    {
        return self::$endpoint;
    }

    public static function init($apiToken, $apiKey, $env = 'production')
    {
        if( $env == 'production' ) {
            self::$endpoint = self::ENDPOINT_PRODUCTION . self::VERSION;
        }

        if( $env == 'sandbox' ) {
            self::$endpoint = self::ENDPOINT_SANDBOX . self::VERSION;
        }

        self::$apiToken = $apiToken;
        self::$apiKey = $apiKey;
        self::$api_authorization = base64_encode(self::$apiToken.':'.self::$apiKey);
    }
}