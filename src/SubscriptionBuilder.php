<?php

namespace Potelo\MoPayment;


use Carbon\Carbon;

class SubscriptionBuilder
{
    /**
     * The user model that is subscribing.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * The name of the subscription.
     *
     * @var string
     */
    protected $name;

    /**
     * The code of subscription.
     *
     * @var string
     */
    protected $code;

    /**
     * The amount of the subscription in cents (override the plan amount).
     *
     * @var string
     */
    protected $amount;

    /**
     * The code of the plan being subscribed to.
     *
     * @var string
     */
    protected $plan_code;

    /**
     * The payment method.
     *
     * @var string
     */
    protected $payment_method;

    /**
     * The code of the coupon to be applied
     *
     * @var string
     */
    protected $coupon_code;

    /**
     * Extra fields to subscription table
     *
     * @var string
     */
    protected $table_fields;

    /**
     * Create a new subscription builder instance.
     *
     * @param  mixed  $user
     * @param  string  $subscription_name
     * @param  string  $plan_code
     * @param  string  $payment_method
     * @param  string  $amount
     * @param  string  $coupon_code
     * @param  array  $table_fields
     */
    public function __construct($user, $subscription_name, $plan_code, $payment_method, $amount = null, $coupon_code = null, $table_fields = [])
    {
        $this->user = $user;
        $this->name = $subscription_name;
        $this->code = uniqid();
        $this->plan_code = $plan_code;
        $this->payment_method = $payment_method;
        $this->amount = $amount;
        $this->coupon_code = $coupon_code;
        $this->table_fields = $table_fields;
    }

    /**
     * Create a new Moip subscription.
     *
     * @param  array|\stdClass  $options
     * @return \Potelo\MoPayment\Moip\Subscription
     */
    public function create($options = [])
    {
        $customer = $this->getMoipCustomer($options);

        $subscription_moip = $this->user->createMoipSubscription($this->buildPayload($customer->code));

        $attributes = [
            'name' => $this->name,
            'moip_plan' => $this->plan_code,
            'moip_id' => $this->code,
            'trial_ends_at' => $subscription_moip->status == 'TRIAL' ? Carbon::create($subscription_moip->trial->end->year, $subscription_moip->trial->end->month, $subscription_moip->trial->end->day) : null,
            'ends_at' => null,
        ];

        if(count($this->table_fields)) {
            $attributes = array_merge($attributes, $this->table_fields);
        }

        $subscription = $this->user->subscriptions()->create($attributes);

        return $subscription_moip;
    }

    /**
     * Get the Moip customer instance for the current user and token.
     *
     * @param  array|\stdClass  $options
     * @return \Potelo\MoPayment\Moip\Customer
     */
    protected function getMoipCustomer($options = [])
    {
        return $this->user->moip_id ? $this->user->asMoipCustomer() : $this->user->createAsMoipCustomer($options);
    }

    /**
     * Build the payload for subscription creation.
     *
     * @param $customer_code
     * @return array
     */
    protected function buildPayload($customer_code)
    {
        $payload = [
            'code' => $this->code,
            'payment_method' => $this->payment_method,
            'plan' => [
                'code' => $this->plan_code
            ],
            'customer' => [
                'code' => $customer_code
            ]
        ];

        if($this->amount) {
            $payload['amount'] = $this->amount;
        }

        if($this->coupon_code) {
            $payload['coupon'] = [
                'code' => $this->coupon_code
            ];
        } else {
            $payload['payment_method'] = $this->payment_method;
        }

        return $payload;
    }
}