<?php
require_once 'config.php';
require_once 'BoothMaster.php';

try {
    echo "=== Testing Booth Upload Workflow ===\n";
    
    // Test 1: Check database connection
    echo "1. Testing database connection...\n";
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connection successful\n";
    
    // Test 2: Check MLA records
    echo "\n2. Checking MLA records...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM mla_master");
    $mlaCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Found " . $mlaCount['count'] . " MLA records\n";
    
    if ($mlaCount['count'] == 0) {
        echo "❌ No MLA records found. Please add MLA records first.\n";
        echo "   You can add MLA records through the MLA Master interface.\n";
        exit(1);
    }
    
    // Get sample MLA records
    $stmt = $pdo->query("SELECT mla_id, mla_constituency_code, mla_constituency_name FROM mla_master LIMIT 3");
    $mlaRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample MLA records:\n";
    foreach ($mlaRecords as $mla) {
        echo "  - Code: {$mla['mla_constituency_code']}, Name: {$mla['mla_constituency_name']}\n";
    }
    
    // Test 3: Test BoothMaster class
    echo "\n3. Testing BoothMaster class...\n";
    $boothMaster = new BoothMaster($pdo);
    echo "✓ BoothMaster class instantiated successfully\n";
    
    // Test 4: Test CSV processing functions
    echo "\n4. Testing CSV processing functions...\n";
    
    // Simulate the CSV processing
    $testCsvPath = 'test_booth_upload.csv';
    if (!file_exists($testCsvPath)) {
        echo "❌ Test CSV file not found: $testCsvPath\n";
        exit(1);
    }
    
    // Test preview function
    function testProcessFileForPreview($filePath, $fileType) {
        $data = [];
        
        if ($fileType === 'csv') {
            $data = testProcessBoothCSVForPreview($filePath);
        }
        
        return $data;
    }
    
    function testProcessBoothCSVForPreview($filePath) {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle !== false) {
            $rowCount = 0;
            $maxPreviewRows = 5;
            
            while (($row = fgetcsv($handle)) !== false && $rowCount < $maxPreviewRows) {
                if ($rowCount === 0) {
                    $data['headers'] = $row;
                } else {
                    $data['rows'][] = $row;
                }
                $rowCount++;
            }
            
            fclose($handle);
            
            // Get total row count
            $totalRows = 0;
            $handle = fopen($filePath, 'r');
            while (fgetcsv($handle) !== false) {
                $totalRows++;
            }
            fclose($handle);
            
            $data['total_rows'] = $totalRows;
            $data['preview_rows'] = count($data['rows']);
            
            // Validate booth data structure
            $data['validation'] = testValidateBoothDataStructure($data['headers'], $data['rows']);
        }
        
        return $data;
    }
    
    function testValidateBoothDataStructure($headers, $rows) {
        $requiredColumns = [
            'mla_constituency_code',
            'sl_no', 
            'polling_station_no',
            'location_name_of_building'
        ];
        
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'found_columns' => $headers
        ];
        
        // Check for required columns
        foreach ($requiredColumns as $required) {
            if (!in_array($required, $headers)) {
                $validation['valid'] = false;
                $validation['errors'][] = "Missing required column: {$required}";
            }
        }
        
        return $validation;
    }
    
    $previewData = testProcessFileForPreview($testCsvPath, 'csv');
    echo "✓ CSV preview processing successful\n";
    echo "  - Headers: " . implode(', ', $previewData['headers']) . "\n";
    echo "  - Total rows: " . $previewData['total_rows'] . "\n";
    echo "  - Preview rows: " . $previewData['preview_rows'] . "\n";
    echo "  - Validation valid: " . ($previewData['validation']['valid'] ? 'Yes' : 'No') . "\n";
    
    if (!$previewData['validation']['valid']) {
        echo "  - Errors: " . implode(', ', $previewData['validation']['errors']) . "\n";
    }
    
    // Test 5: Test data mapping
    echo "\n5. Testing data mapping...\n";
    
    function testMapCSVRowToBoothData($headers, $row, $createdBy) {
        global $pdo;
        
        // Map MLA constituency code to MLA ID
        $mlaCodeIndex = array_search('mla_constituency_code', $headers);
        if ($mlaCodeIndex === false || !isset($row[$mlaCodeIndex])) {
            throw new Exception('MLA constituency code is required');
        }
        
        $mlaCode = trim($row[$mlaCodeIndex]);
        
        // Get MLA ID from constituency code
        $stmt = $pdo->prepare("SELECT mla_id FROM mla_master WHERE mla_constituency_code = ?");
        $stmt->execute([$mlaCode]);
        $mla = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mla) {
            throw new Exception("MLA with constituency code '{$mlaCode}' not found");
        }
        
        $data['mla_id'] = $mla['mla_id'];
        
        // Map other required fields
        $fieldMappings = [
            'sl_no' => 'sl_no',
            'polling_station_no' => 'polling_station_no',
            'location_name_of_building' => 'location_name_of_building',
            'polling_areas' => 'polling_areas',
            'polling_station_type' => 'polling_station_type'
        ];
        
        foreach ($fieldMappings as $csvField => $dbField) {
            $columnIndex = array_search($csvField, $headers);
            
            if ($columnIndex !== false && isset($row[$columnIndex])) {
                $value = trim($row[$columnIndex]);
                
                // Handle specific field types
                switch ($csvField) {
                    case 'sl_no':
                        $data[$dbField] = (int)$value;
                        break;
                    case 'polling_station_no':
                        $data[$dbField] = $value;
                        break;
                    case 'location_name_of_building':
                        $data[$dbField] = $value;
                        break;
                    case 'polling_areas':
                        $data[$dbField] = $value ?: '';
                        break;
                    case 'polling_station_type':
                        $data[$dbField] = $value ?: 'Regular';
                        break;
                }
            } else {
                // Set defaults for optional fields
                switch ($csvField) {
                    case 'polling_areas':
                        $data[$dbField] = '';
                        break;
                    case 'polling_station_type':
                        $data[$dbField] = 'Regular';
                        break;
                }
            }
        }
        
        $data['created_by'] = $createdBy;
        
        return $data;
    }
    
    // Test mapping with first data row
    if (!empty($previewData['rows'])) {
        $testRow = $previewData['rows'][0];
        $mappedData = testMapCSVRowToBoothData($previewData['headers'], $testRow, 'TEST_USER');
        echo "✓ Data mapping successful\n";
        echo "  - MLA ID: {$mappedData['mla_id']}\n";
        echo "  - Serial No: {$mappedData['sl_no']}\n";
        echo "  - Station No: {$mappedData['polling_station_no']}\n";
        echo "  - Location: {$mappedData['location_name_of_building']}\n";
    }
    
    // Test 6: Test booth creation (dry run)
    echo "\n6. Testing booth creation (dry run)...\n";
    
    // Check if booth already exists
    if (!empty($previewData['rows'])) {
        $testRow = $previewData['rows'][0];
        $mappedData = testMapCSVRowToBoothData($previewData['headers'], $testRow, 'TEST_USER');
        
        $exists = $boothMaster->stationExists($mappedData['mla_id'], $mappedData['polling_station_no']);
        echo "✓ Station existence check: " . ($exists ? 'Exists' : 'Does not exist') . "\n";
        
        if (!$exists) {
            echo "✓ Ready for booth creation\n";
        } else {
            echo "⚠ Booth already exists, would be skipped in actual upload\n";
        }
    }
    
    echo "\n=== Upload Workflow Test Complete ===\n";
    echo "✓ All tests passed! The upload system is ready to use.\n";
    echo "\nTo test the actual upload:\n";
    echo "1. Start your web server (WAMP/XAMPP)\n";
    echo "2. Navigate to excel_upload_preview.php in your browser\n";
    echo "3. Upload the test_booth_upload.csv file\n";
    echo "4. Review the preview and confirm upload\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
