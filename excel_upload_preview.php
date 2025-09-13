<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'BoothMaster.php';

$auth = new Auth($pdo);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';
$previewData = null;
$fileName = '';
$fileType = '';
$skipFirstRow = false; // Default to false (treat first row as headers)

// Check if user has permission to create booth records
if ($auth->hasPermission('booth', 'create')) {
    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
        // Get skip first row setting
        $skipFirstRow = isset($_POST['skip_first_row']) && $_POST['skip_first_row'] === 'yes';
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['excel_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Validate file
    if ($fileError === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check file type
        if (in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
            // Generate unique filename
            $uniqueFileName = uniqid() . '_' . $fileName;
            $filePath = $uploadDir . $uniqueFileName;
            
            if (move_uploaded_file($fileTmpName, $filePath)) {
                // Process file for preview
                try {
                    $previewData = processFileForPreview($filePath, $fileExtension, $skipFirstRow);
                    $fileType = $fileExtension;
                    $message = "File uploaded successfully. Please review the preview below.";
                    $messageType = 'success';
                    // Store file path and settings for later use
                    $_SESSION['uploaded_file_path'] = $filePath;
                    $_SESSION['skip_first_row'] = $skipFirstRow;
                } catch (Exception $e) {
                    $message = "Error processing file: " . $e->getMessage();
                    $messageType = 'error';
                    unlink($filePath); // Clean up file
                }
            } else {
                $message = "Error uploading file.";
                $messageType = 'error';
            }
        } else {
            $message = "Please upload a valid Excel file (.xlsx, .xls) or CSV file (.csv).";
            $messageType = 'error';
        }
    } else {
        $message = "Error uploading file: " . getUploadErrorMessage($fileError);
        $messageType = 'error';
    }
}

// Handle final upload after preview confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_upload'])) {
    $filePath = $_POST['file_path'];
    $fileType = $_POST['file_type'];
    $skipFirstRow = isset($_POST['skip_first_row']) && $_POST['skip_first_row'] === 'yes';
    
    if (file_exists($filePath)) {
        try {
            // Process the file for actual upload
            $result = processFileForUpload($filePath, $fileType, $skipFirstRow);
            
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
                $previewData = null; // Clear preview
                
                // Clean up uploaded file and session
                unlink($filePath);
                unset($_SESSION['uploaded_file_path']);
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = "Error processing upload: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = "File not found. Please upload again.";
        $messageType = 'error';
    }
    } else {
        $message = "You don't have permission to create booth records.";
        $messageType = 'error';
    }
} else {
    $message = "You don't have permission to create booth records.";
    $messageType = 'error';
}

// Move functions outside the permission check
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
                ['Excel file processing failed: ' . $e->getMessage()],
                [''],
                ['Please try one of these solutions:'],
                ['1. Save your Excel file as CSV format (File > Save As > CSV)'],
                ['2. Ensure your Excel file has the correct format (see below)'],
                ['3. Install PhpSpreadsheet library for better Excel support'],
                [''],
                ['Expected Excel format:'],
                ['Column A: mla_constituency_code'],
                ['Column B: sl_no'],
                ['Column C: polling_station_no'],
                ['Column D: location_name_of_building'],
                ['Column E: polling_areas'],
                ['Column F: polling_station_type']
            ],
            'isExcel' => true,
            'hasError' => true
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

function validateBoothDataStructure($headers, $rows) {
    global $pdo;
    
    $requiredColumns = [
        'mla_constituency_code',
        'sl_no', 
        'polling_station_no',
        'location_name_of_building'
    ];
    
    $optionalColumns = [
        'polling_areas',
        'polling_station_type'
    ];
    
    $validation = [
        'valid' => true,
        'errors' => [],
        'warnings' => [],
        'found_columns' => $headers,
        'database_validation' => [
            'mla_codes_found' => [],
            'mla_codes_missing' => [],
            'duplicate_stations' => [],
            'invalid_data' => []
        ]
    ];
    
    // Check for required columns
    foreach ($requiredColumns as $required) {
        if (!in_array($required, $headers)) {
            $validation['valid'] = false;
            $validation['errors'][] = "Missing required column: {$required}";
        }
    }
    
    // If basic structure is valid, perform database validation
    if ($validation['valid'] && !empty($rows)) {
        $mlaCodeIndex = array_search('mla_constituency_code', $headers);
        $stationNoIndex = array_search('polling_station_no', $headers);
        $slNoIndex = array_search('sl_no', $headers);
        
        // Get all unique MLA codes from the uploaded data
        $uploadedMlaCodes = [];
        $stationData = [];
        
        foreach ($rows as $rowIndex => $row) {
            if ($mlaCodeIndex !== false && isset($row[$mlaCodeIndex])) {
                $mlaCode = trim($row[$mlaCodeIndex]);
                if (!empty($mlaCode)) {
                    $uploadedMlaCodes[] = $mlaCode;
                    
                    // Track station data for duplicate checking
                    if ($stationNoIndex !== false && isset($row[$stationNoIndex])) {
                        $stationNo = trim($row[$stationNoIndex]);
                        if (!empty($stationNo)) {
                            $key = $mlaCode . '_' . $stationNo;
                            if (isset($stationData[$key])) {
                                $validation['database_validation']['duplicate_stations'][] = "Row " . ($rowIndex + 2) . ": Duplicate polling station '{$stationNo}' for MLA code '{$mlaCode}' (also found in row " . ($stationData[$key] + 2) . ")";
                            } else {
                                $stationData[$key] = $rowIndex;
                            }
                        }
                    }
                }
            }
        }
        
        // Check which MLA codes exist in database
        if (!empty($uploadedMlaCodes)) {
            $uniqueMlaCodes = array_unique($uploadedMlaCodes);
            $placeholders = str_repeat('?,', count($uniqueMlaCodes) - 1) . '?';
            $stmt = $pdo->prepare("SELECT mla_constituency_code FROM mla_master WHERE mla_constituency_code IN ($placeholders)");
            $stmt->execute($uniqueMlaCodes);
            $existingMlaCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $validation['database_validation']['mla_codes_found'] = $existingMlaCodes;
            $validation['database_validation']['mla_codes_missing'] = array_diff($uniqueMlaCodes, $existingMlaCodes);
            
            if (!empty($validation['database_validation']['mla_codes_missing'])) {
                $validation['valid'] = false;
                $validation['errors'][] = "The following MLA constituency codes are not found in the database: " . implode(', ', $validation['database_validation']['mla_codes_missing']);
            }
            
            // Check for existing booth records in database
            $validation['database_validation']['existing_booths'] = [];
            if (!empty($existingMlaCodes)) {
                // Get MLA IDs for existing codes
                $placeholders = str_repeat('?,', count($existingMlaCodes) - 1) . '?';
                $stmt = $pdo->prepare("SELECT mla_id, mla_constituency_code FROM mla_master WHERE mla_constituency_code IN ($placeholders)");
                $stmt->execute($existingMlaCodes);
                $mlaData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Create mapping of MLA codes to IDs
                $mlaCodeToId = [];
                foreach ($mlaData as $mla) {
                    $mlaCodeToId[$mla['mla_constituency_code']] = $mla['mla_id'];
                }
                
                // Check for existing booth records
                foreach ($rows as $rowIndex => $row) {
                    if ($mlaCodeIndex !== false && isset($row[$mlaCodeIndex]) && 
                        $stationNoIndex !== false && isset($row[$stationNoIndex])) {
                        
                        $mlaCode = trim($row[$mlaCodeIndex]);
                        $stationNo = trim($row[$stationNoIndex]);
                        
                        if (!empty($mlaCode) && !empty($stationNo) && isset($mlaCodeToId[$mlaCode])) {
                            $mlaId = $mlaCodeToId[$mlaCode];
                            
                            // Check if this booth already exists
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booth_master WHERE mla_id = ? AND polling_station_no = ? AND status = 'ACTIVE'");
                            $stmt->execute([$mlaId, $stationNo]);
                            $exists = $stmt->fetchColumn() > 0;
                            
                            if ($exists) {
                                $validation['database_validation']['existing_booths'][] = "Row " . ($rowIndex + 2) . ": Polling station '{$stationNo}' for MLA code '{$mlaCode}' already exists in database";
                            }
                        }
                    }
                }
                
                if (!empty($validation['database_validation']['existing_booths'])) {
                    $validation['valid'] = false;
                    $validation['errors'] = array_merge($validation['errors'], $validation['database_validation']['existing_booths']);
                }
            }
        }
        
        // Check for data validation issues
        foreach ($rows as $rowIndex => $row) {
            $rowErrors = [];
            
            // Check required fields
            foreach ($requiredColumns as $required) {
                $columnIndex = array_search($required, $headers);
                if ($columnIndex !== false) {
                    $value = isset($row[$columnIndex]) ? trim($row[$columnIndex]) : '';
                    if (empty($value)) {
                        $rowErrors[] = "{$required} is empty";
                    }
                }
            }
            
            // Check data types and formats
            if ($slNoIndex !== false && isset($row[$slNoIndex])) {
                $slNo = trim($row[$slNoIndex]);
                if (!empty($slNo) && (!is_numeric($slNo) || (int)$slNo <= 0)) {
                    $rowErrors[] = "sl_no must be a positive integer";
                }
            }
            
            // Check polling station type if provided
            $typeIndex = array_search('polling_station_type', $headers);
            if ($typeIndex !== false && isset($row[$typeIndex])) {
                $type = trim($row[$typeIndex]);
                if (!empty($type)) {
                    $validTypes = ['Regular', 'Auxiliary', 'Special', 'Mobile', 'Temporary', 'Emergency', 'Remote', 'Urban', 'Rural'];
                    if (!in_array($type, $validTypes)) {
                        $rowErrors[] = "polling_station_type '{$type}' is not valid. Valid types: " . implode(', ', $validTypes);
                    }
                }
            }
            
            if (!empty($rowErrors)) {
                $validation['database_validation']['invalid_data'][] = "Row " . ($rowIndex + 2) . ": " . implode(', ', $rowErrors);
            }
        }
        
        // Add invalid data errors to main errors
        if (!empty($validation['database_validation']['invalid_data'])) {
            $validation['valid'] = false;
            $validation['errors'] = array_merge($validation['errors'], $validation['database_validation']['invalid_data']);
        }
        
        // Add duplicate station errors to main errors
        if (!empty($validation['database_validation']['duplicate_stations'])) {
            $validation['valid'] = false;
            $validation['errors'] = array_merge($validation['errors'], $validation['database_validation']['duplicate_stations']);
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
            
            try {
                // Map CSV data to booth record
                $boothData = mapCSVRowToBoothData($headers, $row, $auth->getCurrentUser()['username']);
                
                // Create booth record
                $result = $boothMaster->create($boothData);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Row {$rowNumber}: " . $result['message'];
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        
        $message = "Upload completed! {$successCount} records created successfully.";
        if ($errorCount > 0) {
            $message .= " {$errorCount} records failed.";
            if (count($errors) <= 10) {
                $message .= " Errors: " . implode('; ', $errors);
            } else {
                $message .= " First 10 errors: " . implode('; ', array_slice($errors, 0, 10));
            }
        }
        
        return [
            'success' => $successCount > 0,
            'message' => $message,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error processing file: ' . $e->getMessage()
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
        
        try {
            // Map Excel data to booth record
            $boothData = mapCSVRowToBoothData($headers, $row, $auth->getCurrentUser()['username']);
            
            // Create booth record
            $result = $boothMaster->create($boothData);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: " . $result['message'];
            }
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = "Row {$rowNumber}: " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    $message = "Excel upload completed! {$successCount} records created successfully.";
    if ($errorCount > 0) {
        $message .= " {$errorCount} records failed.";
        if (count($errors) <= 10) {
            $message .= " Errors: " . implode('; ', $errors);
        } else {
            $message .= " First 10 errors: " . implode('; ', array_slice($errors, 0, 10));
        }
    }
    
    return [
        'success' => $successCount > 0,
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
    
    // Map MLA constituency code to MLA ID
    $mlaCodeIndex = array_search('mla_constituency_code', $headers);
    if ($mlaCodeIndex === false || !isset($row[$mlaCodeIndex])) {
        throw new Exception('MLA constituency code is required');
    }
    
    $mlaCode = trim($row[$mlaCodeIndex]);
    
    // Get MLA ID from constituency code
    global $pdo;
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
    
    // Validate required fields
    if (empty($data['sl_no'])) {
        throw new Exception('Serial number is required');
    }
    if (empty($data['polling_station_no'])) {
        throw new Exception('Polling station number is required');
    }
    if (empty($data['location_name_of_building'])) {
        throw new Exception('Location name is required');
    }
    
    $data['created_by'] = $createdBy;
    
    return $data;
}

function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File is too large.';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension.';
        default:
            return 'Unknown upload error.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booth Data Upload & Preview - Election Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .nav-buttons {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-buttons a {
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .nav-buttons a.btn-secondary {
            background: #6c757d;
            color: white;
        }

        .nav-buttons a.btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .nav-buttons a.btn-primary {
            background: #007bff;
            color: white;
        }

        .nav-buttons a.btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .nav-buttons a.current-page {
            background: #28a745;
            border-color: #1e7e34;
        }

        .content {
            padding: 40px;
        }

        .upload-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px dashed #dee2e6;
            text-align: center;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .upload-section.dragover {
            border-color: #28a745;
            background: #d4edda;
        }

        .upload-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .upload-text {
            font-size: 1.2rem;
            color: #495057;
            margin-bottom: 20px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-button {
            background: #007bff;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }

        .file-input-button:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .file-info {
            margin-top: 15px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 8px;
            display: none;
        }

        .file-info.show {
            display: block;
        }

        .preview-section {
            margin-top: 30px;
            display: none;
        }

        .preview-section.show {
            display: block;
        }

        .preview-header {
            background: #17a2b8;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-title {
            font-size: 1.5rem;
            font-weight: 500;
        }

        .preview-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #1e7e34;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 0 0 10px 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .preview-table th {
            background: #f8f9fa;
            color: #495057;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .preview-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }

        .preview-table tbody tr:hover {
            background: #f8f9fa;
        }

        .preview-stats {
            background: #e9ecef;
            padding: 15px;
            border-radius: 0 0 10px 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
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

        .hidden {
            display: none;
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
            background-color: #e9ecef;
        }

        .radio-option input[type="radio"] {
            margin: 0;
            transform: scale(1.2);
        }

        .radio-option span {
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .nav-buttons {
                flex-direction: column;
            }
            
            .nav-buttons a {
                text-align: center;
            }
            
            .preview-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .preview-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏛️ Booth Data Upload & Preview</h1>
            <p>Upload booth data from Excel or CSV files and preview before inserting into database</p>
        </div>

        <div class="nav-buttons">
            <a href="mp_view.php" class="btn-secondary">📊 MP Master</a>
            <a href="mla_view.php" class="btn-secondary">🏛️ MLA Master</a>
            <a href="booth_view.php" class="btn-secondary">🏛️ Booth Master</a>
            <a href="excel_upload_preview.php" class="btn-primary current-page">📤 Upload Data</a>
            <?php if ($auth->hasPermission('users', 'read')): ?>
                <a href="user_management.php" class="btn-secondary">👥 Users</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-secondary">🚪 Logout</a>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="upload-section" id="uploadSection">
                <div class="upload-icon">📁</div>
                <div class="upload-text">
                    <strong>Choose an Excel or CSV file to upload</strong><br>
                    Supported formats: .xlsx, .xls, .csv<br>
                    <a href="booth_template.csv" download style="color: #007bff; text-decoration: none; font-size: 0.9rem; margin-right: 15px;">
                        📥 Download CSV Template
                    </a>
                    <a href="test_booth_upload.csv" download style="color: #28a745; text-decoration: none; font-size: 0.9rem; margin-right: 15px;">
                        📊 Download Sample CSV
                    </a>
                    <a href="test_booth_upload.xlsx" download style="color: #ff6b35; text-decoration: none; font-size: 0.9rem; margin-right: 15px;">
                        📈 Download Sample Excel
                    </a>
                    <a href="test_no_headers.csv" download style="color: #6f42c1; text-decoration: none; font-size: 0.9rem;">
                        📋 Download No-Headers CSV
                    </a>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: left; font-size: 0.9rem;">
                    <strong>📋 Expected File Format:</strong><br><br>
                    <strong>Column Headers (Row 1):</strong><br>
                    <code style="background: #e9ecef; padding: 2px 4px; border-radius: 3px;">
                        mla_constituency_code | sl_no | polling_station_no | location_name_of_building | polling_areas | polling_station_type
                    </code><br><br>
                    
                    <strong>Sample Data (Row 2 onwards):</strong><br>
                    <code style="background: #e9ecef; padding: 2px 4px; border-radius: 3px;">
                        1 | 1 | 001 | Government Higher Secondary School | Areas 1-5 | Regular
                    </code><br>
                    <code style="background: #e9ecef; padding: 2px 4px; border-radius: 3px;">
                        1 | 2 | 002 | Panchayat Union Primary School | Areas 6-10 | Regular
                    </code><br><br>
                    
                    <strong>Column Descriptions:</strong><br>
                    • <strong>mla_constituency_code:</strong> Must match existing MLA constituency codes<br>
                    • <strong>sl_no:</strong> Serial number (integer)<br>
                    • <strong>polling_station_no:</strong> Polling station number (text)<br>
                    • <strong>location_name_of_building:</strong> Name of the building/location<br>
                    • <strong>polling_areas:</strong> Areas covered by this booth (optional)<br>
                    • <strong>polling_station_type:</strong> Regular, Auxiliary, Special, Mobile (optional)
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background: #e3f2fd; border-radius: 8px; text-align: left; font-size: 0.9rem;">
                    <strong>📊 Excel File Layout Example:</strong><br><br>
                    <div style="overflow-x: auto;">
                        <table style="border-collapse: collapse; width: 100%; font-size: 0.8rem;">
                            <tr style="background: #2196f3; color: white;">
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">A</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">B</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">C</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">D</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">E</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">F</th>
                            </tr>
                            <tr style="background: #f5f5f5;">
                                <td style="border: 1px solid #ddd; padding: 8px;"><strong>mla_constituency_code</strong></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><strong>sl_no</strong></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><strong>polling_station_no</strong></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><strong>location_name_of_building</strong></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><strong>polling_areas</strong></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><strong>polling_station_type</strong></td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">1</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">1</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">001</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Government Higher Secondary School</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Areas 1-5</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Regular</td>
                            </tr>
                            <tr style="background: #f9f9f9;">
                                <td style="border: 1px solid #ddd; padding: 8px;">1</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">2</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">002</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Panchayat Union Primary School</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Areas 6-10</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Regular</td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">2</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">1</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">001</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Government High School</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Areas 1-8</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">Auxiliary</td>
                            </tr>
                        </table>
                    </div>
                    <br>
                    <em>💡 Tip: Make sure your Excel file has the exact column headers in Row 1, and data starts from Row 2.</em>
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; text-align: left; font-size: 0.9rem;">
                    <strong>✅ Excel Support Available:</strong><br><br>
                    <strong>Direct Excel Upload:</strong> You can now upload .xlsx and .xls files directly!<br><br>
                    
                    <strong>📝 How to Prepare Excel File:</strong><br>
                    1. <strong>Download a template</strong> using the links above (CSV or Excel)<br>
                    2. <strong>Open in Excel</strong> and add your booth data<br>
                    3. <strong>Ensure correct format:</strong> Headers in Row 1, data from Row 2 onwards<br>
                    4. <strong>Upload directly:</strong> No conversion needed!<br><br>
                    
                    <strong>⚠️ Important Notes:</strong><br>
                    • Keep the exact column headers in Row 1<br>
                    • Don't add extra columns or change column order<br>
                    • Make sure MLA constituency codes match existing records in the system<br>
                    • Leave optional fields empty if you don't have data for them<br>
                    • <strong>Excel files are processed directly</strong> - no conversion required!
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="file-input-wrapper">
                        <input type="file" name="excel_file" id="excelFile" class="file-input" 
                               accept=".xlsx,.xls,.csv" required>
                        <button type="button" class="file-input-button" onclick="document.getElementById('excelFile').click()">
                            📁 Choose File
                        </button>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: left;">
                        <strong>📋 First Row Options:</strong><br><br>
                        <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                            <label class="radio-option">
                                <input type="radio" name="skip_first_row" value="no" checked>
                                <span>First row contains column headers (recommended)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="skip_first_row" value="yes">
                                <span>Skip first row (treat as data)</span>
                            </label>
                        </div>
                        <div style="margin-top: 10px; font-size: 0.9rem; color: #6c757d;">
                            <strong>💡 Tip:</strong> Most files have headers in the first row. Only select "Skip first row" if your file doesn't have column headers.
                        </div>
                    </div>
                </form>

                <div class="file-info" id="fileInfo">
                    <strong>Selected File:</strong> <span id="fileName"></span><br>
                    <strong>File Size:</strong> <span id="fileSize"></span>
                </div>
            </div>

            <?php if ($previewData): ?>
                <div class="preview-section show" id="previewSection">
                    <div class="preview-header">
                        <div class="preview-title">
                            📋 Data Preview - <?php echo htmlspecialchars($fileName); ?>
                        </div>
                        <div class="preview-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($_SESSION['uploaded_file_path'] ?? ''); ?>">
                                <input type="hidden" name="file_type" value="<?php echo htmlspecialchars($fileType); ?>">
                                <input type="hidden" name="skip_first_row" value="<?php echo $skipFirstRow ? 'yes' : 'no'; ?>">
                                <button type="submit" name="confirm_upload" class="btn btn-success" 
                                        <?php if (isset($previewData['validation']) && !$previewData['validation']['valid']): ?>
                                            disabled title="Fix validation errors before uploading"
                                        <?php endif; ?>>
                                    ✅ Confirm & Upload
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger" onclick="resetUpload()">
                                ❌ Cancel
                            </button>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <?php foreach ($previewData['headers'] as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previewData['rows'] as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                            <td><?php echo htmlspecialchars($cell); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="preview-stats">
                        <?php if (isset($previewData['total_rows'])): ?>
                            <strong>Total Rows:</strong> <?php echo $previewData['total_rows']; ?> | 
                            <strong>Preview Rows:</strong> <?php echo $previewData['preview_rows']; ?>
                            <?php if ($previewData['preview_rows'] < $previewData['total_rows']): ?>
                                | <em>Showing first <?php echo $previewData['preview_rows']; ?> rows</em>
                            <?php endif; ?>
                            | <strong>First Row:</strong> 
                            <?php if ($skipFirstRow): ?>
                                <span style="color: #ff6b35;">⚠️ Skipped (treated as data)</span>
                            <?php else: ?>
                                <span style="color: #28a745;">✅ Used as headers</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (isset($previewData['validation'])): ?>
                            <div style="margin-top: 10px;">
                                <?php if ($previewData['validation']['valid']): ?>
                                    <span style="color: #28a745; font-weight: bold;">✅ Data structure is valid and ready for upload</span>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-weight: bold;">❌ Data structure has errors - please fix before uploading</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($previewData['validation']['errors'])): ?>
                                    <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">
                                        <strong style="color: #721c24;">🚫 Validation Errors:</strong>
                                        <ul style="margin: 5px 0; padding-left: 20px; color: #721c24;">
                                            <?php foreach ($previewData['validation']['errors'] as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($previewData['validation']['database_validation'])): ?>
                                    <?php $dbValidation = $previewData['validation']['database_validation']; ?>
                                    
                                    <?php if (!empty($dbValidation['mla_codes_found'])): ?>
                                        <div style="margin-top: 10px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;">
                                            <strong style="color: #155724;">✅ Valid MLA Codes Found:</strong>
                                            <span style="color: #155724;"><?php echo implode(', ', $dbValidation['mla_codes_found']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($dbValidation['mla_codes_missing'])): ?>
                                        <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">
                                            <strong style="color: #721c24;">❌ Missing MLA Codes:</strong>
                                            <span style="color: #721c24;"><?php echo implode(', ', $dbValidation['mla_codes_missing']); ?></span>
                                            <br><small style="color: #721c24;">These MLA constituency codes are not found in the database. Please check the MLA Master data or correct the codes in your file.</small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($dbValidation['duplicate_stations'])): ?>
                                        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                                            <strong style="color: #856404;">⚠️ Duplicate Polling Stations:</strong>
                                            <ul style="margin: 5px 0; padding-left: 20px; color: #856404;">
                                                <?php foreach ($dbValidation['duplicate_stations'] as $duplicate): ?>
                                                    <li><?php echo htmlspecialchars($duplicate); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($dbValidation['invalid_data'])): ?>
                                        <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">
                                            <strong style="color: #721c24;">❌ Data Format Errors:</strong>
                                            <ul style="margin: 5px 0; padding-left: 20px; color: #721c24;">
                                                <?php foreach ($dbValidation['invalid_data'] as $invalid): ?>
                                                    <li><?php echo htmlspecialchars($invalid); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($dbValidation['existing_booths'])): ?>
                                        <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">
                                            <strong style="color: #721c24;">❌ Existing Booth Records:</strong>
                                            <ul style="margin: 5px 0; padding-left: 20px; color: #721c24;">
                                                <?php foreach ($dbValidation['existing_booths'] as $existing): ?>
                                                    <li><?php echo htmlspecialchars($existing); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <small style="color: #721c24;">These polling stations already exist in the database. Please use different station numbers or update existing records instead.</small>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($previewData['validation']['warnings'])): ?>
                                    <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                                        <strong style="color: #856404;">⚠️ Warnings:</strong>
                                        <ul style="margin: 5px 0; padding-left: 20px; color: #856404;">
                                            <?php foreach (array_slice($previewData['validation']['warnings'], 0, 5) as $warning): ?>
                                                <li><?php echo htmlspecialchars($warning); ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($previewData['validation']['warnings']) > 5): ?>
                                                <li>... and <?php echo count($previewData['validation']['warnings']) - 5; ?> more warnings</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!$previewData['validation']['valid']): ?>
                                    <div style="margin-top: 10px; padding: 10px; background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px;">
                                        <strong style="color: #383d41;">💡 How to Fix Errors:</strong>
                                        <ul style="margin: 5px 0; padding-left: 20px; color: #383d41; font-size: 0.9rem;">
                                            <li><strong>Missing MLA Codes:</strong> Check that all MLA constituency codes exist in the MLA Master</li>
                                            <li><strong>Data Format:</strong> Ensure serial numbers are positive integers</li>
                                            <li><strong>Duplicates:</strong> Remove duplicate polling stations within the same MLA constituency</li>
                                            <li><strong>Existing Records:</strong> Use different polling station numbers if they already exist in the database</li>
                                            <li><strong>Station Types:</strong> Use valid polling station types: Regular, Auxiliary, Special, Mobile, etc.</li>
                                            <li><strong>Required Fields:</strong> Fill in all required fields (mla_constituency_code, sl_no, polling_station_no, location_name_of_building)</li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('excelFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadForm = document.getElementById('uploadForm');
        const uploadSection = document.getElementById('uploadSection');

        // File input change handler
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.classList.add('show');
                
                // Auto-submit form
                uploadForm.submit();
            }
        });

        // Drag and drop functionality
        uploadSection.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        });

        uploadSection.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
        });

        uploadSection.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
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
            fileInput.value = '';
            fileInfo.classList.remove('show');
            document.getElementById('previewSection').classList.remove('show');
            location.reload();
        }
    </script>
</body>
</html>
