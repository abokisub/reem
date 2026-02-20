<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIXING COMPANY USERNAME ===\n\n";

$companies = DB::table('companies')->whereNull('username')->orWhere('username', '')->get();

echo "Found " . $companies->count() . " companies without username\n\n";

foreach ($companies as $company) {
    // Generate username from company name or email
    $username = null;
    
    if ($company->company_name) {
        // Create username from company name
        $username = strtolower(str_replace(' ', '', $company->company_name));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
    } elseif ($company->name) {
        $username = strtolower(str_replace(' ', '', $company->name));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
    } elseif ($company->email) {
        // Use email prefix as username
        $username = explode('@', $company->email)[0];
    }
    
    if ($username) {
        // Check if username already exists
        $exists = DB::table('companies')
            ->where('username', $username)
            ->where('id', '!=', $company->id)
            ->exists();
        
        if ($exists) {
            // Append company ID to make it unique
            $username = $username . $company->id;
        }
        
        DB::table('companies')
            ->where('id', $company->id)
            ->update(['username' => $username]);
        
        echo "✅ Company ID {$company->id}: Set username to '{$username}'\n";
    } else {
        echo "⚠️  Company ID {$company->id}: Could not generate username\n";
    }
}

echo "\n=== DONE ===\n";
