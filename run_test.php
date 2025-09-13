<?php
// Simple web-based test for the election management system
?>
<!DOCTYPE html>
<html>
<head>
    <title>Election Management System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🏛️ Election Management System Test</h1>
    
    <?php
    try {
        echo "<div class='test-section'>";
        echo "<h2>Database Connection Test</h2>";
        require_once 'config.php';
        echo "<p class='success'>✓ Database connection successful</p>";
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>MLA Records Check</h2>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mla_master");
        $mlaCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='info'>Found " . $mlaCount['count'] . " MLA records</p>";
        
        if ($mlaCount['count'] > 0) {
            $stmt = $pdo->query("SELECT mla_constituency_code, mla_constituency_name FROM mla_master LIMIT 5");
            $mlaRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Sample MLA Records:</h3><ul>";
            foreach ($mlaRecords as $mla) {
                echo "<li>Code: {$mla['mla_constituency_code']}, Name: {$mla['mla_constituency_name']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='warning'>⚠️ No MLA records found. Please add MLA records first.</p>";
        }
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>Booth Records Check</h2>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM booth_master");
        $boothCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='info'>Found " . $boothCount['count'] . " booth records</p>";
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>Upload System Check</h2>";
        $files = [
            'excel_upload_preview.php' => 'Upload Preview System',
            'BoothMaster.php' => 'Booth Master Class',
            'booth_template.csv' => 'CSV Template',
            'test_booth_upload.csv' => 'Test Data File',
            'test_invalid_booth_data.csv' => 'Invalid Test Data'
        ];
        
        foreach ($files as $file => $description) {
            if (file_exists($file)) {
                echo "<p class='success'>✓ {$description} - {$file}</p>";
            } else {
                echo "<p class='error'>❌ Missing: {$description} - {$file}</p>";
            }
        }
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>System Status</h2>";
        if ($mlaCount['count'] > 0) {
            echo "<p class='success'>🎉 System is ready for testing!</p>";
            echo "<h3>Next Steps:</h3>";
            echo "<ol>";
            echo "<li><a href='excel_upload_preview.php' target='_blank'>Open Upload System</a></li>";
            echo "<li><a href='booth_template.csv' download>Download CSV Template</a></li>";
            echo "<li><a href='test_booth_upload.csv' download>Download Test Data</a></li>";
            echo "<li><a href='test_invalid_booth_data.csv' download>Download Invalid Test Data</a></li>";
            echo "</ol>";
            
            echo "<h3>Test Scenarios:</h3>";
            echo "<ul>";
            echo "<li><strong>Valid Upload:</strong> Use test_booth_upload.csv to test successful upload</li>";
            echo "<li><strong>Validation Test:</strong> Use test_invalid_booth_data.csv to test error handling</li>";
            echo "<li><strong>Template Test:</strong> Use booth_template.csv as a starting point</li>";
            echo "</ul>";
        } else {
            echo "<p class='warning'>⚠️ System not ready - no MLA records found</p>";
            echo "<p>Please add MLA records through the MLA Master interface first.</p>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='test-section'>";
        echo "<h2 class='error'>❌ System Error</h2>";
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
    
    <div class="test-section">
        <h2>Quick Links</h2>
        <p><a href="excel_upload_preview.php">📤 Booth Upload System</a></p>
        <p><a href="booth_view.php">🏛️ Booth Master View</a></p>
        <p><a href="mla_view.php">🏛️ MLA Master View</a></p>
        <p><a href="mp_view.php">📊 MP Master View</a></p>
    </div>
</body>
</html>
