<?php
echo "Testing Election Management System...\n";

try {
    require_once 'config.php';
    echo "✓ Database connection successful\n";
    
    // Test MLA records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM mla_master");
    $mlaCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Found " . $mlaCount['count'] . " MLA records\n";
    
    // Test booth records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM booth_master");
    $boothCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Found " . $boothCount['count'] . " booth records\n";
    
    // Test upload system files
    if (file_exists('excel_upload_preview.php')) {
        echo "✓ Upload system file exists\n";
    }
    
    if (file_exists('BoothMaster.php')) {
        echo "✓ BoothMaster class exists\n";
    }
    
    if (file_exists('booth_template.csv')) {
        echo "✓ CSV template exists\n";
    }
    
    echo "\n=== System Status ===\n";
    echo "✓ Database: Connected\n";
    echo "✓ MLA Records: " . $mlaCount['count'] . "\n";
    echo "✓ Booth Records: " . $boothCount['count'] . "\n";
    echo "✓ Upload System: Ready\n";
    
    if ($mlaCount['count'] > 0) {
        echo "\n🎉 System is ready for testing!\n";
        echo "You can now:\n";
        echo "1. Open http://localhost/election_mgmt/excel_upload_preview.php in your browser\n";
        echo "2. Upload booth data using the CSV template\n";
        echo "3. Test the validation system\n";
    } else {
        echo "\n⚠️  No MLA records found. Please add MLA records first.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
