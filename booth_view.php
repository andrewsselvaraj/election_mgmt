<?php
require_once 'config.php';
require_once 'BoothMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$boothMaster = new BoothMaster($pdo);
$currentUser = $auth->getCurrentUser();

// Handle file upload
$message = '';
$messageType = '';

// Get all booth records with MLA and MP details
$booths = $boothMaster->readAllWithDetails();

// Get statistics
$stats = $boothMaster->getStatistics();

// Include processing functions
function processFileForPreview($filePath, $fileType, $skipFirstRow = false) {
    $data = [];
    
    if ($fileType === 'csv') {
        $data = processBoothCSVForPreview($filePath, $skipFirstRow);
    } else {
        // For Excel files, try to process them directly
        $data = processBoothExcelForPreview($filePath, $fileType, $skipFirstRow);
    }
    
    return $data;
}

function processBoothCSVForPreview($filePath, $skipFirstRow = false) {
    $data = [];
    $handle = fopen($filePath, 'r');
    
    if ($handle !== false) {
        $rowCount = 0;
        $maxPreviewRows = 20; // Limit preview to first 20 rows
        
        while (($row = fgetcsv($handle)) !== false && $rowCount < $maxPreviewRows) {
            if ($skipFirstRow) {
                // Skip first row, treat all rows as data
                if ($rowCount === 0) {
                    // Generate default headers
                    $data['headers'] = ['mla_constituency_code', 'sl_no', 'polling_station_no', 'location_name_of_building', 'polling_areas', 'polling_station_type'];
                } else {
                    $data['rows'][] = $row;
                }
            } else {
                // Treat first row as headers
                if ($rowCount === 0) {
                    $data['headers'] = $row;
                } else {
                    $data['rows'][] = $row;
                }
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
        $data['validation'] = validateBoothDataStructure($data['headers'], $data['rows']);
    }
    
    return $data;
}

function processBoothExcelForPreview($filePath, $fileType, $skipFirstRow = false) {
    $data = [];
    
    try {
        // Check if PhpSpreadsheet is available
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            $data = processExcelWithPhpSpreadsheet($filePath, $skipFirstRow);
        } else {
            // Fallback: Try to read as CSV (some Excel files can be read as CSV)
            $data = processExcelAsCSV($filePath, $skipFirstRow);
        }
    } catch (Exception $e) {
        // If Excel processing fails, show helpful message
        $data = [
            'headers' => ['Error'],
            'rows' => [
                ['Excel processing failed: ' . $e->getMessage()],
                ['Please convert to CSV format or install PhpSpreadsheet library']
            ],
            'total_rows' => 0,
            'preview_rows' => 2,
            'validation' => ['valid' => false, 'errors' => ['Excel processing failed']]
        ];
    }
    
    return $data;
}

function processExcelAsCSV($filePath, $skipFirstRow = false) {
    // Try to read Excel file as CSV (works for simple Excel files)
    $handle = fopen($filePath, 'r');
    
    if ($handle === false) {
        throw new Exception('Could not open Excel file');
    }
    
    $data = [];
    $rowCount = 0;
    $maxPreviewRows = 20;
    
    while (($row = fgetcsv($handle)) !== false && $rowCount < $maxPreviewRows) {
        if ($skipFirstRow) {
            // Skip first row, treat all rows as data
            if ($rowCount === 0) {
                // Generate default headers
                $data['headers'] = ['mla_constituency_code', 'sl_no', 'polling_station_no', 'location_name_of_building', 'polling_areas', 'polling_station_type'];
            } else {
                $data['rows'][] = $row;
            }
        } else {
            // Treat first row as headers
            if ($rowCount === 0) {
                $data['headers'] = $row;
            } else {
                $data['rows'][] = $row;
            }
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
    $data['validation'] = validateBoothDataStructure($data['headers'], $data['rows']);
    
    return $data;
}

function processExcelWithPhpSpreadsheet($filePath, $skipFirstRow = false) {
    // This would use PhpSpreadsheet library for proper Excel processing
    // For now, fall back to CSV method
    return processExcelAsCSV($filePath, $skipFirstRow);
}

function validateBoothDataStructure($headers, $rows) {
    global $pdo;
    
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
        'database_validation' => [],
        'data_validation' => [],
        'duplicate_validation' => []
    ];
    
    // Check required columns
    $missingColumns = [];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $headers)) {
            $missingColumns[] = $col;
        }
    }
    
    if (!empty($missingColumns)) {
        $validation['valid'] = false;
        $validation['errors'][] = 'Missing required columns: ' . implode(', ', $missingColumns);
    }
    
    // Data validation
    if (!empty($rows)) {
        $rowErrors = [];
        $duplicateStations = [];
        $stationTracker = [];
        
        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;
            $rowErrors[$rowNum] = [];
            
            // Check if row has enough columns
            if (count($row) < count($headers)) {
                $rowErrors[$rowNum][] = "Insufficient data columns (expected " . count($headers) . ", got " . count($row) . ")";
                continue;
            }
            
            // Validate MLA constituency code
            $mlaCode = trim($row[0] ?? '');
            if (empty($mlaCode)) {
                $rowErrors[$rowNum][] = "MLA constituency code is required";
            } elseif (!is_numeric($mlaCode)) {
                $rowErrors[$rowNum][] = "MLA constituency code must be numeric";
            }
            
            // Validate serial number
            $slNo = trim($row[1] ?? '');
            if (empty($slNo)) {
                $rowErrors[$rowNum][] = "Serial number is required";
            } elseif (!is_numeric($slNo) || $slNo <= 0) {
                $rowErrors[$rowNum][] = "Serial number must be a positive integer";
            }
            
            // Validate polling station number
            $stationNo = trim($row[2] ?? '');
            if (empty($stationNo)) {
                $rowErrors[$rowNum][] = "Polling station number is required";
            }
            
            // Validate location name
            $location = trim($row[3] ?? '');
            if (empty($location)) {
                $rowErrors[$rowNum][] = "Location name is required";
            }
            
            // Validate polling station type
            $stationType = trim($row[5] ?? '');
            if (!empty($stationType)) {
                $validTypes = ['Regular', 'Auxiliary', 'Special', 'Mobile'];
                if (!in_array($stationType, $validTypes)) {
                    $rowErrors[$rowNum][] = "Invalid polling station type. Must be one of: " . implode(', ', $validTypes);
                }
            }
            
            // Check for duplicate stations within the file
            if (!empty($mlaCode) && !empty($stationNo)) {
                $stationKey = $mlaCode . '_' . $stationNo;
                if (isset($stationTracker[$stationKey])) {
                    $duplicateStations[] = "Row $rowNum: Duplicate polling station $stationNo for MLA $mlaCode (also found in row " . $stationTracker[$stationKey] . ")";
                } else {
                    $stationTracker[$stationKey] = $rowNum;
                }
            }
        }
        
        // Add row errors to validation
        foreach ($rowErrors as $rowNum => $errors) {
            if (!empty($errors)) {
                $validation['data_validation'][] = "Row $rowNum: " . implode(', ', $errors);
                $validation['valid'] = false;
            }
        }
        
        // Add duplicate errors
        if (!empty($duplicateStations)) {
            $validation['duplicate_validation'] = $duplicateStations;
            $validation['valid'] = false;
        }
        
        // Database validation - Check MLA constituency codes exist
        $mlaCodes = array_unique(array_filter(array_column($rows, 0), function($code) {
            return !empty(trim($code)) && is_numeric(trim($code));
        }));
        
        if (!empty($mlaCodes)) {
            $existingMlaCodes = [];
            try {
                $stmt = $pdo->prepare("SELECT mla_constituency_code FROM mla_master WHERE mla_constituency_code IN (" . implode(',', array_fill(0, count($mlaCodes), '?')) . ")");
                $stmt->execute($mlaCodes);
                $existingMlaCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {
                $validation['database_validation'][] = 'Error checking MLA codes: ' . $e->getMessage();
            }
            
            $missingMlaCodes = array_diff($mlaCodes, $existingMlaCodes);
            if (!empty($missingMlaCodes)) {
                $validation['valid'] = false;
                $validation['errors'][] = 'MLA constituency codes not found in database: ' . implode(', ', $missingMlaCodes);
            }
            
            // Check for existing booth records in database
            $existingBooths = [];
            try {
                foreach ($mlaCodes as $mlaCode) {
                    $stmt = $pdo->prepare("SELECT polling_station_no FROM booth_master b 
                                         JOIN mla_master m ON b.mla_id = m.mla_id 
                                         WHERE m.mla_constituency_code = ?");
                    $stmt->execute([$mlaCode]);
                    $existingBooths[$mlaCode] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            } catch (Exception $e) {
                $validation['database_validation'][] = 'Error checking existing booths: ' . $e->getMessage();
            }
            
            // Check for conflicts with existing booth records
            $conflictStations = [];
            foreach ($rows as $index => $row) {
                $rowNum = $index + 1;
                $mlaCode = trim($row[0] ?? '');
                $stationNo = trim($row[2] ?? '');
                
                if (!empty($mlaCode) && !empty($stationNo) && isset($existingBooths[$mlaCode])) {
                    if (in_array($stationNo, $existingBooths[$mlaCode])) {
                        $conflictStations[] = "Row $rowNum: Polling station $stationNo for MLA $mlaCode already exists in database";
                    }
                }
            }
            
            if (!empty($conflictStations)) {
                $validation['database_validation'] = array_merge($validation['database_validation'], $conflictStations);
                $validation['warnings'][] = 'Some polling stations already exist in database and will be skipped';
            }
        }
    }
    
    return $validation;
}

function processFileForUpload($filePath, $fileType, $skipFirstRow = false) {
    global $pdo, $auth;
    
    try {
        if ($fileType === 'csv') {
            return processCSVForUpload($filePath, $skipFirstRow);
        } else {
            return processExcelForUpload($filePath, $fileType, $skipFirstRow);
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error processing file: ' . $e->getMessage()
        ];
    }
}

function processCSVForUpload($filePath, $skipFirstRow = false) {
    global $pdo, $auth;
    
    try {
        $boothMaster = new BoothMaster($pdo);
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            return [
                'success' => false,
                'message' => 'Could not open file for processing.'
            ];
        }
        
        if ($skipFirstRow) {
            // Skip first row, use default headers
            $headers = ['mla_constituency_code', 'sl_no', 'polling_station_no', 'location_name_of_building', 'polling_areas', 'polling_station_type'];
            // Skip the first data row
            fgetcsv($handle);
        } else {
            // Read headers from first row
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return [
                    'success' => false,
                    'message' => 'Could not read headers from file.'
                ];
            }
        }
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $rowNumber = 1; // Start from 1 (header row)
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            if (count($row) < count($headers)) {
                $errors[] = "Row $rowNumber: Insufficient data columns";
                $errorCount++;
                continue;
            }
            
            $boothData = mapCSVRowToBoothData($headers, $row, $auth->getCurrentUser()['user_id']);
            
            if ($boothData) {
                $result = $boothMaster->create($boothData);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errors[] = "Row $rowNumber: " . $result['message'];
                    $errorCount++;
                }
            } else {
                $errors[] = "Row $rowNumber: Invalid data format";
                $errorCount++;
            }
        }
        
        fclose($handle);
        
        $message = "Upload completed. Success: $successCount, Errors: $errorCount";
        if (!empty($errors)) {
            $message .= "\nErrors:\n" . implode("\n", array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $message .= "\n... and " . (count($errors) - 10) . " more errors";
            }
        }
        
        return [
            'success' => $errorCount === 0,
            'message' => $message,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error processing CSV file: ' . $e->getMessage()
        ];
    }
}

function processExcelForUpload($filePath, $fileType, $skipFirstRow = false) {
    global $pdo, $auth;
    
    try {
        // Check if PhpSpreadsheet is available
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            return processExcelWithPhpSpreadsheetForUpload($filePath, $skipFirstRow);
        } else {
            // Fallback: Try to read as CSV
            return processExcelAsCSVForUpload($filePath, $skipFirstRow);
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Excel file processing failed: ' . $e->getMessage() . '. Please convert to CSV format.'
        ];
    }
}

function processExcelAsCSVForUpload($filePath, $skipFirstRow = false) {
    global $pdo, $auth;
    
    $boothMaster = new BoothMaster($pdo);
    $handle = fopen($filePath, 'r');
    
    if ($handle === false) {
        return [
            'success' => false,
            'message' => 'Could not open Excel file for processing.'
        ];
    }
    
    if ($skipFirstRow) {
        // Skip first row, use default headers
        $headers = ['mla_constituency_code', 'sl_no', 'polling_station_no', 'location_name_of_building', 'polling_areas', 'polling_station_type'];
        // Skip the first data row
        fgetcsv($handle);
    } else {
        // Read headers from first row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'Could not read headers from Excel file.'
            ];
        }
    }
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $rowNumber = 1; // Start from 1 (header row)
    
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        
        if (count($row) < count($headers)) {
            $errors[] = "Row $rowNumber: Insufficient data columns";
            $errorCount++;
            continue;
        }
        
        $boothData = mapCSVRowToBoothData($headers, $row, $auth->getCurrentUser()['user_id']);
        
        if ($boothData) {
            $result = $boothMaster->create($boothData);
            if ($result['success']) {
                $successCount++;
            } else {
                $errors[] = "Row $rowNumber: " . $result['message'];
                $errorCount++;
            }
        } else {
            $errors[] = "Row $rowNumber: Invalid data format";
            $errorCount++;
        }
    }
    
    fclose($handle);
    
    $message = "Upload completed. Success: $successCount, Errors: $errorCount";
    if (!empty($errors)) {
        $message .= "\nErrors:\n" . implode("\n", array_slice($errors, 0, 10));
        if (count($errors) > 10) {
            $message .= "\n... and " . (count($errors) - 10) . " more errors";
        }
    }
    
    return [
        'success' => $errorCount === 0,
        'message' => $message,
        'success_count' => $successCount,
        'error_count' => $errorCount
    ];
}

function processExcelWithPhpSpreadsheetForUpload($filePath, $skipFirstRow = false) {
    // This would use PhpSpreadsheet library for proper Excel processing
    // For now, fall back to CSV method
    return processExcelAsCSVForUpload($filePath, $skipFirstRow);
}

function mapCSVRowToBoothData($headers, $row, $createdBy) {
    // Create mapping array
    $data = [];
    
    // Map each column to its corresponding field
    for ($i = 0; $i < count($headers); $i++) {
        $header = strtolower(trim($headers[$i]));
        $value = isset($row[$i]) ? trim($row[$i]) : '';
        
        switch ($header) {
            case 'mla_constituency_code':
                $data['mla_id'] = $value;
                break;
            case 'sl_no':
                $data['sl_no'] = $value;
                break;
            case 'polling_station_no':
                $data['polling_station_no'] = $value;
                break;
            case 'location_name_of_building':
                $data['location_name_of_building'] = $value;
                break;
            case 'polling_areas':
                $data['polling_areas'] = $value;
                break;
            case 'polling_station_type':
                $data['polling_station_type'] = $value;
                break;
        }
    }
    
    // Validate required fields
    if (empty($data['mla_id']) || empty($data['sl_no']) || empty($data['polling_station_no']) || empty($data['location_name_of_building'])) {
        return false;
    }
    
    // Set default values for optional fields
    if (empty($data['polling_areas'])) {
        $data['polling_areas'] = '';
    }
    if (empty($data['polling_station_type'])) {
        $data['polling_station_type'] = 'Regular';
    }
    
    $data['created_by'] = $createdBy;
    
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booth Master - View Records</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .upload-content {
            display: none;
        }
        
        .upload-info {
            margin-bottom: 20px;
        }
        
        .download-links {
            margin: 10px 0;
        }
        
        .download-links a {
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin: 10px 0;
        }
        
        .file-input {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .file-input-button:hover {
            background: #0056b3;
        }
        
        .radio-options {
            margin: 20px 0;
            padding: 15px;
            background: #e9ecef;
            border-radius: 8px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }
        
        .radio-option:hover {
            background-color: #f8f9fa;
        }
        
        .radio-option input[type="radio"] {
            margin: 0;
            transform: scale(1.2);
        }
        
        .radio-option span {
            font-weight: 500;
        }
        
        .radio-tip {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .file-info {
            margin-top: 15px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .preview-section {
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
        }
        
        .preview-title {
            font-size: 18px;
            font-weight: bold;
        }
        
        .preview-actions {
            display: flex;
            gap: 10px;
        }
        
        .preview-content {
            padding: 20px;
        }
        
        .preview-table-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .preview-table th,
        .preview-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .preview-table th {
            background: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        
        .preview-stats {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .loading-indicator {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .loading-indicator div:first-child {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>🏛️ Booth Master - View Records</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_view.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="booth_view.php" class="btn btn-primary current-page">🏛️ Booth Master</a>
                <a href="voter_view.php" class="btn btn-secondary">🗳️ Voter Information</a>
                <?php if ($auth->hasPermission('users', 'read')): ?>
                    <a href="user_management.php" class="btn btn-warning">👥 Users</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Booths</h3>
                <p class="stat-number"><?php echo $stats['total_booths']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Records</h3>
                <p class="stat-number"><?php echo $stats['active_records']; ?></p>
            </div>
            <div class="stat-card">
                <h3>MLA Constituencies</h3>
                <p class="stat-number"><?php echo $stats['total_mla_constituencies']; ?></p>
            </div>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>" style="margin: 20px 0;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>


        <!-- Search and Filter -->
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Search by booth details, MLA, or MP...">
                <button type="button" id="search-btn" class="btn btn-primary">🔍 Search</button>
                <button type="button" id="clear-search" class="btn btn-secondary">Clear</button>
            </div>
        </div>

        <!-- Records Table -->
        <div class="table-container">
            <table class="data-table" id="booth-table">
                <thead>
                    <tr>
                        <th>Sl No</th>
                        <th>Polling Station No</th>
                        <th>Location/Building</th>
                        <th>Polling Areas</th>
                        <th>Station Type</th>
                        <th>Notes</th>
                        <th>MLA Constituency</th>
                        <th>MP Constituency</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($booths)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No booth records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($booths as $booth): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booth['sl_no']); ?></td>
                                <td><?php echo htmlspecialchars($booth['polling_station_no']); ?></td>
                                <td><?php echo htmlspecialchars($booth['location_name_of_building']); ?></td>
                                <td><?php echo htmlspecialchars($booth['polling_areas']); ?></td>
                                <td><?php echo htmlspecialchars($booth['polling_station_type']); ?></td>
                                <td><?php echo htmlspecialchars($booth['notes'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($booth['mla_constituency_name']); ?></td>
                                <td><?php echo htmlspecialchars($booth['mp_constituency_name']); ?></td>
                                <td>
                                    <a href="booth_detail.php?mp_id=<?php echo $booth['mp_id'] ?? ''; ?>&mla_id=<?php echo $booth['mla_id']; ?>&booth_id=<?php echo $booth['booth_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php if ($auth->hasPermission('booth', 'update')): ?>
                                        <button onclick="editBooth('<?php echo $booth['booth_id']; ?>')" class="btn btn-sm btn-warning">Edit</button>
                                    <?php endif; ?>
                                    <?php if ($auth->hasPermission('booth', 'delete')): ?>
                                        <button onclick="deleteBooth('<?php echo $booth['booth_id']; ?>')" class="btn btn-sm btn-danger">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <button id="prev-page" class="btn btn-secondary">Previous</button>
            <span id="page-info">Page 1 of 1</span>
            <button id="next-page" class="btn btn-secondary">Next</button>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('search-btn').addEventListener('click', function() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#booth-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Clear search
        document.getElementById('clear-search').addEventListener('click', function() {
            document.getElementById('search-input').value = '';
            const rows = document.querySelectorAll('#booth-table tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        });

        // Edit function
        function editBooth(boothId) {
            window.location.href = 'booth_edit.php?booth_id=' + boothId;
        }

        // Delete function
        function deleteBooth(boothId) {
            if (confirm('Are you sure you want to delete this booth record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'booth_delete.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'booth_id';
                input.value = boothId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Upload functionality
        document.getElementById('toggleUpload')?.addEventListener('click', function() {
            const uploadContent = document.getElementById('uploadContent');
            const toggleButton = document.getElementById('toggleUpload');
            
            if (uploadContent.style.display === 'none') {
                uploadContent.style.display = 'block';
                toggleButton.textContent = '📤 Hide Upload';
            } else {
                uploadContent.style.display = 'none';
                toggleButton.textContent = '📤 Upload Data';
            }
        });

        // File input handling
        document.getElementById('excelFile')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                const fileInfo = document.getElementById('fileInfo');
                const loadingIndicator = document.getElementById('loadingIndicator');
                
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                
                // Show loading indicator
                loadingIndicator.style.display = 'block';
                
                // Auto-submit form to show preview
                document.getElementById('uploadForm').submit();
            }
        });

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function resetUpload() {
            // Reset form
            document.getElementById('uploadForm').reset();
            document.getElementById('fileInfo').style.display = 'none';
            document.getElementById('loadingIndicator').style.display = 'none';
            
            // Hide preview if exists
            const previewSection = document.getElementById('previewSection');
            if (previewSection) {
                previewSection.remove();
            }
            
            // Hide upload content
            document.getElementById('uploadContent').style.display = 'none';
            document.getElementById('toggleUpload').textContent = '📤 Upload Data';
        }

    </script>
</body>
</html>
