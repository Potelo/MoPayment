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

    private static $api_token = null;
    private static $api_key = null;

    /**
     * Authorization hash
     *
     * @var
     */
    private static $api_authorization;

    public static function setApiToken( $api_token )
    {
        self::$api_key = $api_token;
    }

    public static function getApiToken()
    {
        return self::$api_token;
    }

    public static function setApiKey( $api_key )
    {
        self::$api_key = $api_key;
    }

    public static function getApiKey()
    {
        return self::$api_key;
    }

    public static function setApiAuthorization( $api_token, $api_key )
    {
        self::$api_key = $api_token;
        self::$api_key = $api_key;
        self::$api_authorization = base64_encode($api_token.':'.$api_key);
    }

    public static function getApiAuthorization()
    {
        return self::$api_authorization;
    }

    public static function setEndpoint( $env )
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

    public static function init($api_token, $api_key, $env = 'production')
    {
        if( $env == 'production' ) {
            self::$endpoint = self::ENDPOINT_PRODUCTION . self::VERSION;
        }

        if( $env == 'sandbox' ) {
            self::$endpoint = self::ENDPOINT_SANDBOX . self::VERSION;
        }

        self::$api_token = $api_token;
        self::$api_key = $api_key;
        self::$api_authorization = base64_encode(self::$api_token.':'.self::$api_key);
    }
}