<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOldTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('old_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_id');
            $table->string('trace_id');
            $table->string('ussd_code');
            $table->string('payment_reference')->nullable();
            $table->string('phone_number');
            $table->string('amount');
            $table->string('remarks')->nullable();
            $table->string('service_name');
            $table->string('request_time');
            $table->string('response_time');
            $table->string('status')->default("unpaid");
            $table->string('platform');
            $table->string('category');

            $table->json('api_request')->nullable();
            $table->json('api_response')->nullable();
            $table->json('client_request')->nullable();
            $table->json('client_response')->nullable();
            $table->json('service_request')->nullable();
            $table->json('service_response')->nullable();
            $table->json('api_callback')->nullable();
            $table->string('date');

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
        Schema::dropIfExists('old_transactions');
    }
}
