<?php
// 1. Get verified records from user_kyc
$kycRecords = DB::table('user_kyc')
    ->join('users', 'user_kyc.user_id', '=', 'users.id')
    ->select(
        'user_kyc.id',
        'user_kyc.user_id',
        'user_kyc.id_type',
        'user_kyc.id_number',
        'user_kyc.status',
        'user_kyc.verified_at as submitted_at',
        'user_kyc.full_response_json',
        'users.username',
        'users.name',
        'users.email',
        'users.phone',
        'users.profile_image',
        'users.address',
        'users.id_card_path',
        'users.utility_bill_path',
        'users.type as user_type',
        DB::raw('NULL as kyc_documents'),
        DB::raw("'verified' as kyc_source"),
        DB::raw("CONCAT('@', users.username) as display_user")
    );

// 2. Get pending/submitted records from user table
$userSubmissions = DB::table('users')
    ->where(function ($query) {
        $query->whereNotNull('kyc_submitted_at')
            ->orWhere('kyc_status', '!=', 'unverified');
    })
    ->where('type', '!=', 'ADMIN')
    ->where('type', '!=', 'admin')
    ->select(
        'users.id as id',
        'users.id as user_id',
        DB::raw("'N/A' as id_type"),
        DB::raw("COALESCE(users.bvn, users.nin, 'N/A') as id_number"),
        'users.kyc_status as status',
        DB::raw("COALESCE(users.kyc_submitted_at, users.created_at) as submitted_at"),
        'users.xixapay_kyc_data as full_response_json',
        'users.username',
        'users.name',
        'users.email',
        'users.phone',
        'users.profile_image',
        'users.address',
        'users.id_card_path',
        'users.utility_bill_path',
        'users.type as user_type',
        DB::raw('NULL as kyc_documents'),
        DB::raw("'user_table' as kyc_source"),
        DB::raw("CONCAT('@', users.username) as display_user")
    );

// 3. Get Company KYC submissions
$companySubmissions = DB::table('companies')
    ->join('users', 'companies.user_id', '=', 'users.id')
    ->select(
        'companies.id as id',
        'companies.user_id',
        DB::raw("'Business' as id_type"),
        DB::raw("COALESCE(companies.business_registration_number, 'N/A') as id_number"),
        'companies.kyc_status as status',
        'companies.created_at as submitted_at',
        'companies.verification_data as full_response_json',
        'users.username',
        'companies.name',
        'companies.email',
        'companies.phone',
        'users.profile_image',
        'companies.address',
        DB::raw('NULL as id_card_path'),
        DB::raw('NULL as utility_bill_path'),
        'users.type as user_type',
        'companies.kyc_documents',
        DB::raw("'company_kyc' as kyc_source"),
        DB::raw("CONCAT('🏢 ', companies.name) as display_user")
    );

dump('kycRecords: ' . $kycRecords->count());
dump('userSubmissions: ' . $userSubmissions->count());
dump('companySubmissions: ' . $companySubmissions->count());

$query = $kycRecords->union($userSubmissions)->union($companySubmissions);

dump($query->toSql());
dump($query->getBindings());

$finalQuery = DB::table(DB::raw("({$query->toSql()}) as combined_kyc"))
    ->addBinding($query->getBindings(), 'join')
    ->orderBy('submitted_at', 'desc');

$finalQuery->whereIn('status', ['pending', 'under_review']);

dump($finalQuery->toSql());
dump($finalQuery->getBindings());

dump($finalQuery->count());
dump($finalQuery->get());