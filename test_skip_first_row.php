<?php
// Test page for skip first row functionality
echo "<!DOCTYPE html>";
echo "<html><head><title>Skip First Row Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .test-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;}</style>";
echo "</head><body>";

echo "<h1>📋 Skip First Row Functionality Test</h1>";

try {
    require_once 'config.php';
    
    echo "<div class='test-section'>";
    echo "<h2>Test Files Available</h2>";
    
    $testFiles = [
        'test_booth_upload.csv' => 'With Headers (Standard)',
        'test_no_headers.csv' => 'Without Headers (Skip First Row)',
        'test_booth_upload.xlsx' => 'Excel with Headers',
        'test_invalid_booth_data.csv' => 'Invalid Data (for error testing)'
    ];
    
    foreach ($testFiles as $file => $description) {
        if (file_exists($file)) {
            echo "<p class='success'>✓ {$description}: <a href='{$file}' download>{$file}</a></p>";
        } else {
            echo "<p class='error'>❌ Missing: {$description}: {$file}</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>How to Test Skip First Row</h2>";
    echo "<ol>";
    echo "<li><strong>Test with Headers:</strong>";
    echo "<ul>";
    echo "<li>Upload <code>test_booth_upload.csv</code></li>";
    echo "<li>Select 'First row contains column headers'</li>";
    echo "<li>Verify headers are detected correctly</li>";
    echo "</ul></li>";
    echo "<li><strong>Test without Headers:</strong>";
    echo "<ul>";
    echo "<li>Upload <code>test_no_headers.csv</code></li>";
    echo "<li>Select 'Skip first row (treat as data)'</li>";
    echo "<li>Verify default headers are used</li>";
    echo "<li>Verify first row is treated as data</li>";
    echo "</ul></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>Expected Behavior</h2>";
    echo "<h3>With Headers (skip_first_row = no):</h3>";
    echo "<ul>";
    echo "<li>First row becomes column headers</li>";
    echo "<li>Data starts from second row</li>";
    echo "<li>Headers are validated against expected format</li>";
    echo "</ul>";
    
    echo "<h3>Without Headers (skip_first_row = yes):</h3>";
    echo "<ul>";
    echo "<li>Default headers are used: mla_constituency_code, sl_no, polling_station_no, location_name_of_building, polling_areas, polling_station_type</li>";
    echo "<li>All rows including first row are treated as data</li>";
    echo "<li>Data is validated against default column structure</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>Visual Indicators</h2>";
    echo "<p>In the preview section, you'll see:</p>";
    echo "<ul>";
    echo "<li><strong>First Row:</strong> ✅ Used as headers (when skip_first_row = no)</li>";
    echo "<li><strong>First Row:</strong> ⚠️ Skipped (treated as data) (when skip_first_row = yes)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>Ready to Test</h2>";
    echo "<p><a href='excel_upload_preview.php' target='_blank'>🚀 Open Upload System</a></p>";
    echo "<p><a href='test_booth_upload.csv' download>📊 Download CSV with Headers</a></p>";
    echo "<p><a href='test_no_headers.csv' download>📋 Download CSV without Headers</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ Test Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
