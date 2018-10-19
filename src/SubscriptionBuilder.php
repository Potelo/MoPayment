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
    protected $plan;

    /**
     * The payment method.
     *
     * @var string
     */
    protected $paymentMethod;

    /**
     * The code of the coupon to be applied
     *
     * @var string
     */
    protected $coupon;

    /**
     * Extra fields to subscription table
     *
     * @var array
     */
    protected $extraTableFields = [];

    /**
     * Create a new subscription builder instance.
     *
     * @param  mixed  $user
     * @param  string  $subscriptionName
     * @param  string  $plan
     * @param  string  $paymentMethod
     */
    public function __construct($user, $subscriptionName, $plan, $paymentMethod)
    {
        $this->user = $user;
        $this->name = $subscriptionName;
        $this->code = uniqid();
        $this->plan = $plan;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Create a new Moip subscription.
     *
     * @param  array|\stdClass  $options
     * @return \Potelo\MoPayment\Moip\Subscription
     */
    public function create($options = [])
    {
        $moipSubscriptionModelIdColumn = getenv('MOIP_SUBSCRIPTION_MODEL_ID_COLUMN') ?: config('services.moip.subscription_model_id_column', 'moip_id');
        $moipSubscriptionModelPlanColumn = getenv('MOIP_SUBSCRIPTION_MODEL_PLAN_COLUMN') ?: config('services.moip.subscription_model_plan_column', 'moip_plan');

        $customer = $this->getMoipCustomer($options);

        $subscription_moip = $this->user->createMoipSubscription($this->buildPayload($customer->code));

        $attributes = [
            'name' => $this->name,
            $moipSubscriptionModelPlanColumn => $this->plan,
            $moipSubscriptionModelIdColumn => $this->code,
            'trial_ends_at' => $subscription_moip->status == 'TRIAL' ? Carbon::create($subscription_moip->trial->end->year, $subscription_moip->trial->end->month, $subscription_moip->trial->end->day) : null,
            'ends_at' => null,
        ];

        if(count($this->extraTableFields)) {
            $attributes = array_merge($attributes, $this->extraTableFields);
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
        if ($this->user->getMoipUserId()) {
            $user = $this->user->asMoipCustomer();

            if (array_key_exists('billing_info', $options)) {
                $user->updateCard($this->user->getMoipUserId(), $options['billing_info']);
            }

            return $user;
        }

        return $this->user->createAsMoipCustomer($options);
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
            'payment_method' => $this->paymentMethod,
            'plan' => [
                'code' => $this->plan
            ],
            'customer' => [
                'code' => $customer_code
            ]
        ];

        if ($this->amount) {
            $payload['amount'] = $this->amount;
        }

        if ($this->coupon) {
            $payload['coupon'] = [
                'code' => $this->coupon
            ];
        }

        return $payload;
    }
}