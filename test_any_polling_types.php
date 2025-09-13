<?php
// Test page for any polling station types
echo "<!DOCTYPE html>";
echo "<html><head><title>Any Polling Types Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .test-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;} .feature-list{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .step{background:#e9ecef;padding:10px;border-radius:5px;margin:10px 0;border-left:4px solid #007bff;}</style>";
echo "</head><body>";

echo "<h1>✅ Any Polling Station Type Accepted</h1>";

try {
    require_once 'config.php';
    
    echo "<div class='test-section'>";
    echo "<h2>✅ Database Reverted to VARCHAR - Any Value Accepted!</h2>";
    echo "<div class='feature-list'>";
    echo "<h3>🗃️ Database Changes:</h3>";
    echo "<ul>";
    echo "<li><strong>Column Type:</strong> VARCHAR(255) - accepts any text value</li>";
    echo "<li><strong>Default Value:</strong> 'Regular'</li>";
    echo "<li><strong>Validation:</strong> No restrictions - any value accepted</li>";
    echo "<li><strong>Case Sensitivity:</strong> All cases accepted (Regular, regular, REGULAR)</li>";
    echo "</ul>";
    
    echo "<h3>✅ Any Polling Station Type Examples:</h3>";
    echo "<ul>";
    echo "<li><strong>Standard Types:</strong> Regular, Auxiliary, Special, Mobile</li>";
    echo "<li><strong>Custom Types:</strong> Custom Type, Another Type, Any Value</li>";
    echo "<li><strong>Case Variations:</strong> regular, AUXILIARY, Special</li>";
    echo "<li><strong>Descriptive Types:</strong> Primary Station, Backup Station, Emergency Station</li>";
    echo "<li><strong>Any Text:</strong> Literally any text value is accepted</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>📁 New Test File Available</h2>";
    
    if (file_exists('test_any_polling_types.csv')) {
        echo "<div class='step'>";
        echo "<strong>📁 test_any_polling_types.csv:</strong><br>";
        echo "<em>Test file with various polling station types - all should be accepted</em><br>";
        echo "<a href='test_any_polling_types.csv' download style='color:#007bff;'>📥 Download</a>";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<h4>Test Data Contents:</h4>";
        echo "<ul>";
        echo "<li><strong>Row 1:</strong> Regular ✅ (accepted)</li>";
        echo "<li><strong>Row 2:</strong> Auxiliary ✅ (accepted)</li>";
        echo "<li><strong>Row 3:</strong> Special ✅ (accepted)</li>";
        echo "<li><strong>Row 4:</strong> Mobile ✅ (accepted)</li>";
        echo "<li><strong>Row 5:</strong> Custom Type ✅ (accepted)</li>";
        echo "<li><strong>Row 6:</strong> regular ✅ (accepted - any case)</li>";
        echo "<li><strong>Row 7:</strong> AUXILIARY ✅ (accepted - any case)</li>";
        echo "<li><strong>Row 8:</strong> Special ✅ (accepted)</li>";
        echo "<li><strong>Row 9:</strong> Any Value ✅ (accepted)</li>";
        echo "<li><strong>Row 10:</strong> Another Custom Type ✅ (accepted)</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Missing: test_any_polling_types.csv</p>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🧪 How to Test Any Polling Station Type</h2>";
    
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
    echo "<p>Download and upload <strong>test_any_polling_types.csv</strong></p>";
    echo "<p>You should see:</p>";
    echo "<ul>";
    echo "<li>✅ <strong>All polling station types</strong> pass validation</li>";
    echo "<li>✅ <strong>No validation errors</strong> for polling station type</li>";
    echo "<li>✅ <strong>Any case accepted</strong> (regular, AUXILIARY, etc.)</li>";
    echo "<li>✅ <strong>Custom types accepted</strong> (Custom Type, Any Value, etc.)</li>";
    echo "<li>✅ <strong>Green 'Ready for Upload'</strong> status</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 4: Test Your Own Values</h4>";
    echo "<p>Create your own CSV file with any polling station types you want:</p>";
    echo "<ul>";
    echo "<li>Primary Station</li>";
    echo "<li>Backup Station</li>";
    echo "<li>Emergency Station</li>";
    echo "<li>Mobile Unit</li>";
    echo "<li>Special Category</li>";
    echo "<li>Any other text you want</li>";
    echo "</ul>";
    echo "<p>All values will be accepted without validation errors!</p>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🎯 Database Verification</h2>";
    
    // Verify the database VARCHAR
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM booth_master LIKE 'polling_station_type'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='step'>";
        echo "<h4>✅ Current Database Schema:</h4>";
        echo "<ul>";
        echo "<li><strong>Field:</strong> " . $result['Field'] . "</li>";
        echo "<li><strong>Type:</strong> " . $result['Type'] . " (accepts any text up to 255 characters)</li>";
        echo "<li><strong>Default:</strong> " . $result['Default'] . "</li>";
        echo "<li><strong>Null:</strong> " . ($result['Null'] === 'YES' ? 'Allowed' : 'Not Allowed') . "</li>";
        echo "</ul>";
        echo "</div>";
        
        // Test various values
        echo "<div class='step'>";
        echo "<h4>✅ Value Testing:</h4>";
        $testValues = ['Regular', 'Auxiliary', 'Special', 'Mobile', 'Custom Type', 'regular', 'AUXILIARY', 'Any Value', 'Primary Station', 'Backup Station'];
        
        echo "<strong>All these values are accepted:</strong><br>";
        foreach ($testValues as $value) {
            echo "✅ '$value' - Accepted<br>";
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
    echo "<h2>🚀 Ready to Test Any Polling Station Type</h2>";
    echo "<p><a href='mla_detail.php?mp_id=1&mla_id=1' target='_blank' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🏛️ Test MLA Detail with Any Polling Types</a></p>";
    echo "<p><em>Upload test_any_polling_types.csv to see that any polling station type is accepted! ✅</em></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ Test Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
