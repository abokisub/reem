<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardCheckoutOrder extends Model
{
    protected $fillable = [
        'company_id',
        'merchant_order_id',
        'palmpay_order_no',
        'reference',
        'amount',
        'currency',
        'order_status',
        'checkout_url',
        'notify_url',
        'callback_url',
        'customer_info',
        'palmpay_response',
        'error_msg',
        'completed_at',
    ];

    protected $casts = [
        'customer_info'    => 'array',
        'palmpay_response' => 'array',
        'completed_at'     => 'datetime',
    ];

    // Order status constants
    const STATUS_UNPAID  = 0;
    const STATUS_PAYING  = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL    = 3;
    const STATUS_CLOSED  = 4;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
