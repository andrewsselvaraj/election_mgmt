<?php
// Demo of the validation system
require_once 'config.php';
require_once 'BoothMaster.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Validation Demo</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} .info{color:blue;} .demo-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;}</style>";
echo "</head><body>";

echo "<h1>🔍 Booth Upload Validation Demo</h1>";

try {
    // Test 1: Valid CSV data
    echo "<div class='demo-section'>";
    echo "<h2>Test 1: Valid CSV Data</h2>";
    
    $validCsvPath = 'test_booth_upload.csv';
    if (file_exists($validCsvPath)) {
        $handle = fopen($validCsvPath, 'r');
        $headers = fgetcsv($handle);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        
        // Simulate validation
        function demoValidateBoothDataStructure($headers, $rows) {
            global $pdo;
            
            $validation = [
                'valid' => true,
                'errors' => [],
                'warnings' => [],
                'database_validation' => [
                    'mla_codes_found' => [],
                    'mla_codes_missing' => [],
                    'duplicate_stations' => [],
                    'invalid_data' => []
                ]
            ];
            
            // Check MLA codes
            $mlaCodes = [];
            foreach ($rows as $row) {
                $mlaCode = trim($row[0]); // mla_constituency_code is first column
                if (!empty($mlaCode)) {
                    $mlaCodes[] = $mlaCode;
                }
            }
            
            if (!empty($mlaCodes)) {
                $uniqueMlaCodes = array_unique($mlaCodes);
                $placeholders = str_repeat('?,', count($uniqueMlaCodes) - 1) . '?';
                $stmt = $pdo->prepare("SELECT mla_constituency_code FROM mla_master WHERE mla_constituency_code IN ($placeholders)");
                $stmt->execute($uniqueMlaCodes);
                $existingMlaCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $validation['database_validation']['mla_codes_found'] = $existingMlaCodes;
                $validation['database_validation']['mla_codes_missing'] = array_diff($uniqueMlaCodes, $existingMlaCodes);
                
                if (!empty($validation['database_validation']['mla_codes_missing'])) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "Missing MLA codes: " . implode(', ', $validation['database_validation']['mla_codes_missing']);
                }
            }
            
            return $validation;
        }
        
        $validation = demoValidateBoothDataStructure($headers, $rows);
        
        if ($validation['valid']) {
            echo "<p class='success'>✅ Valid CSV data - ready for upload</p>";
        } else {
            echo "<p class='error'>❌ Invalid CSV data</p>";
            foreach ($validation['errors'] as $error) {
                echo "<p class='error'>• {$error}</p>";
            }
        }
        
        if (!empty($validation['database_validation']['mla_codes_found'])) {
            echo "<p class='info'>✓ Valid MLA codes: " . implode(', ', $validation['database_validation']['mla_codes_found']) . "</p>";
        }
        
    } else {
        echo "<p class='warning'>⚠️ Valid test file not found</p>";
    }
    echo "</div>";
    
    // Test 2: Invalid CSV data
    echo "<div class='demo-section'>";
    echo "<h2>Test 2: Invalid CSV Data</h2>";
    
    $invalidCsvPath = 'test_invalid_booth_data.csv';
    if (file_exists($invalidCsvPath)) {
        $handle = fopen($invalidCsvPath, 'r');
        $headers = fgetcsv($handle);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        
        $validation = demoValidateBoothDataStructure($headers, $rows);
        
        if ($validation['valid']) {
            echo "<p class='success'>✅ Valid CSV data</p>";
        } else {
            echo "<p class='error'>❌ Invalid CSV data detected</p>";
            foreach ($validation['errors'] as $error) {
                echo "<p class='error'>• {$error}</p>";
            }
        }
        
        if (!empty($validation['database_validation']['mla_codes_missing'])) {
            echo "<p class='error'>❌ Missing MLA codes: " . implode(', ', $validation['database_validation']['mla_codes_missing']) . "</p>";
        }
        
    } else {
        echo "<p class='warning'>⚠️ Invalid test file not found</p>";
    }
    echo "</div>";
    
    // Test 3: Show available MLA codes
    echo "<div class='demo-section'>";
    echo "<h2>Test 3: Available MLA Codes in Database</h2>";
    
    $stmt = $pdo->query("SELECT mla_constituency_code, mla_constituency_name FROM mla_master LIMIT 10");
    $mlaRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($mlaRecords)) {
        echo "<p class='info'>Available MLA codes for testing:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>MLA Code</th><th>MLA Name</th></tr>";
        foreach ($mlaRecords as $mla) {
            echo "<tr><td>{$mla['mla_constituency_code']}</td><td>{$mla['mla_constituency_name']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No MLA records found in database</p>";
    }
    echo "</div>";
    
    echo "<div class='demo-section'>";
    echo "<h2>Demo Complete</h2>";
    echo "<p><a href='excel_upload_preview.php'>🚀 Try the Upload System</a></p>";
    echo "<p><a href='run_test.php'>🔧 Run System Test</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='demo-section'>";
    echo "<h2 class='error'>❌ Demo Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
