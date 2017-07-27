<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Potelo\MoPayment\Moip\Customer as Moip_Customer;
use Potelo\MoPayment\Moip\Subscription as Moip_Subscription;


class MoPaymentTest extends TestCase
{
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

        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('cpf');
            $table->string('moip_id')->unique();
            $table->timestamps();
        });

        $this->schema()->create('subscriptions', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('user_id');
            $table->string('moip_id')->unique();
            $table->string('moip_plan');
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
        $user = User::create([
            'name' => 'Joao Assinante',
            'email' => 'joao_assinante@example.com',
            'cpf' => '01234567891',
            'moip_id' => 'some_existing_code',
        ]);

        $plan_code = '00000';

        $subscription = $user->newSubscription('Name', $plan_code, 'CREDIT_CARD')->create();

        $subscription_retrieved = Moip_Subscription::get($subscription->moip_id);

        $this->assertEquals($subscription->moip_id, $subscription_retrieved->code);
    }

    public function testNonExistingUserCanSubcribe()
    {
        $user = User::create([
            'name' => 'Maria Assinante',
            'email' => 'maria_assinante@example.com',
            'cpf' => '01234567891',
            'moip_id' => uniqid(),
        ]);

        $plan_code = '00000';

        $subscription = $user->newSubscription('Monitoramentos', $plan_code, 'CREDIT_CARD')
            ->create([
                'code' => $user->moip_id,
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
                        'expiration_year' => '18',
                    ]
                ]
            ]);

        $subscription_retrieved = Moip_Subscription::get($subscription->moip_id);
        $this->assertEquals($subscription->moip_id, $subscription_retrieved->code);

        $customer_retrieved = Moip_Customer::get($user->moip_id);
        $this->assertEquals($user->moip_id, $customer_retrieved->code);

        // testes usuario
        $this->assertTrue($user->subscribed('Monitoramentos'));
        $this->assertTrue($user->subscription('Monitoramentos')->onTrial());
        $this->assertFalse($user->subscription('Monitoramentos')->suspended());
        $this->assertFalse($user->subscription('Monitoramentos')->onGracePeriod());
        $this->assertTrue($user->onPlan($plan_code));

        // testes assinatura
        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->suspended());
        $this->assertTrue($subscription->onTrial());
        $this->assertFalse($subscription->onGracePeriod());

        // cancelar assinatura
        $subscription->suspend();
        $this->assertTrue($subscription->suspended());

        // reativar assinatura
        $subscription->resume();
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->suspended());
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
    protected $fillable = ['name', 'moip_id'];
}