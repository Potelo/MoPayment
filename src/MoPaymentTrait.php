<?php

namespace Potelo\MoPayment;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use Potelo\MoPayment\Moip\Customer;
use Potelo\MoPayment\Moip\Moip;
use Potelo\MoPayment\Moip\Subscription as Moip_Subscription;
use Potelo\MoPayment\Moip\Invoice as Moip_Invoice;

trait MoPaymentTrait
{
    /**
     * The Moip API token.
     *
     * @var string
     */

    protected static $api_token;

    /**
     * The Moip API key.
     *
     * @var string
     */
    protected static $api_key;

    /**
     * Begin creating a new subscription.
     *
     * @param string  $subscription_name
     * @param string  $plan_code
     * @param string  $payment_mode
     * @param string  $amount
     * @param string  $coupon_code
     * @return \Potelo\MoPayment\SubscriptionBuilder
     */
    public function newSubscription($subscription_name, $plan_code, $payment_mode, $amount = null, $coupon_code = null)
    {
        return new SubscriptionBuilder($this, $subscription_name, $plan_code, $payment_mode, $amount, $coupon_code);
    }

    /**
     * Create a Moip customer for the given Moip model.
     *
     * @param  array|\stdClass  $options
     * @return \Potelo\MoPayment\Moip\Customer
     */
    public function createAsMoipCustomer($options)
    {
        Moip::init($this->getApiToken(), $this->getApiKey(), $this->getEndpointEnvironment());

        $options['code'] = uniqid();

        $customer = Customer::create($options);

        $this->moip_id = $customer->code;

        $this->save();

        return $customer;
    }

    /**
     * Get the Moip customer for the Moip model.
     *
     * @return \Potelo\MoPayment\Moip\Customer
     */
    public function asMoipCustomer()
    {
        Moip::init($this->getApiToken(), $this->getApiKey(), $this->getEndpointEnvironment());

        try {
            return Customer::get($this->moip_id);
        } catch (RequestException $e) {
            if($e->getCode() == 404) {
                return null;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Get the Moip API key.
     *
     * @return string
     */
    public static function getApiKey()
    {
        if (static::$api_key = getenv('MOIP_APIKEY')) {
            return static::$api_key;
        }

        return config('services.moip.key');
    }

    /**
     * Get the Moip API token.
     *
     * @return mixed|string
     */
    public static function getApiToken()
    {
        if (static::$api_token = getenv('MOIP_APITOKEN')) {
            return static::$api_token;
        }

        return config('services.moip.token');
    }

    /**
     * Get the Moip API environment.
     *
     * @return mixed|string
     */
    public static function getEndpointEnvironment()
    {
        if ($env = getenv('MOIP_ENV')) {
            return $env;
        }

        return config('services.moip.env');
    }

    /**
     * @param $params
     * @return \Potelo\MoPayment\Moip\Subscription
     */
    public function createMoipSubscription($params)
    {
        Moip::init($this->getApiToken(), $this->getApiKey(), $this->getEndpointEnvironment());

        return Moip_Subscription::create($params);
    }

    /**
     * Get all of the subscriptions for the Moip model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Get a subscription instance by name.
     *
     * @param  string  $subscription
     * @return \Potelo\MoPayment\Subscription|null
     */
    public function subscription($subscription = 'default')
    {
        return $this->subscriptions->sortByDesc(function ($value) {
            return $value->created_at->getTimestamp();
        })->where('name', $subscription)->first();
    }

    /**
     * Determine if the user has a given subscription.
     *
     * @param  string  $subscription
     * @param  string|null  $plan
     * @return bool
     */
    public function subscribed($subscription = 'default', $plan = null)
    {
        $subscription = $this->subscription($subscription);

        if (is_null($subscription)) {
            return false;
        }

        if (is_null($plan)) {
            return $subscription->valid();
        }

        return $subscription->valid() &&
            $subscription->moip_plan === $plan;
    }

    /**
     * Determine if the entity is on the given plan.
     *
     * @param  string  $plan_code
     * @return bool
     */
    public function onPlan($plan_code)
    {
        $onPlan = $this->subscriptions->where('moip_plan', $plan_code)->first();

        return ! is_null($onPlan);
    }

    /**
     * Get a Moip subscription
     *
     * @param $subscription_code
     * @return \Potelo\MoPayment\Moip\Subscription
     */
    public function getMoipSubscription($subscription_code)
    {
        Moip::init($this->getApiToken(), $this->getApiKey(), $this->getEndpointEnvironment());

        return Moip_Subscription::get($subscription_code);
    }

    /**
     * Get a collection of the entity's invoices.
     *
     * @param $subscription_code
     * @param $include_pending
     * @return \Illuminate\Support\Collection
     */
    public function invoices($subscription_code, $include_pending = false)
    {
        $invoices = [];

        Moip::init($this->getApiToken(), $this->getApiKey(), $this->getEndpointEnvironment());

        $moip_invoices = Moip_Invoice::search($subscription_code);

        // Here we will loop through the Moip invoices and create our own custom Invoice
        // instances that have more helper methods and are generally more convenient to
        // work with than the plain Moip objects are. Then, we'll return the array.
        foreach ($moip_invoices as $invoice) {
            if ($invoice->status->description == 'Pago' || $include_pending) {
                $invoices[] = new Invoice($invoice);
            }
        }

        return new Collection($invoices);
    }

    /**
     * Find an invoice by ID.
     *
     * @param  string  $id
     * @return \Potelo\MoPayment\Invoice|null
     */
    public function findInvoice($id)
    {
        Moip::init($this->getApiToken(), $this->getApiKey(), $this->getEndpointEnvironment());

        try {
            return new Invoice(Moip_Invoice::get($id));
        } catch (\Exception $e) {
            //
        }

        return null;
    }

    /**
     * Update customer credit card.
     *
     * @param string $params
     */
    public function updateCard($params)
    {
        $customer = $this->asMoipCustomer();

        $customer->updateCard($this->moip_id, $params);
    }
}