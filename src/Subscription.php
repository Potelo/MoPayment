<?php

namespace Potelo\MoPayment;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Exception\LogicException;

class Subscription extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'trial_ends_at', 'ends_at',
        'created_at', 'updated_at',
    ];

    protected $moipSubscriptionModelIdColumn;

    protected $moipSubscriptionModelPlanColumn;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->table = getenv('MOPAYMENT_SIGNATURE_TABLE') ?: config('services.moip.signature_table', 'subscriptions');
        $this->moipSubscriptionModelIdColumn = getenv('MOIP_SUBSCRIPTION_MODEL_ID_COLUMN') ?: config('services.moip.subscription_model_id_column', 'moip_id');
        $this->moipSubscriptionModelPlanColumn = getenv('MOIP_SUBSCRIPTION_MODEL_PLAN_COLUMN') ?: config('services.moip.subscription_model_plan_column', 'moip_plan');
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        $model = getenv('MOIP_MODEL') ?: config('services.moip.model', 'App\\User');
        $column = getenv('MOIP_MODEL_FOREIGN_KEY') ?: config('services.moip.model_foreign_key', 'user_id');

        $model = new $model;

        if (in_array("Illuminate\Database\Eloquent\SoftDeletes", class_uses($model))) {
            return $this->belongsTo(get_class($model), $column)->withTrashed();
        }

        return $this->belongsTo(get_class($model), $column);
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return is_null($this->ends_at) || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        if (! is_null($this->trial_ends_at)) {
            return Carbon::today()->lt($this->trial_ends_at);
        } else {
            return false;
        }
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        if (! is_null($endsAt = $this->ends_at)) {
            return Carbon::now()->lt(Carbon::instance($endsAt));
        } else {
            return false;
        }
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function suspended()
    {
        return ! is_null($this->ends_at);
    }

    /**
     * Suspend the subscription at the end of the billing period.
     *
     * @return $this
     */
    public function suspend()
    {
        $subscription = $this->asMoipSubscription();

        $subscription->suspend();

        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // and make that the end of the grace period for this current user.
        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } elseif(isset($subscription->next_invoice_date) && $subscription->status == 'ACTIVE') {
            $this->ends_at = Carbon::create($subscription->next_invoice_date->year, $subscription->next_invoice_date->month, $subscription->next_invoice_date->day);
        } else {
            $this->ends_at = Carbon::now();
        }

        $this->save();

        return $this;
    }

    /**
     * Mark the subscription as cancelled.
     *
     * @return void
     */
    public function markAsCancelled()
    {
        $this->fill(['ends_at' => Carbon::now()])->save();
    }

    /**
     * Resume the suspended subscription.
     */
    public function resume()
    {
        $subscription = $this->asMoipSubscription();

        $subscription->activate();

        // Finally, we will remove the ending timestamp from the user's record in the
        // local database to indicate that the subscription is active again and is
        // no longer "cancelled". Then we will save this record in the database.
        $this->fill(['ends_at' => null])->save();

        return $this;
    }

    /**
     * Get the subscription as a Moip subscription object.
     *
     * @return \Potelo\MoPayment\Moip\Subscription
     */
    public function asMoipSubscription()
    {
        return $this->user->getMoipSubscription($this->{$this->moipSubscriptionModelIdColumn});
    }
}