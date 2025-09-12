<?php
require_once 'config.php';
require_once 'BoothMaster.php';

try {
    echo "Testing BoothMaster class...\n";
    $booth = new BoothMaster($pdo);
    echo "✓ BoothMaster class loaded successfully!\n";
    
    // Test reading records
    $records = $booth->readAll();
    echo "✓ Read " . count($records) . " booth records\n";
    
    // Test getting MLA records
    $mlaRecords = $booth->getMLARecords();
    echo "✓ Found " . count($mlaRecords) . " MLA records\n";
    
    // Test getting stats
    $stats = $booth->getStats();
    echo "✓ Statistics: " . $stats['total_booths'] . " booths, " . $stats['total_mla_constituencies'] . " MLA constituencies\n";
    
    echo "\n=== Booth System Test Complete ===\n";
    echo "✓ All tests passed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
