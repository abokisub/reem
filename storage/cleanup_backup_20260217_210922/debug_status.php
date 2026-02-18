<?php
$host = '127.0.0.1';
$db = 'pointpay';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- CHECKING USER 'JAMILU' ---\n";

    // Check Users Table for kyc_submitted_at
    $stmt = $pdo->prepare("SELECT id, username, kyc_status, kyc_submitted_at FROM users WHERE username LIKE ?");
    $stmt->execute(['%jamilu%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $u) {
        echo "USERS TABLE: ID: {$u['id']}, User: {$u['username']}, Status: {$u['kyc_status']}, SubmittedAt: " . ($u['kyc_submitted_at'] ? $u['kyc_submitted_at'] : 'NULL') . "\n";
    }

} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
