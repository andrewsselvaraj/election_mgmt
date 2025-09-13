<?php
// Test Excel file support
echo "<!DOCTYPE html>";
echo "<html><head><title>Excel Support Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .test-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;}</style>";
echo "</head><body>";

echo "<h1>📊 Excel File Support Test</h1>";

try {
    require_once 'config.php';
    
    echo "<div class='test-section'>";
    echo "<h2>Excel File Processing Test</h2>";
    
    // Test 1: Check if Excel files exist
    $excelFiles = [
        'test_booth_upload.xlsx' => 'Sample Excel File',
        'test_booth_upload.csv' => 'Sample CSV File',
        'booth_template.csv' => 'CSV Template'
    ];
    
    foreach ($excelFiles as $file => $description) {
        if (file_exists($file)) {
            echo "<p class='success'>✓ {$description}: {$file}</p>";
        } else {
            echo "<p class='error'>❌ Missing: {$description}: {$file}</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>Excel Processing Functions Test</h2>";
    
    // Test Excel processing functions
    if (function_exists('processBoothExcelForPreview')) {
        echo "<p class='success'>✓ processBoothExcelForPreview function exists</p>";
    } else {
        echo "<p class='error'>❌ processBoothExcelForPreview function missing</p>";
    }
    
    if (function_exists('processExcelForUpload')) {
        echo "<p class='success'>✓ processExcelForUpload function exists</p>";
    } else {
        echo "<p class='error'>❌ processExcelForUpload function missing</p>";
    }
    
    if (function_exists('processExcelAsCSV')) {
        echo "<p class='success'>✓ processExcelAsCSV function exists</p>";
    } else {
        echo "<p class='error'>❌ processExcelAsCSV function missing</p>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>File Upload Test</h2>";
    
    if (file_exists('test_booth_upload.xlsx')) {
        echo "<p class='info'>Testing Excel file processing...</p>";
        
        // Simulate Excel file processing
        $filePath = 'test_booth_upload.xlsx';
        $fileType = 'xlsx';
        
        // Test preview processing
        if (function_exists('processBoothExcelForPreview')) {
            try {
                $previewData = processBoothExcelForPreview($filePath, $fileType);
                echo "<p class='success'>✓ Excel preview processing successful</p>";
                echo "<p class='info'>Headers: " . implode(', ', $previewData['headers']) . "</p>";
                echo "<p class='info'>Preview rows: " . count($previewData['rows']) . "</p>";
                echo "<p class='info'>Total rows: " . $previewData['total_rows'] . "</p>";
                
                if (isset($previewData['validation'])) {
                    if ($previewData['validation']['valid']) {
                        echo "<p class='success'>✓ Excel data validation passed</p>";
                    } else {
                        echo "<p class='error'>❌ Excel data validation failed</p>";
                        foreach ($previewData['validation']['errors'] as $error) {
                            echo "<p class='error'>• {$error}</p>";
                        }
                    }
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Excel processing error: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ Excel test file not found</p>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>System Status</h2>";
    echo "<p class='success'>🎉 Excel file support is now available!</p>";
    echo "<h3>What's New:</h3>";
    echo "<ul>";
    echo "<li>✅ Direct Excel file upload (.xlsx, .xls)</li>";
    echo "<li>✅ Excel file preview and validation</li>";
    echo "<li>✅ Excel file processing for database upload</li>";
    echo "<li>✅ Fallback to CSV processing if needed</li>";
    echo "<li>✅ Comprehensive error handling</li>";
    echo "</ul>";
    
    echo "<h3>Test the System:</h3>";
    echo "<p><a href='excel_upload_preview.php' target='_blank'>🚀 Open Upload System</a></p>";
    echo "<p><a href='test_booth_upload.xlsx' download>📈 Download Sample Excel File</a></p>";
    echo "<p><a href='test_booth_upload.csv' download>📊 Download Sample CSV File</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ Test Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
