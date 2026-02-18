<?php
$host = '127.0.0.1';
$db = 'pointpay';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find Jamilu in users
    $stmt = $pdo->prepare("SELECT id, username, id_card_path, utility_bill_path FROM users WHERE username LIKE ?");
    $stmt->execute(['%jamilu%']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "User: " . $user['username'] . "\n";
        echo "ID Card: " . ($user['id_card_path'] ? $user['id_card_path'] : 'NULL') . "\n";
        echo "Utility: " . ($user['utility_bill_path'] ? $user['utility_bill_path'] : 'NULL') . "\n";

        // Check user_kyc
        $stmt = $pdo->prepare("SELECT full_response_json FROM user_kyc WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $kyc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($kyc && $kyc['full_response_json']) {
            echo "Full Response JSON Length: " . strlen($kyc['full_response_json']) . "\n";
            $json = json_decode($kyc['full_response_json'], true);
            if ($json) {
                echo "JSON Keys: " . implode(', ', array_keys($json)) . "\n";
                if (isset($json['bvn_full_payload']))
                    echo "Has bvn_full_payload\n";
                if (isset($json['nin_full_payload']))
                    echo "Has nin_full_payload\n";
            } else {
                echo "JSON Decode Failed. Raw (first 100 chars): " . substr($kyc['full_response_json'], 0, 100) . "\n";
            }
        } else {
            echo "No full_response_json in user_kyc.\n";
        }
    } else {
        echo "User not found.\n";
    }

} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
