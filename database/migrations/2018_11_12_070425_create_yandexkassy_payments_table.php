<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYandexkassyPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yandexkassy_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->char('payment_id')->index();
            $table->integer('order_id')->index();
            $table->float('amount');
            $table->char('status')->default('pending');
            $table->char('idempotenceKey');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('yandexkassy_payments');
    }
}
