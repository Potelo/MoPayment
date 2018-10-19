<?php

namespace Potelo\MoPayment\Http\Controllers;

use Illuminate\Http\Request;
use Potelo\MoPayment\Subscription;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    protected $moipSubscriptionModelIdColumn;

    public function __construct()
    {
        $this->moipSubscriptionModelIdColumn = getenv('MOIP_SUBSCRIPTION_MODEL_ID_COLUMN') ?: config('services.moip.subscription_model_id_column', 'moip_id');
    }

    /**
     * Handle a Moip webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        $method = 'handle'.studly_case(str_replace('.', '_', $payload['event']));

        if (method_exists($this, $method)) {
            return $this->{$method}($payload);
        } else {
            return $this->missingMethod();
        }
    }

    /**
     * Handle a suspended customer from a Moip subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleSubscriptionSuspended(array $payload)
    {
        $subscription = Subscription::where($this->moipSubscriptionModelIdColumn, $payload['resource']['code'])->first();

        $subscription->markAsCancelled();

        return new Response('Webhook Handled', 200);
    }
}