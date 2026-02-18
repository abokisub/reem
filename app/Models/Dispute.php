<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'transaction_id',
        'amount',
        'reason',
        'status',
        'evidence',
        'resolved_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($dispute) {
            $dispute->uuid = $dispute->uuid ?? 'PWV_DSP_' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
