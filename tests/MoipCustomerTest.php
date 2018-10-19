<?php

use PHPUnit\Framework\TestCase;
use Potelo\MoPayment\Moip\Moip;
use Potelo\MoPayment\Moip\Customer;

class MoipCustomerTest extends TestCase
{
    protected $apiToken;
    protected $apiKey;
    protected $env;

    protected function setUp()
    {
        $this->apiToken = '';
        $this->apiKey = '';
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

    public function testCustomerCanBeCreated()
    {
        $params = $this->customerParams();

        Moip::init($this->apiToken, $this->apiKey, $this->env);

        $customer = Customer::create($params);

        $this->assertInstanceOf(\Potelo\MoPayment\Moip\Customer::class, $customer);
    }


    public function testCustomerWithCardCanBeCreated()
    {
        $params = $this->customerParams();

        $params['billing_info'] = [
            'credit_card' => [
                'holder_name' => 'Joao Com Cartao',
                'number' => '4111111111111111',
                'expiration_month' => '04',
                'expiration_year' => '18',
            ]
        ];

        Moip::init($this->apiToken, $this->apiKey, $this->env);

        $customer = Customer::create($params);

        $this->assertInstanceOf(\Potelo\MoPayment\Moip\Customer::class, $customer);

        // Test retrieve customer from Moip API
        $customer = Customer::get($params['code']);
        $this->assertEquals($params['code'], $customer->code);
    }

    public function testCantGetCustomer()
    {
        $params = $this->customerParams();

        Moip::init($this->apiToken, $this->apiKey, $this->env);

        $customer = Customer::create($params);

        $customer = Customer::get($params['code']);
        $this->assertEquals($params['code'], $customer->code);
    }

    public function testCanGetCustomersList()
    {
        Moip::init($this->apiToken, $this->apiKey, $this->env);

        $customers = Customer::all();

        $this->assertContainsOnlyInstancesOf(\Potelo\MoPayment\Moip\Customer::class, $customers);
    }

    public function testCustomerCanBeUpdated()
    {
        $params = $this->customerParams();

        Moip::init($this->apiToken, $this->apiKey, $this->env);

        $customer = Customer::create($params);

        $params['fullname'] = 'Joao Com Cartao';

        $customer = Customer::update($params['code'], $params);

        $customer = Customer::get($params['code']);

        $this->assertEquals('Joao Com Cartao', $customer->fullname);
    }

    public function testCanUpdateCustomerCard()
    {
        $params = $this->customerParams();

        Moip::init($this->apiToken, $this->apiKey, $this->env);

        $customer = Customer::create($params);

        $paramsCreditCard = [
            'credit_card' => [
                'holder_name' => 'Joao Com Cartao',
                'number' => '4111111111111111',
                'expiration_month' => '04',
                'expiration_year' => '18',
            ]
        ];

        $customer = Customer::updateCard($params['code'], $paramsCreditCard);

        $this->assertInstanceOf(\Potelo\MoPayment\Moip\Customer::class, $customer);
    }

    public function testFetchNonexistentCustomer()
    {
        $this->expectExceptionCode(404);

        Moip::init($this->apiToken, $this->apiKey, $this->env);

        $customer = Customer::get('non_existent_code');
    }
}