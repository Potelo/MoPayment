<?php

use PHPUnit\Framework\TestCase;
use Potelo\MoPayment\Moip\Moip;
use Potelo\MoPayment\Moip\Subscription;
use Potelo\MoPayment\Moip\Customer;

class MoipSubscriptionTest extends TestCase
{
    protected $api_token;
    protected $api_key;
    protected $env;

    protected function setUp()
    {
        $this->api_token = '';
        $this->api_key = '';
        $this->env = 'sandbox';
    }

    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__.'/../.env')) {
            $dotenv = new Dotenv\Dotenv(__DIR__.'/../');
            $dotenv->load();
        }
    }

    public function customerParams()
    {
        $customer_code = uniqid();

        $params = [
            'code' => $customer_code,
            'fullname' => 'Joao Assinante',
            'email' => 'joaoassinante@example.com',
            'cpf' => '22222222222',
            'phone_area_code' => '11',
            'phone_number' => '934343434',
            'birthdate_day' => '30',
            'birthdate_month' => '12',
            'birthdate_year' => '1988',
            'address' => [
                'street' => 'Rua dos Valentes',
                'number' => 100,
                'complement' => 'Casa',
                'district' => 'Bairro',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'country' => 'BRA',
                'zipcode' => '00000000',
            ]
        ];

        return $params;
    }

    public function subscriptionParams()
    {
        $subscription_code = uniqid();

        $params = [
            'code' => $subscription_code,
            'payment_method' => 'CREDIT_CARD',
            'plan' => [
                'code' => '0000'
            ]
        ];

        return $params;
    }

    public function testSubscriptionWithNewCustomerCanBeCreated()
    {
        $params = $this->subscriptionParams();

        $params_customer = $this->customerParams();

        $params_customer['billing_info'] = [
            'credit_card' => [
                'holder_name' => 'Joao Com Cartao',
                'number' => '4111111111111111',
                'expiration_month' => '04',
                'expiration_year' => '18',
            ]
        ];

        $params['customer'] = $params_customer;

        Moip::init($this->api_token, $this->api_key, $this->env);

        $subscription = Subscription::create($params);

        $this->assertInstanceOf(\Potelo\MoPayment\Moip\Subscription::class, $subscription);

        $subscription_retrieved = Subscription::get($params['code']);

        $this->assertEquals($params['code'], $subscription_retrieved->code);
    }

    public function testSubscriptionWithExistingCustomerCanBeCreated()
    {
        Moip::init($this->api_token, $this->api_key, $this->env);

        $params = $this->customerParams();
        $customer = Customer::create($params);

        $params = $this->subscriptionParams();

        $params['customer'] = [
            'code' => $customer->code
        ];

        $subscription = Subscription::create($params);

        $this->assertInstanceOf(\Potelo\MoPayment\Moip\Subscription::class, $subscription);
    }

    public function testCanGetCustomersList()
    {
        Moip::init($this->api_token, $this->api_key, $this->env);

        $subscriptions = Subscription::all();

        $this->assertContainsOnlyInstancesOf(\Potelo\MoPayment\Moip\Subscription::class, $subscriptions);
    }
}