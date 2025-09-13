<?php
// Simple test page to verify upload functionality
echo "<!DOCTYPE html>";
echo "<html><head><title>Upload Test</title></head><body>";
echo "<h1>Booth Upload System Test</h1>";

try {
    require_once 'config.php';
    require_once 'BoothMaster.php';
    
    echo "<h2>Database Connection Test</h2>";
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connection successful<br>";
    
    echo "<h2>MLA Records Check</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM mla_master");
    $mlaCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Found " . $mlaCount['count'] . " MLA records<br>";
    
    if ($mlaCount['count'] > 0) {
        $stmt = $pdo->query("SELECT mla_id, mla_constituency_code, mla_constituency_name FROM mla_master LIMIT 5");
        $mlaRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample MLA Records:</h3><ul>";
        foreach ($mlaRecords as $mla) {
            echo "<li>Code: {$mla['mla_constituency_code']}, Name: {$mla['mla_constituency_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ No MLA records found. Please add MLA records first.<br>";
    }
    
    echo "<h2>Booth Records Check</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM booth_master");
    $boothCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Found " . $boothCount['count'] . " booth records<br>";
    
    echo "<h2>Upload System Test</h2>";
    if ($mlaCount['count'] > 0) {
        echo "✓ System ready for testing<br>";
        echo "<p><a href='excel_upload_preview.php'>Go to Upload Page</a></p>";
        echo "<p><a href='test_booth_upload.csv' download>Download Test CSV File</a></p>";
    } else {
        echo "❌ System not ready - no MLA records found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "</body></html>";
?>
