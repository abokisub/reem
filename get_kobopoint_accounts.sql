-- Get KoboPoint company details
SELECT 
    id as company_id,
    name,
    email,
    business_id,
    api_public_key,
    status,
    created_at
FROM companies 
WHERE business_id = '3450968aa027e86e3ff5b0169dc17edd7694a846'
   OR api_public_key = '7db8dbb3991382487a1fc388a05d96a7139d92ba'
LIMIT 1;

-- Get all virtual accounts for KoboPoint
SELECT 
    va.id,
    va.customer_id,
    va.account_number,
    va.account_name,
    va.bank_name,
    va.bank_code,
    va.status,
    va.kyc_level,
    va.daily_limit,
    va.created_at,
    cu.first_name,
    cu.last_name,
    cu.email,
    cu.phone,
    cu.bvn,
    cu.nin
FROM virtual_accounts va
LEFT JOIN company_users cu ON va.customer_id = cu.id
WHERE va.company_id = (
    SELECT id FROM companies 
    WHERE business_id = '3450968aa027e86e3ff5b0169dc17edd7694a846'
    LIMIT 1
)
ORDER BY va.created_at DESC;

-- Get statistics
SELECT 
    COUNT(*) as total_accounts,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_accounts,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_accounts,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_accounts,
    SUM(CASE WHEN kyc_level = 'tier1' THEN 1 ELSE 0 END) as tier1_accounts,
    SUM(CASE WHEN kyc_level = 'tier2' THEN 1 ELSE 0 END) as tier2_accounts,
    SUM(CASE WHEN kyc_level = 'tier3' THEN 1 ELSE 0 END) as tier3_accounts
FROM virtual_accounts
WHERE company_id = (
    SELECT id FROM companies 
    WHERE business_id = '3450968aa027e86e3ff5b0169dc17edd7694a846'
    LIMIT 1
);

-- Get recent transactions for these accounts
SELECT 
    t.id,
    t.transaction_id,
    t.reference,
    t.amount,
    t.fee,
    t.status,
    t.type,
    t.category,
    t.transaction_type,
    t.created_at,
    va.account_number,
    va.account_name
FROM transactions t
JOIN virtual_accounts va ON t.virtual_account_id = va.id
WHERE va.company_id = (
    SELECT id FROM companies 
    WHERE business_id = '3450968aa027e86e3ff5b0169dc17edd7694a846'
    LIMIT 1
)
ORDER BY t.created_at DESC
LIMIT 20;
