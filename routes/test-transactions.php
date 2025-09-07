<?php

// Simple test file to debug transactions API
// Access via: http://localhost/gpr/routes/test-transactions.php

header('Content-Type: application/json');

try {
    // Test database connection
    $pdo = new PDO('mysql:host=localhost;dbname=gpr', 'root', '');
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test actual query that controller uses
    $stmt2 = $pdo->prepare("
        SELECT 
            t.id,
            t.booking_transaction_id,
            t.started_at as start_date,
            t.end_date,
            t.status as booking_status,
            t.customer_name
        FROM transactions as t 
        WHERE t.status IN ('booking', 'confirmed', 'active', 'ongoing')
        AND t.end_date >= CURDATE()
        ORDER BY t.started_at ASC
        LIMIT 100
    ");
    $stmt2->execute();
    $transactions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'database_connected' => true,
        'total_transactions' => $count['count'],
        'active_transactions' => count($transactions),
        'sample_data' => array_slice($transactions, 0, 3)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
