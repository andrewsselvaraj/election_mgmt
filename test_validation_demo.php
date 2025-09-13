<?php
// Test page for enhanced validation functionality
echo "<!DOCTYPE html>";
echo "<html><head><title>Enhanced Validation Demo</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .test-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;} .feature-list{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .error-example{background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;border-left:4px solid #dc3545;} .success-example{background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;border-left:4px solid #28a745;}</style>";
echo "</head><body>";

echo "<h1>🔍 Enhanced Preview & Validation Functionality Demo</h1>";

try {
    require_once 'config.php';
    
    echo "<div class='test-section'>";
    echo "<h2>✅ Enhanced Validation Features</h2>";
    echo "<div class='feature-list'>";
    echo "<h3>📋 Data Validation:</h3>";
    echo "<ul>";
    echo "<li><strong>Required Field Validation:</strong> Checks for empty required fields</li>";
    echo "<li><strong>Data Type Validation:</strong> Validates MLA codes are numeric, serial numbers are positive integers</li>";
    echo "<li><strong>Format Validation:</strong> Ensures proper data formats</li>";
    echo "<li><strong>Enum Validation:</strong> Validates polling station types (Regular, Auxiliary, Special, Mobile)</li>";
    echo "<li><strong>Column Count Validation:</strong> Ensures rows have correct number of columns</li>";
    echo "</ul>";
    
    echo "<h3>🔄 Duplicate Detection:</h3>";
    echo "<ul>";
    echo "<li><strong>File-level Duplicates:</strong> Detects duplicate polling stations within the uploaded file</li>";
    echo "<li><strong>Row Reference:</strong> Shows which rows contain duplicates</li>";
    echo "<li><strong>Station Key Validation:</strong> Uses MLA code + station number as unique key</li>";
    echo "</ul>";
    
    echo "<h3>🗄️ Database Validation:</h3>";
    echo "<ul>";
    echo "<li><strong>MLA Code Existence:</strong> Verifies MLA constituency codes exist in mla_master table</li>";
    echo "<li><strong>Booth Conflicts:</strong> Checks for existing polling stations in booth_master table</li>";
    echo "<li><strong>Foreign Key Validation:</strong> Ensures data integrity with related tables</li>";
    echo "</ul>";
    
    echo "<h3>📊 Enhanced Preview:</h3>";
    echo "<ul>";
    echo "<li><strong>Validation Summary:</strong> Color-coded status indicators</li>";
    echo "<li><strong>Error Categorization:</strong> Groups errors by type (General, Data, Duplicates, DB Conflicts)</li>";
    echo "<li><strong>Scrollable Error Lists:</strong> Handles large numbers of errors efficiently</li>";
    echo "<li><strong>Fix Suggestions:</strong> Provides actionable advice for resolving errors</li>";
    echo "<li><strong>Warning System:</strong> Shows non-blocking warnings</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🧪 Test Files Available</h2>";
    
    $testFiles = [
        'test_booth_upload.csv' => 'Valid data with headers (should pass validation)',
        'test_no_headers.csv' => 'Valid data without headers (use "Skip first row" option)',
        'test_validation_errors.csv' => 'Data with various validation errors (for testing error display)',
        'test_booth_upload.xlsx' => 'Valid Excel file with headers',
        'booth_template.csv' => 'Empty template for user data'
    ];
    
    foreach ($testFiles as $file => $description) {
        if (file_exists($file)) {
            echo "<p class='success'>✓ <strong>{$file}:</strong> {$description}</p>";
        } else {
            echo "<p class='error'>❌ Missing: {$file}</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🔍 Validation Error Examples</h2>";
    
    echo "<div class='error-example'>";
    echo "<h4>🚫 General Errors:</h4>";
    echo "<ul>";
    echo "<li>Missing required columns: mla_constituency_code, sl_no, polling_station_no, location_name_of_building</li>";
    echo "<li>MLA constituency codes not found in database: 999</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='error-example'>";
    echo "<h4>📋 Data Format Errors:</h4>";
    echo "<ul>";
    echo "<li>Row 2: MLA constituency code must be numeric</li>";
    echo "<li>Row 3: Serial number must be a positive integer</li>";
    echo "<li>Row 4: Polling station number is required</li>";
    echo "<li>Row 5: Location name is required</li>";
    echo "<li>Row 6: Invalid polling station type. Must be one of: Regular, Auxiliary, Special, Mobile</li>";
    echo "<li>Row 7: MLA constituency code is required</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='error-example'>";
    echo "<h4>🔄 Duplicate Records in File:</h4>";
    echo "<ul>";
    echo "<li>Row 4: Duplicate polling station 003 for MLA 1 (also found in row 3)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='error-example'>";
    echo "<h4>🗄️ Database Conflicts:</h4>";
    echo "<ul>";
    echo "<li>Row 8: Polling station 001 for MLA 1 already exists in database</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='success-example'>";
    echo "<h4>✅ Valid Data Example:</h4>";
    echo "<ul>";
    echo "<li>Row 9: All fields valid - MLA code exists, proper data types, unique station</li>";
    echo "<li>Row 10: Valid with Auxiliary station type</li>";
    echo "<li>Row 11: Valid with Special station type</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🎯 How to Test Enhanced Validation</h2>";
    echo "<ol>";
    echo "<li><strong>Test Valid Data:</strong>";
    echo "<ul>";
    echo "<li>Upload <code>test_booth_upload.csv</code></li>";
    echo "<li>Select 'First row contains column headers'</li>";
    echo "<li>Verify green 'Ready for Upload' status</li>";
    echo "</ul></li>";
    
    echo "<li><strong>Test Error Validation:</strong>";
    echo "<ul>";
    echo "<li>Upload <code>test_validation_errors.csv</code></li>";
    echo "<li>Select 'First row contains column headers'</li>";
    echo "<li>Review all error categories and counts</li>";
    echo "<li>Check detailed error messages</li>";
    echo "<li>Read fix suggestions</li>";
    echo "</ul></li>";
    
    echo "<li><strong>Test Skip First Row:</strong>";
    echo "<ul>";
    echo "<li>Upload <code>test_no_headers.csv</code></li>";
    echo "<li>Select 'Skip first row (treat as data)'</li>";
    echo "<li>Verify default headers are applied</li>";
    echo "<li>Check validation works with default headers</li>";
    echo "</ul></li>";
    
    echo "<li><strong>Test Excel Files:</strong>";
    echo "<ul>";
    echo "<li>Upload <code>test_booth_upload.xlsx</code></li>";
    echo "<li>Test both header options</li>";
    echo "<li>Verify Excel processing works</li>";
    echo "</ul></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🚀 Ready to Test</h2>";
    echo "<p><a href='booth_view.php' target='_blank'>🏛️ Open Booth Master with Enhanced Validation</a></p>";
    echo "<p><a href='test_validation_errors.csv' download>🚫 Download Error Test File</a></p>";
    echo "<p><a href='test_booth_upload.csv' download>✅ Download Valid Test File</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ Test Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
