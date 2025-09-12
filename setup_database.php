<?php
require_once 'config.php';

echo "Setting up Election Management Database...\n\n";

try {
    // Read and execute the SQL file
    $sqlFile = 'election_mgmt_v1.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Could not read SQL file: $sqlFile");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ Error: " . $e->getMessage() . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n=== Setup Complete ===\n";
    echo "Successful statements: $successCount\n";
    echo "Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n🎉 Database setup completed successfully!\n";
        echo "You can now login with:\n";
        echo "Username: superadmin\n";
        echo "Password: admin123\n";
    } else {
        echo "\n⚠️  Some statements failed. Please check the errors above.\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
?>
