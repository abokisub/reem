<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_checkout_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('merchant_order_id', 32)->unique(); // our generated order ID sent to PalmPay
            $table->string('palmpay_order_no', 64)->nullable(); // PalmPay's order number
            $table->string('reference')->nullable();           // merchant's own reference (optional)
            $table->unsignedBigInteger('amount');              // in kobo/cents
            $table->string('currency', 10)->default('NGN');
            $table->tinyInteger('order_status')->default(0);   // 0=unpaid,1=paying,2=success,3=fail,4=close
            $table->string('checkout_url', 500)->nullable();
            $table->string('notify_url', 300)->nullable();
            $table->string('callback_url', 300)->nullable();
            $table->json('customer_info')->nullable();
            $table->json('palmpay_response')->nullable();
            $table->string('error_msg', 300)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'order_status']);
            $table->index('merchant_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_checkout_orders');
    }
};
