<?php
$host = '127.0.0.1';
$db = 'pointpay';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- CHECKING USER 'Abubakar' ---\n";

    $stmt = $pdo->prepare("SELECT id, username, email, kyc_status FROM users WHERE username LIKE ? OR name LIKE ?");
    $stmt->execute(['%abubakar%', '%abubakar%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $u) {
        echo "USER: ID: {$u['id']}, Name: {$u['username']}, Email: {$u['email']}, Status: {$u['kyc_status']}\n";
    }

    echo "\n--- CHECKING COLUMNS IN user_kyc ---\n";
    $stmt = $pdo->query("DESCRIBE user_kyc");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";

    echo "\n--- CHECKING COLUMNS IN companies ---\n";
    $stmt = $pdo->query("DESCRIBE companies");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";

} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
