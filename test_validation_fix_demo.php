<?php
// Comprehensive test for validation fix
echo "<!DOCTYPE html>";
echo "<html><head><title>Validation Fix Demo</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .test-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;} .feature-list{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .step{background:#e9ecef;padding:10px;border-radius:5px;margin:10px 0;border-left:4px solid #007bff;} .test-result{background:#f8f9fa;padding:10px;border-radius:5px;margin:10px 0;} .pass{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;} .warning{color:orange;font-weight:bold;}</style>";
echo "</head><body>";

echo "<h1>🔧 Validation Fix Demo</h1>";

echo "<div class='test-section'>";
echo "<h2>✅ Problem Fixed: Missing Required Columns</h2>";
echo "<div class='feature-list'>";
echo "<h3>🐛 Original Problem:</h3>";
echo "<ul>";
echo "<li><strong>Error:</strong> 'Missing required columns: Sl.No, Polling station No., Location and name of building in which Polling Station located'</li>";
echo "<li><strong>Cause:</strong> Exact string matching was too strict</li>";
echo "<li><strong>Impact:</strong> Valid files were rejected due to minor header variations</li>";
echo "</ul>";

echo "<h3>🔧 Solution Implemented:</h3>";
echo "<ul>";
echo "<li><strong>Flexible Matching:</strong> Case-insensitive comparison with trimming</li>";
echo "<li><strong>Similarity Detection:</strong> Finds similar headers with 70%+ similarity</li>";
echo "<li><strong>Smart Suggestions:</strong> Provides helpful suggestions for missing columns</li>";
echo "<li><strong>Warning System:</strong> Warns about similar but not exact matches</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🧪 Test Cases</h2>";

// Test cases
$testCases = [
    [
        'name' => '✅ Exact Match (Should Pass)',
        'headers' => ['Sl.No', 'Polling station No.', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type'],
        'expected' => 'PASS'
    ],
    [
        'name' => '✅ Case Variation (Should Pass)',
        'headers' => ['sl.no', 'polling station no.', 'location and name of building in which polling station located', 'polling areas', 'polling station type'],
        'expected' => 'PASS'
    ],
    [
        'name' => '✅ Extra Spaces (Should Pass)',
        'headers' => [' Sl.No ', ' Polling station No. ', ' Location and name of building in which Polling Station located ', ' Polling Areas ', ' Polling Station Type '],
        'expected' => 'PASS'
    ],
    [
        'name' => '⚠️ Similar But Different (Should Warn)',
        'headers' => ['Sl.No', 'Polling Station No', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type'],
        'expected' => 'WARN'
    ],
    [
        'name' => '❌ Missing One Column (Should Fail)',
        'headers' => ['Sl.No', 'Polling station No.', 'Polling Areas', 'Polling Station Type'],
        'expected' => 'FAIL'
    ],
    [
        'name' => '❌ Completely Different (Should Fail)',
        'headers' => ['ID', 'Station', 'Address', 'Area', 'Type'],
        'expected' => 'FAIL'
    ]
];

// Include the validation function
function validateBoothDataStructure($headers, $rows) {
    $requiredColumns = [
        'Sl.No',
        'Polling station No.',
        'Location and name of building in which Polling Station located'
    ];
    
    $validation = [
        'valid' => true,
        'errors' => [],
        'warnings' => [],
        'database_validation' => [],
        'data_validation' => [],
        'duplicate_validation' => []
    ];
    
    // Check required columns with flexible matching
    $missingColumns = [];
    $foundColumns = [];
    
    foreach ($requiredColumns as $requiredCol) {
        $found = false;
        $bestMatch = '';
        $bestSimilarity = 0;
        
        foreach ($headers as $header) {
            // Exact match
            if (strcasecmp(trim($header), trim($requiredCol)) === 0) {
                $found = true;
                $foundColumns[] = $header;
                break;
            }
            
            // Similarity check for close matches
            $similarity = similar_text(strtolower(trim($header)), strtolower(trim($requiredCol)), $percent);
            if ($percent > $bestSimilarity && $percent > 70) {
                $bestSimilarity = $percent;
                $bestMatch = $header;
            }
        }
        
        if (!$found) {
            if ($bestMatch) {
                $validation['warnings'][] = "Column '$requiredCol' not found exactly, but found similar: '$bestMatch' (similarity: " . round($bestSimilarity, 1) . "%)";
                $foundColumns[] = $bestMatch;
            } else {
                $missingColumns[] = $requiredCol;
            }
        }
    }
    
    if (!empty($missingColumns)) {
        $validation['valid'] = false;
        $validation['errors'][] = 'Missing required columns: ' . implode(', ', $missingColumns);
        
        // Add suggestion for similar headers
        $suggestions = [];
        foreach ($missingColumns as $missing) {
            $similarHeaders = [];
            foreach ($headers as $header) {
                $similarity = similar_text(strtolower(trim($header)), strtolower(trim($missing)), $percent);
                if ($percent > 50) {
                    $similarHeaders[] = "'$header' (similarity: " . round($percent, 1) . "%)";
                }
            }
            if (!empty($similarHeaders)) {
                $suggestions[] = "For '$missing', similar headers found: " . implode(', ', $similarHeaders);
            }
        }
        if (!empty($suggestions)) {
            $validation['errors'][] = 'Suggestions: ' . implode('; ', $suggestions);
        }
    }
    
    return $validation;
}

foreach ($testCases as $testCase) {
    echo "<div class='test-result'>";
    echo "<h4>" . $testCase['name'] . "</h4>";
    echo "<p><strong>Headers:</strong> " . implode(', ', array_map('htmlspecialchars', $testCase['headers'])) . "</p>";
    
    $validation = validateBoothDataStructure($testCase['headers'], []);
    
    $actualResult = 'PASS';
    if (!$validation['valid']) {
        $actualResult = 'FAIL';
    } elseif (!empty($validation['warnings'])) {
        $actualResult = 'WARN';
    }
    
    $statusClass = $actualResult === 'PASS' ? 'pass' : ($actualResult === 'WARN' ? 'warning' : 'fail');
    $expectedClass = $testCase['expected'] === 'PASS' ? 'pass' : ($testCase['expected'] === 'WARN' ? 'warning' : 'fail');
    
    echo "<p><strong>Expected:</strong> <span class='$expectedClass'>" . $testCase['expected'] . "</span></p>";
    echo "<p><strong>Actual:</strong> <span class='$statusClass'>" . $actualResult . "</span></p>";
    
    if ($actualResult === $testCase['expected']) {
        echo "<p class='pass'>✅ Test PASSED</p>";
    } else {
        echo "<p class='fail'>❌ Test FAILED</p>";
    }
    
    if (!empty($validation['errors'])) {
        echo "<p><strong>Errors:</strong></p>";
        echo "<ul>";
        foreach ($validation['errors'] as $error) {
            echo "<li class='fail'>❌ " . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($validation['warnings'])) {
        echo "<p><strong>Warnings:</strong></p>";
        echo "<ul>";
        foreach ($validation['warnings'] as $warning) {
            echo "<li class='warning'>⚠️ " . htmlspecialchars($warning) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "</div>";
    echo "<hr>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>📁 Test Files Available</h2>";

$testFiles = [
    'test_booth_preview_format.csv' => 'Preview format with correct headers',
    'test_booth_upload_new_format.csv' => 'New format with correct headers',
    'test_any_polling_types.csv' => 'Any polling types test',
    'test_validation_errors.csv' => 'Validation errors test'
];

echo "<div class='feature-list'>";
foreach ($testFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='step'>";
        echo "<strong>📁 $file:</strong><br>";
        echo "<em>$description</em><br>";
        echo "<a href='$file' download style='color:#007bff;'>📥 Download</a>";
        echo "</div>";
    }
}
echo "</div>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🧪 How to Test the Fix</h2>";

echo "<div class='step'>";
echo "<h4>Step 1: Access MLA Detail Page</h4>";
echo "<p>Go to <a href='mla_detail.php?mp_id=1&mla_id=1' target='_blank'>mla_detail.php?mp_id=1&mla_id=1</a></p>";
echo "</div>";

echo "<div class='step'>";
echo "<h4>Step 2: Open Upload Section</h4>";
echo "<p>Click the <strong>'📤 Upload Data'</strong> button</p>";
echo "</div>";

echo "<div class='step'>";
echo "<h4>Step 3: Upload Test Files</h4>";
echo "<p>Try uploading these files to test the fix:</p>";
echo "<ul>";
echo "<li><strong>test_booth_preview_format.csv</strong> - Should work perfectly ✅</li>";
echo "<li><strong>test_booth_upload_new_format.csv</strong> - Should work perfectly ✅</li>";
echo "<li><strong>test_any_polling_types.csv</strong> - Should work perfectly ✅</li>";
echo "</ul>";
echo "</div>";

echo "<div class='step'>";
echo "<h4>Step 4: Expected Results</h4>";
echo "<p>You should see:</p>";
echo "<ul>";
echo "<li>✅ <strong>No validation errors</strong> for correct files</li>";
echo "<li>✅ <strong>Flexible matching</strong> handles case variations</li>";
echo "<li>✅ <strong>Smart suggestions</strong> for similar headers</li>";
echo "<li>✅ <strong>Clear error messages</strong> for missing columns</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🎯 Key Improvements</h2>";
echo "<div class='feature-list'>";
echo "<h3>✅ User Experience:</h3>";
echo "<ul>";
echo "<li><strong>Flexible Validation:</strong> Handles minor header variations</li>";
echo "<li><strong>Smart Suggestions:</strong> Helps users fix header issues</li>";
echo "<li><strong>Clear Messages:</strong> Better error and warning messages</li>";
echo "<li><strong>Case Insensitive:</strong> Works with any case combination</li>";
echo "</ul>";

echo "<h3>✅ Technical Benefits:</h3>";
echo "<ul>";
echo "<li><strong>Robust Matching:</strong> Uses similarity algorithms for close matches</li>";
echo "<li><strong>Error Prevention:</strong> Reduces false rejections</li>";
echo "<li><strong>User Guidance:</strong> Provides actionable suggestions</li>";
echo "<li><strong>Maintainable:</strong> Easy to extend with new column variations</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🚀 Ready to Test the Fix</h2>";
echo "<p><a href='mla_detail.php?mp_id=1&mla_id=1' target='_blank' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🏛️ Test MLA Detail with Fixed Validation</a></p>";
echo "<p><em>The 'Missing required columns' error should now be fixed! 🔧</em></p>";
echo "</div>";

echo "</body></html>";
?>
