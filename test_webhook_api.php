<?php
/**
 * Test the webhook API endpoint to see what it returns
 */

require __DIR__.'/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_DATABASE'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TESTING WEBHOOK API ENDPOINT ===\n\n";
    
    // Get an admin user
    $stmt = $pdo->query("SELECT id, email, type FROM users WHERE UPPER(type) = 'ADMIN' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "❌ No admin user found\n";
        exit(1);
    }
    
    echo "✅ Found admin user: {$admin['email']} (ID: {$admin['id']})\n\n";
    
    // Simulate the API call
    echo "=== SIMULATING API CALL ===\n";
    echo "Endpoint: /api/secure/webhooks?id={$admin['id']}\n\n";
    
    // Get the data like the controller does
    $userId = $admin['id'];
    $user = $pdo->query("SELECT * FROM users WHERE id = $userId")->fetch(PDO::FETCH_ASSOC);
    $isAdmin = strtoupper($user['type']) === 'ADMIN';
    
    echo "User Type: {$user['type']}\n";
    echo "Is Admin: " . ($isAdmin ? 'YES' : 'NO') . "\n\n";
    
    if ($isAdmin) {
        echo "=== ADMIN QUERY ===\n";
        
        // Count total records
        $countStmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM palmpay_webhooks
            LEFT JOIN transactions ON palmpay_webhooks.transaction_id = transactions.id
            LEFT JOIN companies ON transactions.company_id = companies.id
        ");
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "Total records: $total\n\n";
        
        // Get paginated data
        $stmt = $pdo->query("
            SELECT 
                palmpay_webhooks.*,
                companies.name as company_name,
                companies.business_name,
                transactions.transaction_id as transaction_ref,
                transactions.amount as transaction_amount,
                palmpay_webhooks.created_at as sent_at
            FROM palmpay_webhooks
            LEFT JOIN transactions ON palmpay_webhooks.transaction_id = transactions.id
            LEFT JOIN companies ON transactions.company_id = companies.id
            ORDER BY palmpay_webhooks.created_at DESC
            LIMIT 50
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Records fetched: " . count($data) . "\n\n";
        
        // Show what the API would return
        $response = [
            'status' => 'success',
            'webhook_logs' => [
                'current_page' => 1,
                'data' => $data,
                'total' => $total,
                'per_page' => 50
            ]
        ];
        
        echo "=== API RESPONSE STRUCTURE ===\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
        
        echo "=== FIRST RECORD SAMPLE ===\n";
        if (count($data) > 0) {
            echo json_encode($data[0], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "No records found\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
