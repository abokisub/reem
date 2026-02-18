<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyWebhookLog extends Model
{
    use HasFactory;

    protected $table = 'webhook_logs';

    protected $fillable = [
        'company_id',
        'transaction_id',
        'event_type',
        'webhook_url',
        'payload',
        'http_status',
        'response_body',
        'attempt_number',
        'status',
        'sent_at',
        'last_attempt_at',
        'next_retry_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction()
    {
        // Assuming transaction_id links to transfers or some transaction table
        // But the migration said 'transaction_id' constrained. 
        // We need to know WHICH table it's constrained to. 
        // Migration 000007 said: $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
        // By default Laravel assumes 'transactions' table.
        // But pointing to 'transactions' (if it exists) or 'transfers'.
        // File list showed 'Transaction.php'.
        return $this->belongsTo(Transaction::class);
    }
}
