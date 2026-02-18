<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'external_customer_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'date_of_birth',
        'id_type',
        'id_number',
        'id_card_path',
        'utility_bill_path',
        'kyc_status',
        'status',
        'is_test',
        'nin',
        'nin_verified',
        'nin_verified_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'nin_verified' => 'boolean',
        'nin_verified_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->uuid = $user->uuid ?? bin2hex(random_bytes(20)); // 40 chars hex
            $user->status = $user->status ?? 'active';
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class, 'company_user_id');
    }
}
