<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('palmpay_webhooks', function (Blueprint $table) {
            // Add extracted fields from JSON payload for easier querying
            $table->string('order_no')->nullable()->after('event_type');
            $table->decimal('order_amount', 15, 2)->nullable()->after('order_no');
            $table->string('account_reference')->nullable()->after('order_amount');
            
            // Add index for order_no
            $table->index('order_no');
        });

        // Extract data from existing records
        $webhooks = DB::table('palmpay_webhooks')->get();
        
        foreach ($webhooks as $webhook) {
            $payload = json_decode($webhook->payload, true);
            
            if ($payload) {
                DB::table('palmpay_webhooks')
                    ->where('id', $webhook->id)
                    ->update([
                        'order_no' => $payload['orderNo'] ?? null,
                        'order_amount' => isset($payload['orderAmount']) ? $payload['orderAmount'] / 100 : null, // Convert from kobo to naira
                        'account_reference' => $payload['accountReference'] ?? null,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('palmpay_webhooks', function (Blueprint $table) {
            $table->dropIndex(['order_no']);
            $table->dropColumn(['order_no', 'order_amount', 'account_reference']);
        });
    }
};
