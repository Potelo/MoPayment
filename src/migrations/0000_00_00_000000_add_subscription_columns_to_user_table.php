<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionColumnsToUserTable extends Migration
{
    protected $table;
    protected $column;

    public function __construct()
    {
        $model = getenv('MOIP_MODEL') ?: config('services.moip.user_model', 'App\User');
        $this->table = (new $model)->getTable();
        $this->column = getenv('MOIP_USER_MODEL_COLUMN') ?: config('services.moip.user_model_column', 'moip_id');
    }

    public function up()
    {
        Schema::table($this->table, function ($table) {
            $table->string($this->column)->nullable();
        });
    }

    public function down()
    {
        Schema::table($this->table, function ($table) {
            $table->dropColumn($this->column);
        });
    }
}