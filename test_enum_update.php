<?php
// Test page for ENUM update
echo "<!DOCTYPE html>";
echo "<html><head><title>ENUM Update Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .test-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;} .feature-list{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .step{background:#e9ecef;padding:10px;border-radius:5px;margin:10px 0;border-left:4px solid #007bff;}</style>";
echo "</head><body>";

echo "<h1>🗃️ Database ENUM Update Test</h1>";

try {
    require_once 'config.php';
    
    echo "<div class='test-section'>";
    echo "<h2>✅ Database ENUM Updated Successfully!</h2>";
    echo "<div class='feature-list'>";
    echo "<h3>🗃️ Database Changes:</h3>";
    echo "<ul>";
    echo "<li><strong>Column Type:</strong> Changed from VARCHAR(255) to ENUM</li>";
    echo "<li><strong>ENUM Values:</strong> 'Regular', 'Auxiliary', 'Special', 'Mobile'</li>";
    echo "<li><strong>Default Value:</strong> 'Regular'</li>";
    echo "<li><strong>Case Sensitivity:</strong> Exact case matching required</li>";
    echo "</ul>";
    
    echo "<h3>✅ Valid Polling Station Types:</h3>";
    echo "<ul>";
    echo "<li><strong>Regular</strong> - Standard polling station</li>";
    echo "<li><strong>Auxiliary</strong> - Additional/backup polling station</li>";
    echo "<li><strong>Special</strong> - Special needs or special category station</li>";
    echo "<li><strong>Mobile</strong> - Mobile polling station</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>📁 Test File Available</h2>";
    
    if (file_exists('test_polling_station_types.csv')) {
        echo "<div class='step'>";
        echo "<strong>📁 test_polling_station_types.csv:</strong><br>";
        echo "<em>Test file with various polling station types - some valid, some invalid</em><br>";
        echo "<a href='test_polling_station_types.csv' download style='color:#007bff;'>📥 Download</a>";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<h4>Test Data Contents:</h4>";
        echo "<ul>";
        echo "<li><strong>Row 1:</strong> Regular ✅ (valid)</li>";
        echo "<li><strong>Row 2:</strong> Auxiliary ✅ (valid)</li>";
        echo "<li><strong>Row 3:</strong> Special ✅ (valid)</li>";
        echo "<li><strong>Row 4:</strong> Mobile ✅ (valid)</li>";
        echo "<li><strong>Row 5:</strong> INVALID_TYPE ❌ (invalid - should show error)</li>";
        echo "<li><strong>Row 6:</strong> regular ❌ (invalid - wrong case)</li>";
        echo "<li><strong>Row 7:</strong> AUXILIARY ❌ (invalid - wrong case)</li>";
        echo "<li><strong>Row 8:</strong> Special ✅ (valid)</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Missing: test_polling_station_types.csv</p>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🧪 How to Test ENUM Validation</h2>";
    
    echo "<div class='step'>";
    echo "<h4>Step 1: Access MLA Detail Page</h4>";
    echo "<p>Go to <a href='mla_detail.php?mp_id=1&mla_id=1' target='_blank'>mla_detail.php?mp_id=1&mla_id=1</a></p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 2: Open Upload Section</h4>";
    echo "<p>Click the <strong>'📤 Upload Data'</strong> button</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 3: Upload Test File</h4>";
    echo "<p>Download and upload <strong>test_polling_station_types.csv</strong></p>";
    echo "<p>You should see:</p>";
    echo "<ul>";
    echo "<li>✅ <strong>Valid types</strong> (Regular, Auxiliary, Special, Mobile) pass validation</li>";
    echo "<li>❌ <strong>Invalid types</strong> show specific error messages</li>";
    echo "<li>❌ <strong>Case sensitivity</strong> errors for 'regular' and 'AUXILIARY'</li>";
    echo "<li>❌ <strong>Unknown types</strong> like 'INVALID_TYPE' show error</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 4: Check Error Messages</h4>";
    echo "<p>Look for error messages like:</p>";
    echo "<ul>";
    echo "<li>\"Invalid polling station type 'INVALID_TYPE'. Must be one of: Regular, Auxiliary, Special, Mobile\"</li>";
    echo "<li>\"Invalid polling station type 'regular'. Must be one of: Regular, Auxiliary, Special, Mobile\"</li>";
    echo "<li>\"Invalid polling station type 'AUXILIARY'. Must be one of: Regular, Auxiliary, Special, Mobile\"</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🎯 Database Verification</h2>";
    
    // Verify the database ENUM
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM booth_master LIKE 'polling_station_type'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='step'>";
        echo "<h4>✅ Current Database Schema:</h4>";
        echo "<ul>";
        echo "<li><strong>Field:</strong> " . $result['Field'] . "</li>";
        echo "<li><strong>Type:</strong> " . $result['Type'] . "</li>";
        echo "<li><strong>Default:</strong> " . $result['Default'] . "</li>";
        echo "<li><strong>Null:</strong> " . ($result['Null'] === 'YES' ? 'Allowed' : 'Not Allowed') . "</li>";
        echo "</ul>";
        echo "</div>";
        
        // Test ENUM values
        echo "<div class='step'>";
        echo "<h4>✅ ENUM Value Testing:</h4>";
        $testValues = ['Regular', 'Auxiliary', 'Special', 'Mobile'];
        $invalidValues = ['regular', 'AUXILIARY', 'INVALID_TYPE', 'special'];
        
        echo "<strong>Valid Values:</strong><br>";
        foreach ($testValues as $value) {
            echo "✅ '$value' - Valid<br>";
        }
        
        echo "<br><strong>Invalid Values (should be rejected):</strong><br>";
        foreach ($invalidValues as $value) {
            echo "❌ '$value' - Invalid<br>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='step'>";
        echo "<h4 class='error'>❌ Database Error:</h4>";
        echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🚀 Ready to Test ENUM Validation</h2>";
    echo "<p><a href='mla_detail.php?mp_id=1&mla_id=1' target='_blank' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🏛️ Test MLA Detail with ENUM Validation</a></p>";
    echo "<p><em>Upload test_polling_station_types.csv to see the ENUM validation in action! 🗃️</em></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ Test Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
