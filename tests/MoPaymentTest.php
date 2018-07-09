<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Potelo\MoPayment\Moip\Customer as Moip_Customer;
use Potelo\MoPayment\Moip\Subscription as Moip_Subscription;


class MoPaymentTest extends TestCase
{

    protected $moipUserModelColumn;

    protected $moipSubscriptionModelIdColumn;

    protected $moipSubscriptionModelPlanColumn;

    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__.'/../.env')) {
            $dotenv = new Dotenv\Dotenv(__DIR__.'/../');
            $dotenv->load();
        }
    }

    /**
     * Schema Helpers.
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    public function setUp()
    {
        Eloquent::unguard();

        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->moipUserModelColumn = getenv('MOIP_USER_MODEL_COLUMN') ?: 'moip_id';

        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('cpf');
            $table->string($this->moipUserModelColumn)->unique()->nullable();
            $table->timestamps();
        });

        $this->moipSubscriptionModelIdColumn = getenv('MOIP_SUBSCRIPTION_MODEL_ID_COLUMN') ?: 'moip_id';
        $this->moipSubscriptionModelPlanColumn = getenv('MOIP_SUBSCRIPTION_MODEL_PLAN_COLUMN') ?: 'moip_plan';

        $this->schema()->create('subscriptions', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('user_id');
            $table->string($this->moipSubscriptionModelIdColumn)->unique();
            $table->string($this->moipSubscriptionModelPlanColumn);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function tearDown()
    {
        $this->schema()->drop('users');
        $this->schema()->drop('subscriptions');
    }

    public function testExistingUserCanSubcribe()
    {
        $this->moipUserModelColumn = getenv('MOIP_USER_MODEL_COLUMN') ?: 'moip_id';

        $userData = [
            'name' => 'Joao Assinante',
            'email' => 'joao_assinante@example.com',
            'cpf' => '01234567891',
        ];

        $user = User::create($userData);

        $customerData = [
            'fullname' => 'Joao Assinante',
            'email' => 'joao_assinante@example.com',
            'phone_area_code' => '11',
            'phone_number' => '999887766',
            'cpf' => '01234567891',
            'birthdate_day' => '2',
            'birthdate_month' => '2',
            'birthdate_year' => '2000',
            'address' => [
                'street' => 'Rua dos Pagadores',
                'number' => '1000',
                'complement' => 'Casa',
                'district' => 'Bairro dos Pagadores',
                'city' => 'São Paulo',
                'state' => 'SP',
                'country' => 'BRA',
                'zipcode' => '05015010',
            ],
            'billing_info' => [
                'credit_card' => [
                    'holder_name' => 'Joao Assinante',
                    'number' => '4111111111111111',
                    'expiration_month' => '08',
                    'expiration_year' => '20'
                ]
            ]
        ];

        $customer = $user->createAsMoipCustomer($customerData);

        $plan_code = '1500323600';

        $subscriptionMoip = $user->newSubscription('Name', $plan_code, 'CREDIT_CARD')->create();

        $subscription_retrieved = Moip_Subscription::get($subscriptionMoip->code);

        $this->assertEquals($subscriptionMoip->code, $subscription_retrieved->code);
    }

    public function testNonExistingUserCanSubcribe()
    {
        $user = User::create([
            'name' => 'Maria Assinante',
            'email' => 'maria_assinante@example.com',
            'cpf' => '01234567891'
        ]);

        $plan_code = '1500323600';

        $subscriptionMoip = $user->newSubscription('Monitoramentos', $plan_code, 'CREDIT_CARD')
            ->create([
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
                ],
                'billing_info' => [
                    'credit_card' => [
                        'holder_name' => 'Joao Com Cartao',
                        'number' => '4111111111111111',
                        'expiration_month' => '04',
                        'expiration_year' => '20',
                    ]
                ]
            ]);

        $subscription_retrieved = Moip_Subscription::get($subscriptionMoip->code);
        $this->assertEquals($subscriptionMoip->code, $subscription_retrieved->code);

        $customer_retrieved = Moip_Customer::get($user->{$this->moipUserModelColumn});
        $this->assertEquals($user->{$this->moipUserModelColumn}, $customer_retrieved->code);

        // testes usuario
        $this->assertTrue($user->subscribed('Monitoramentos'));
        $this->assertTrue($user->subscription('Monitoramentos')->onTrial());
        $this->assertFalse($user->subscription('Monitoramentos')->suspended());
        $this->assertFalse($user->subscription('Monitoramentos')->onGracePeriod());
        $this->assertTrue($user->onPlan($plan_code));

        // testes assinatura
        $this->assertTrue($user->subscription('Monitoramentos')->valid());
        $this->assertTrue($user->subscription('Monitoramentos')->active());
        $this->assertFalse($user->subscription('Monitoramentos')->suspended());
        $this->assertTrue($user->subscription('Monitoramentos')->onTrial());
        $this->assertFalse($user->subscription('Monitoramentos')->onGracePeriod());

        // cancelar assinatura
        $user->subscription('Monitoramentos')->suspend();
        $this->assertTrue($user->subscription('Monitoramentos')->suspended());

        // reativar assinatura
        $user->subscription('Monitoramentos')->resume();
        $this->assertTrue($user->subscription('Monitoramentos')->active());
        $this->assertFalse($user->subscription('Monitoramentos')->suspended());
    }
}

class User extends \Illuminate\Database\Eloquent\Model
{
    use \Potelo\MoPayment\MoPaymentTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable;

    public function __construct(array $attributes = [])
    {
        $moipUserModelColumn = getenv('MOIP_USER_MODEL_COLUMN') ?: 'moip_id';

        $this->fillable = ['name', $moipUserModelColumn];

        parent::__construct($attributes);
    }
}