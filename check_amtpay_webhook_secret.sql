-- Run this SQL query on PointWave LIVE database
-- This will show Amtpay's webhook secret

SELECT 
    id,
    name,
    email,
    webhook_secret,
    LENGTH(webhook_secret) as secret_length,
    status,
    is_active
FROM companies 
WHERE id = 10 OR name LIKE '%Amtpay%' OR email LIKE '%amtpay%';

-- Expected result should show:
-- id: 10
-- name: Amtpay (or similar)
-- webhook_secret: whsec_... (this is what we need to verify)
