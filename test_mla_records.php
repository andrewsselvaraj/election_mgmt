<?php
require_once 'config.php';

try {
    echo "Testing database connection and MLA records...\n";
    
    // Check MLA records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM mla_master");
    $mlaCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Found " . $mlaCount['count'] . " MLA records\n";
    
    if ($mlaCount['count'] > 0) {
        // Get first few MLA records
        $stmt = $pdo->query("SELECT mla_id, mla_constituency_code, mla_constituency_name FROM mla_master LIMIT 5");
        $mlaRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample MLA records:\n";
        foreach ($mlaRecords as $mla) {
            echo "  - ID: {$mla['mla_id']}, Code: {$mla['mla_constituency_code']}, Name: {$mla['mla_constituency_name']}\n";
        }
    } else {
        echo "❌ No MLA records found. Please add MLA records first.\n";
    }
    
    // Check booth records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM booth_master");
    $boothCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Found " . $boothCount['count'] . " booth records\n";
    
    echo "\n=== Database Check Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
