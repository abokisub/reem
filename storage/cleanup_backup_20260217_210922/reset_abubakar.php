<?php
$host = '127.0.0.1';
$db = 'pointpay';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = 13;
    echo "--- RESETTING USER ID $userId (Jamilu Abubakar) ---\n";

    // 1. Delete from user_kyc
    $stmt = $pdo->prepare("DELETE FROM user_kyc WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Deleted from user_kyc.\n";

    // 2. Delete from companies
    $stmt = $pdo->prepare("DELETE FROM companies WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Deleted from companies.\n";

    // 3. Delete from wallets (if exists, assuming user_id column)
    // Check if table exists first/column exists to avoid error if not using user_id directly or different schema
    // Skipping to avoid breaking if schema differs, strictly focusing on KYC/User for now unless strictly required.
    // Actually, let's just delete the user, cascade should handle it if set up, otherwise we leave orphans (acceptable for dev reset).

    // 4. Delete from users
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    echo "Deleted from users.\n";

    echo "--- RESET COMPLETE ---\n";

} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
