<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionTable extends Migration
{
    protected $table;
    protected $moipSubscriptionModelIdColumn;
    protected $moipSubscriptionModelPlanColumn;

    public function __construct()
    {
        $this->table = getenv('MOPAYMENT_SIGNATURE_TABLE') ?: config('services.moip.signature_table', 'subscriptions');
        $this->moipSubscriptionModelIdColumn = getenv('MOIP_SUBSCRIPTION_MODEL_ID_COLUMN') ?: config('services.moip.subscription_model_id_column', 'moip_id');
        $this->moipSubscriptionModelPlanColumn = getenv('MOIP_SUBSCRIPTION_MODEL_PLAN_COLUMN') ?: config('services.moip.subscription_model_plan_column', 'moip_plan');
    }

    public function up()
    {
        Schema::create($this->table, function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
            $table->string($this->moipSubscriptionModelIdColumn);
            $table->string($this->moipSubscriptionModelPlanColumn);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop($this->table);
    }
}