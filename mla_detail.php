<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'MLAMaster.php';
require_once 'BoothMaster.php';
require_once 'Auth.php';
require_once 'dynamic_breadcrumb.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$mpId = $_GET['mp_id'] ?? null;
$mlaId = $_GET['mla_id'] ?? null;

if (!$mpId || !$mlaId) {
    header('Location: index.php');
    exit;
}

// Initialize master classes early
$mpMaster = new MPMaster($pdo);
$mlaMaster = new MLAMaster($pdo);
$boothMaster = new BoothMaster($pdo);

$message = '';
$messageType = '';
$previewData = null;
$fileName = '';
$fileType = '';
$skipFirstRow = false;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    // Get skip first row setting
    $skipFirstRow = isset($_POST['skip_first_row']) && $_POST['skip_first_row'] === 'yes';
    
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileTmpName = $_FILES['excel_file']['tmp_name'];
    $fileName = $_FILES['excel_file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
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
                // Store file path for later use
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
        $message = "Invalid file type. Please upload CSV, XLS, or XLSX files.";
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
                // Clean up uploaded file
                unlink($filePath);
                unset($_SESSION['uploaded_file_path']);
                unset($_SESSION['skip_first_row']);
                // Refresh booth records
                $boothRecords = $boothMaster->getByMLAId($mlaId);
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = "Error processing upload: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = "Uploaded file not found. Please try uploading again.";
        $messageType = 'error';
    }
}

$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);

// Get MP details
$mpData = $mpMaster->readById($mpId);
if (!$mpData) {
    header('Location: index.php');
    exit;
}

// Get MLA details
$mlaData = $mlaMaster->readById($mlaId);
if (!$mlaData || $mlaData['mp_id'] != $mpId) {
    header('Location: mp_detail.php?mp_id=' . $mpId);
    exit;
}

// Get Booths for this MLA
$boothRecords = $boothMaster->getByMLAId($mlaId);
$currentUser = $auth->getCurrentUser();

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
                        $data['headers'] = ['Sl.No', 'Polling station No.', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type', 'Notes'];
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
            // Excel files cannot be processed without PhpSpreadsheet
            throw new Exception('Excel files require PhpSpreadsheet library for processing. Please convert to CSV format or install PhpSpreadsheet.');
        }
    } catch (Exception $e) {
        // If Excel processing fails, show helpful message
        $data = [
            'headers' => ['Error', 'Solution'],
            'rows' => [
                ['Excel processing failed', 'Convert to CSV format'],
                ['Missing PhpSpreadsheet library', 'Install PhpSpreadsheet or use CSV'],
                ['File type: ' . $fileType, 'Supported: CSV files only']
            ],
            'total_rows' => 3,
            'preview_rows' => 3,
            'validation' => [
                'valid' => false, 
                'errors' => ['Excel processing not available'],
                'warnings' => ['Please convert to CSV format for processing']
            ]
        ];
    }
    
    return $data;
}

function processExcelAsCSV($filePath, $skipFirstRow = false) {
    // Excel files cannot be read as CSV directly - they are binary files
    // This function should not be called for Excel files
    throw new Exception('Excel files cannot be processed without PhpSpreadsheet library. Please convert to CSV format or install PhpSpreadsheet.');
}

function processExcelWithPhpSpreadsheet($filePath, $skipFirstRow = false) {
    // Try to use PhpSpreadsheet if available
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];
            
            $rowCount = 0;
            $maxPreviewRows = 20;
            
            foreach ($worksheet->getRowIterator() as $row) {
                if ($rowCount >= $maxPreviewRows) break;
                
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }
                
                if ($skipFirstRow) {
                    if ($rowCount === 0) {
                        $data['headers'] = ['Sl.No', 'Polling station No.', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type', 'Notes'];
                    } else {
                        $data['rows'][] = $rowData;
                    }
                } else {
                    if ($rowCount === 0) {
                        $data['headers'] = $rowData;
                    } else {
                        $data['rows'][] = $rowData;
                    }
                }
                $rowCount++;
            }
            
            // Get total row count
            $data['total_rows'] = $worksheet->getHighestRow();
            $data['preview_rows'] = count($data['rows']);
            
            // Validate booth data structure
            $data['validation'] = validateBoothDataStructure($data['headers'], $data['rows']);
            
            return $data;
        } catch (Exception $e) {
            throw new Exception('PhpSpreadsheet error: ' . $e->getMessage());
        }
    } else {
        // Fallback to basic Excel processing
        return processExcelBasic($filePath, $skipFirstRow);
    }
}

function processExcelBasic($filePath, $skipFirstRow = false) {
    // Basic Excel processing using PHP's built-in functions
    // This is a simplified approach for basic Excel files
    
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($fileExtension === 'xlsx') {
        return processXlsxBasic($filePath, $skipFirstRow);
    } elseif ($fileExtension === 'xls') {
        return processXlsBasic($filePath, $skipFirstRow);
    } else {
        throw new Exception('Unsupported Excel file format: ' . $fileExtension);
    }
}

function processXlsxBasic($filePath, $skipFirstRow = false) {
    // Basic XLSX processing using ZIP extraction
    // This is a simplified approach that works for basic Excel files
    
    try {
        // XLSX files are ZIP archives
        $zip = new ZipArchive();
        if ($zip->open($filePath) === TRUE) {
            // Read the shared strings
            $sharedStrings = [];
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml) {
                $xml = simplexml_load_string($sharedStringsXml);
                if ($xml) {
                    foreach ($xml->si as $si) {
                        $sharedStrings[] = (string)$si->t;
                    }
                }
            }
            
            // Read the worksheet data
            $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (!$worksheetXml) {
                $zip->close();
                throw new Exception('Could not read worksheet data from XLSX file');
            }
            
            $xml = simplexml_load_string($worksheetXml);
            if (!$xml) {
                $zip->close();
                throw new Exception('Could not parse worksheet XML');
            }
            
            $data = [];
            $rowCount = 0;
            $maxPreviewRows = 20;
            
            // Parse rows
            foreach ($xml->sheetData->row as $row) {
                if ($rowCount >= $maxPreviewRows) break;
                
                $rowData = [];
                $colIndex = 0;
                
                foreach ($row->c as $cell) {
                    $value = '';
                    $cellType = (string)$cell['t'];
                    
                    if ($cell->v) {
                        if ($cellType === 's') {
                            // Shared string
                            $index = (int)$cell->v;
                            $value = isset($sharedStrings[$index]) ? $sharedStrings[$index] : '';
                        } else {
                            // Direct value
                            $value = (string)$cell->v;
                        }
                    }
                    
                    $rowData[] = $value;
                    $colIndex++;
                }
                
                if ($skipFirstRow) {
                    if ($rowCount === 0) {
                        $data['headers'] = ['Sl.No', 'Polling station No.', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type', 'Notes'];
                    } else {
                        $data['rows'][] = $rowData;
                    }
                } else {
                    if ($rowCount === 0) {
                        $data['headers'] = $rowData;
                    } else {
                        $data['rows'][] = $rowData;
                    }
                }
                $rowCount++;
            }
            
            $zip->close();
            
            // Get total row count (approximate)
            $data['total_rows'] = count($xml->sheetData->row);
            $data['preview_rows'] = count($data['rows']);
            
            // Validate booth data structure
            $data['validation'] = validateBoothDataStructure($data['headers'], $data['rows']);
            
            return $data;
        } else {
            throw new Exception('Could not open XLSX file as ZIP archive');
        }
    } catch (Exception $e) {
        throw new Exception('XLSX processing error: ' . $e->getMessage());
    }
}

function processXlsBasic($filePath, $skipFirstRow = false) {
    // Basic XLS processing - this is more complex and may not work reliably
    // For now, throw an error suggesting conversion to XLSX or CSV
    throw new Exception('XLS files are not supported. Please convert to XLSX or CSV format.');
}

function validateBoothDataStructure($headers, $rows) {
    global $pdo;
    
    // Headers validation with flexible matching
    
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
            
            // Validate serial number (first column)
            $slNo = trim($row[0] ?? '');
            if (empty($slNo)) {
                $rowErrors[$rowNum][] = "Serial number is required";
            } elseif (!is_numeric($slNo) || $slNo <= 0) {
                $rowErrors[$rowNum][] = "Serial number must be a positive integer";
            }
            
            // Validate polling station number (second column)
            $stationNo = trim($row[1] ?? '');
            if (empty($stationNo)) {
                $rowErrors[$rowNum][] = "Polling station number is required";
            }
            
            // Validate location name (third column)
            $location = trim($row[2] ?? '');
            if (empty($location)) {
                $rowErrors[$rowNum][] = "Location name is required";
            }
            
            // Validate polling station type (fifth column) - accept any value
            $stationType = trim($row[4] ?? '');
            // No validation needed - accept any value for polling station type
            
            // Check for duplicate stations within the file
            if (!empty($stationNo)) {
                $stationKey = $stationNo; // Use just station number since MLA is context-specific
                if (isset($stationTracker[$stationKey])) {
                    $duplicateStations[] = "Row $rowNum: Duplicate polling station $stationNo (also found in row " . $stationTracker[$stationKey] . ")";
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
        
        // Database validation - Check for existing booth records in current MLA
        $currentMlaId = $_GET['mla_id'] ?? null;
        if ($currentMlaId) {
            $existingBooths = [];
            try {
                $stmt = $pdo->prepare("SELECT polling_station_no FROM booth_master WHERE mla_id = ?");
                $stmt->execute([$currentMlaId]);
                $existingBooths = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {
                $validation['database_validation'][] = 'Error checking existing booths: ' . $e->getMessage();
            }
            
            // Check for conflicts with existing booth records
            $conflictStations = [];
            foreach ($rows as $index => $row) {
                $rowNum = $index + 1;
                $stationNo = trim($row[1] ?? ''); // Second column is polling station number
                
                if (!empty($stationNo) && in_array($stationNo, $existingBooths)) {
                    $conflictStations[] = "Row $rowNum: Polling station $stationNo already exists in database for this MLA";
                }
            }
            
            if (!empty($conflictStations)) {
                $validation['database_validation'] = array_merge($validation['database_validation'], $conflictStations);
                $validation['warnings'][] = 'Some polling stations already exist in database for this MLA and will be skipped';
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
        global $boothMaster;
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            return [
                'success' => false,
                'message' => 'Could not open file for processing.'
            ];
        }
        
        if ($skipFirstRow) {
            // Skip first row, use default headers
            $headers = ['Sl.No', 'Polling station No.', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type', 'Notes'];
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
            
            $boothData = mapCSVRowToBoothData($headers, $row, $auth->getCurrentUser()['user_id'], $filePath);
            
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
    // Excel files cannot be processed without PhpSpreadsheet
    return [
        'success' => false,
        'message' => 'Excel files require PhpSpreadsheet library for processing. Please convert to CSV format or install PhpSpreadsheet.'
    ];
}

function processExcelWithPhpSpreadsheetForUpload($filePath, $skipFirstRow = false) {
    // Try to use PhpSpreadsheet if available
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            global $pdo, $auth, $boothMaster;
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $rowNumber = 1; // Start from 1 (header row)
            
            if ($skipFirstRow) {
                $headers = ['Sl.No', 'Polling station No.', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type', 'Notes'];
                // Skip the first data row
                $rowNumber++;
            } else {
                // Read headers from first row
                $headerRow = [];
                $cellIterator = $worksheet->getRowIterator(1, 1)->current()->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $headerRow[] = $cell->getCalculatedValue();
                }
                $headers = $headerRow;
                $rowNumber++;
            }
            
            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }
                
                if (count($rowData) < count($headers)) {
                    $errors[] = "Row $rowNumber: Insufficient data columns";
                    $errorCount++;
                    $rowNumber++;
                    continue;
                }
                
                $boothData = mapCSVRowToBoothData($headers, $rowData, $auth->getCurrentUser()['user_id'], $filePath);
                
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
                
                $rowNumber++;
            }
            
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
                'message' => 'PhpSpreadsheet upload error: ' . $e->getMessage()
            ];
        }
    } else {
        // Fallback to basic Excel processing
        return processExcelBasicForUpload($filePath, $skipFirstRow);
    }
}

function processExcelBasicForUpload($filePath, $skipFirstRow = false) {
    // Basic Excel processing for upload using PHP's built-in functions
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($fileExtension === 'xlsx') {
        return processXlsxBasicForUpload($filePath, $skipFirstRow);
    } elseif ($fileExtension === 'xls') {
        return processXlsBasicForUpload($filePath, $skipFirstRow);
    } else {
        return [
            'success' => false,
            'message' => 'Unsupported Excel file format: ' . $fileExtension
        ];
    }
}

function processXlsxBasicForUpload($filePath, $skipFirstRow = false) {
    // Basic XLSX processing for upload using ZIP extraction
    try {
        global $pdo, $auth, $boothMaster;
        
        // XLSX files are ZIP archives
        $zip = new ZipArchive();
        if ($zip->open($filePath) === TRUE) {
            // Read the shared strings
            $sharedStrings = [];
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml) {
                $xml = simplexml_load_string($sharedStringsXml);
                if ($xml) {
                    foreach ($xml->si as $si) {
                        $sharedStrings[] = (string)$si->t;
                    }
                }
            }
            
            // Read the worksheet data
            $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (!$worksheetXml) {
                $zip->close();
                return [
                    'success' => false,
                    'message' => 'Could not read worksheet data from XLSX file'
                ];
            }
            
            $xml = simplexml_load_string($worksheetXml);
            if (!$xml) {
                $zip->close();
                return [
                    'success' => false,
                    'message' => 'Could not parse worksheet XML'
                ];
            }
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $rowNumber = 1;
            
            // Parse rows
            foreach ($xml->sheetData->row as $row) {
                $rowData = [];
                
                foreach ($row->c as $cell) {
                    $value = '';
                    $cellType = (string)$cell['t'];
                    
                    if ($cell->v) {
                        if ($cellType === 's') {
                            // Shared string
                            $index = (int)$cell->v;
                            $value = isset($sharedStrings[$index]) ? $sharedStrings[$index] : '';
                        } else {
                            // Direct value
                            $value = (string)$cell->v;
                        }
                    }
                    
                    $rowData[] = $value;
                }
                
                if ($skipFirstRow) {
                    if ($rowNumber === 1) {
                        $headers = ['Sl.No', 'Polling station No.', 'Location and name of building in which Polling Station located', 'Polling Areas', 'Polling Station Type', 'Notes'];
                    } else {
                        if (count($rowData) >= count($headers)) {
                            $boothData = mapCSVRowToBoothData($headers, $rowData, $auth->getCurrentUser()['user_id'], $filePath);
                            
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
                        } else {
                            $errors[] = "Row $rowNumber: Insufficient data columns";
                            $errorCount++;
                        }
                    }
                } else {
                    if ($rowNumber === 1) {
                        $headers = $rowData;
                    } else {
                        if (count($rowData) >= count($headers)) {
                            $boothData = mapCSVRowToBoothData($headers, $rowData, $auth->getCurrentUser()['user_id'], $filePath);
                            
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
                        } else {
                            $errors[] = "Row $rowNumber: Insufficient data columns";
                            $errorCount++;
                        }
                    }
                }
                
                $rowNumber++;
            }
            
            $zip->close();
            
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
        } else {
            return [
                'success' => false,
                'message' => 'Could not open XLSX file as ZIP archive'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'XLSX upload processing error: ' . $e->getMessage()
        ];
    }
}

function processXlsBasicForUpload($filePath, $skipFirstRow = false) {
    return [
        'success' => false,
        'message' => 'XLS files are not supported. Please convert to XLSX or CSV format.'
    ];
}

function mapCSVRowToBoothData($headers, $row, $createdBy, $filename = '') {
    // Create mapping array
    $data = [];
    
    // Map each column to its corresponding field using position-based mapping
    // This is more reliable than string matching
    for ($i = 0; $i < count($headers); $i++) {
        $header = strtolower(trim($headers[$i]));
        $value = isset($row[$i]) ? trim($row[$i]) : '';
        
        // Map based on position (most reliable) and header content as fallback
        if ($i == 0) {
            $data['sl_no'] = $value;
        } elseif ($i == 1) {
            $data['polling_station_no'] = $value;
        } elseif ($i == 2) {
            $data['location_name_of_building'] = $value;
        } elseif ($i == 3) {
            $data['polling_areas'] = $value;
        } elseif ($i == 4) {
            $data['polling_station_type'] = $value;
        } elseif ($i == 5) {
            $data['notes'] = $value;
        }
    }
    
    // Validate required fields
    if (empty($data['sl_no']) || empty($data['polling_station_no']) || empty($data['location_name_of_building'])) {
        return false;
    }
    
    // Set MLA ID from current context (since it's not in the file)
    $data['mla_id'] = $_GET['mla_id'] ?? null;
    
    // Set default values for optional fields
    if (empty($data['polling_areas'])) {
        $data['polling_areas'] = '';
    }
    if (empty($data['polling_station_type'])) {
        $data['polling_station_type'] = 'Regular';
    }
    
    // Handle notes - append filename if provided
    $existingNotes = $data['notes'] ?? '';
    if (!empty($filename)) {
        $filenameNote = "Uploaded from: " . basename($filename);
        if (!empty($existingNotes)) {
            $data['notes'] = $existingNotes . " | " . $filenameNote;
        } else {
            $data['notes'] = $filenameNote;
        }
    } else {
        $data['notes'] = $existingNotes;
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
    <title>MLA Details - <?php echo htmlspecialchars($mlaData['mla_constituency_name']); ?></title>
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
            color: #2c3e50;
            font-size: 14px;
        }
        
        .preview-table th {
            background: #34495e;
            color: #ffffff;
            font-weight: bold;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .preview-context {
            margin-top: 8px;
            font-size: 14px;
        }
        
        .mla-context {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .mla-id-cell {
            background: #f0f8ff !important;
            color: #1976d2 !important;
            font-weight: 600 !important;
            text-align: center !important;
        }
        
        .preview-table td {
            background: #ffffff;
            color: #2c3e50;
        }
        
        .preview-table tr:nth-child(even) td {
            background: #f8f9fa;
        }
        
        .preview-table tr:hover td {
            background: #e8f4f8;
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
        <?php if ($message): ?>
            <div class="message <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="header-section">
            <h1>🏛️ MLA Constituency Details</h1>
            <div class="header-actions">
                <a href="mp_detail.php?mp_id=<?php echo $mpId; ?>" class="btn btn-secondary">← Back to MP</a>
                <a href="mp_view.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_view.php" class="btn btn-primary current-page">🏛️ MLA Master</a>
                <a href="booth_view.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>
        
        <!-- Dynamic Breadcrumb Navigation -->
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mla_detail.php', ['mp_id' => $mpId, 'mla_id' => $mlaId]); ?>
        
        <!-- MLA Information -->
        <div class="detail-card">
            <h2>🏛️ <?php echo htmlspecialchars($mlaData['mla_constituency_name']); ?></h2>
            <div class="detail-info">
                <p><strong>MLA Code:</strong> <?php echo htmlspecialchars($mlaData['mla_constituency_code']); ?></p>
                <p><strong>MP Constituency:</strong> <?php echo htmlspecialchars($mpData['mp_constituency_name']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($mpData['state']); ?></p>
                <p><strong>Created By:</strong> <?php echo htmlspecialchars($mlaData['created_by']); ?></p>
                <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($mlaData['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- Upload Section -->
        <?php if ($auth->hasPermission('booth', 'create')): ?>
        <div class="upload-section" id="uploadSection">
            <div class="section-header">
                <h2>📤 Upload Booth Data</h2>
                <button type="button" id="toggleUpload" class="btn btn-primary">📤 Upload Data</button>
            </div>
            
            <div class="upload-content" id="uploadContent" style="display: none;">
                <div class="upload-info">
                    <p>Upload booth data from Excel (.xlsx, .xls) or CSV files. The system will validate the data and show a preview before uploading.</p>
                    
                    <div class="download-links">
                        <a href="test_booth_preview_format.csv" download class="btn btn-sm btn-primary">📋 Download Preview Format CSV</a>
                        <a href="test_booth_upload_new_format.csv" download class="btn btn-sm btn-primary">📥 Download New Format CSV</a>
                        <a href="test_any_polling_types.csv" download class="btn btn-sm btn-success">✅ Download Any Type Test CSV</a>
                        <a href="booth_template.csv" download class="btn btn-sm btn-secondary">📥 Download CSV Template</a>
                        <a href="test_booth_upload_simple.csv" download class="btn btn-sm btn-success">📊 Download Sample CSV</a>
                        <a href="test_booth_upload_simple.xlsx" download class="btn btn-sm btn-warning">📈 Download Sample Excel</a>
                        <a href="test_booth_upload.csv" download class="btn btn-sm btn-success">📊 Download Full Sample CSV</a>
                        <a href="test_no_headers.csv" download class="btn btn-sm btn-info">📋 Download No-Headers CSV</a>
                        <a href="test_validation_errors.csv" download class="btn btn-sm btn-danger">🚫 Download Error Test CSV</a>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="file-input-wrapper">
                        <input type="file" name="excel_file" id="excelFile" class="file-input" 
                               accept=".xlsx,.xls,.csv" required>
                        <button type="button" class="file-input-button" onclick="document.getElementById('excelFile').click()">
                            📁 Choose File
                        </button>
                    </div>
                    
                    <div class="radio-options">
                        <strong>📋 First Row Options:</strong><br><br>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="skip_first_row" value="no" checked>
                                <span>First row contains column headers (recommended)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="skip_first_row" value="yes">
                                <span>Skip first row (treat as data)</span>
                            </label>
                        </div>
                        <div class="radio-tip">
                            <strong>💡 Tip:</strong> Most files have headers in the first row. Only select "Skip first row" if your file doesn't have column headers.
                        </div>
                    </div>
                </form>

                <div class="file-info" id="fileInfo" style="display: none;">
                    <strong>Selected File:</strong> <span id="fileName"></span><br>
                    <strong>File Size:</strong> <span id="fileSize"></span>
                </div>
                
                <div class="loading-indicator" id="loadingIndicator" style="display: none; text-align: center; padding: 20px; color: #007bff;">
                    <div style="font-size: 18px; margin-bottom: 10px;">⏳ Processing file...</div>
                    <div style="font-size: 14px; color: #6c757d;">Please wait while we validate your data</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Preview Section -->
        <?php if ($previewData): ?>
        <div class="preview-section" id="previewSection">
            <div class="preview-header">
                <div class="preview-title">
                    📋 Data Preview - <?php echo htmlspecialchars($fileName); ?>
                    <div class="preview-context">
                        <span class="mla-context">🏛️ MLA ID: <?php echo htmlspecialchars($_GET['mla_id'] ?? 'N/A'); ?></span>
                    </div>
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

            <div class="preview-content">
                <div class="preview-table-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>🏛️ MLA ID</th>
                                <th>📊 Sl.No</th>
                                <th>🏢 Polling Station No.</th>
                                <th>📍 Location Name of Building</th>
                                <th>🗺️ Polling Areas</th>
                                <th>🏛️ Polling Station Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewData['rows'] as $row): ?>
                                <tr>
                                    <td class="mla-id-cell"><?php echo htmlspecialchars($_GET['mla_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row[0] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row[1] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row[2] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row[3] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row[4] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="preview-stats">
                    <?php if (isset($previewData['total_rows'])): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                            <div style="padding: 8px 12px; background: #e9ecef; border-radius: 5px;">
                                <strong>Total Rows:</strong> <?php echo $previewData['total_rows']; ?>
                            </div>
                            <div style="padding: 8px 12px; background: #e9ecef; border-radius: 5px;">
                                <strong>Preview Rows:</strong> <?php echo $previewData['preview_rows']; ?>
                                <?php if ($previewData['preview_rows'] < $previewData['total_rows']): ?>
                                    <em>(showing first <?php echo $previewData['preview_rows']; ?>)</em>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 8px 12px; background: #e9ecef; border-radius: 5px;">
                                <strong>First Row:</strong> 
                                <?php if ($skipFirstRow): ?>
                                    <span style="color: #ff6b35;">⚠️ Skipped (treated as data)</span>
                                <?php else: ?>
                                    <span style="color: #28a745;">✅ Used as headers</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Validation Summary -->
                        <?php if (isset($previewData['validation'])): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                                <?php if ($previewData['validation']['valid']): ?>
                                    <div style="padding: 8px 12px; background: #d4edda; color: #155724; border-radius: 5px; border: 1px solid #c3e6cb;">
                                        <strong>✅ Validation Status:</strong> Ready for Upload
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 8px 12px; background: #f8d7da; color: #721c24; border-radius: 5px; border: 1px solid #f5c6cb;">
                                        <strong>❌ Validation Status:</strong> Errors Found
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($previewData['validation']['errors'])): ?>
                                    <div style="padding: 8px 12px; background: #f8d7da; color: #721c24; border-radius: 5px; border: 1px solid #f5c6cb;">
                                        <strong>🚫 General Errors:</strong> <?php echo count($previewData['validation']['errors']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($previewData['validation']['data_validation'])): ?>
                                    <div style="padding: 8px 12px; background: #f8d7da; color: #721c24; border-radius: 5px; border: 1px solid #f5c6cb;">
                                        <strong>📋 Data Errors:</strong> <?php echo count($previewData['validation']['data_validation']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($previewData['validation']['duplicate_validation'])): ?>
                                    <div style="padding: 8px 12px; background: #f8d7da; color: #721c24; border-radius: 5px; border: 1px solid #f5c6cb;">
                                        <strong>🔄 Duplicates:</strong> <?php echo count($previewData['validation']['duplicate_validation']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($previewData['validation']['database_validation'])): ?>
                                    <div style="padding: 8px 12px; background: #f8d7da; color: #721c24; border-radius: 5px; border: 1px solid #f5c6cb;">
                                        <strong>🗄️ DB Conflicts:</strong> <?php echo count($previewData['validation']['database_validation']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($previewData['validation']['warnings'])): ?>
                                    <div style="padding: 8px 12px; background: #fff3cd; color: #856404; border-radius: 5px; border: 1px solid #ffeaa7;">
                                        <strong>⚠️ Warnings:</strong> <?php echo count($previewData['validation']['warnings']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($previewData['validation'])): ?>
                        <div style="margin-top: 15px;">
                            <?php if ($previewData['validation']['valid']): ?>
                                <div style="color: #28a745; font-weight: bold; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;">
                                    ✅ Data structure is valid and ready for upload
                                </div>
                            <?php else: ?>
                                <div style="color: #dc3545; font-weight: bold; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">
                                    ❌ Data structure has errors that need to be fixed
                                </div>
                                
                                <!-- General Errors -->
                                <?php if (!empty($previewData['validation']['errors'])): ?>
                                    <div style="margin-bottom: 15px;">
                                        <h4 style="color: #dc3545; margin-bottom: 8px;">🚫 General Errors:</h4>
                                        <ul style="margin: 0; padding-left: 20px; color: #dc3545;">
                                            <?php foreach ($previewData['validation']['errors'] as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Data Validation Errors -->
                                <?php if (!empty($previewData['validation']['data_validation'])): ?>
                                    <div style="margin-bottom: 15px;">
                                        <h4 style="color: #dc3545; margin-bottom: 8px;">📋 Data Format Errors:</h4>
                                        <div style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6;">
                                            <ul style="margin: 0; padding-left: 20px; color: #dc3545; font-size: 14px;">
                                                <?php foreach ($previewData['validation']['data_validation'] as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Duplicate Validation Errors -->
                                <?php if (!empty($previewData['validation']['duplicate_validation'])): ?>
                                    <div style="margin-bottom: 15px;">
                                        <h4 style="color: #dc3545; margin-bottom: 8px;">🔄 Duplicate Records in File:</h4>
                                        <div style="max-height: 150px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6;">
                                            <ul style="margin: 0; padding-left: 20px; color: #dc3545; font-size: 14px;">
                                                <?php foreach ($previewData['validation']['duplicate_validation'] as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Database Validation Issues -->
                                <?php if (!empty($previewData['validation']['database_validation'])): ?>
                                    <div style="margin-bottom: 15px;">
                                        <h4 style="color: #dc3545; margin-bottom: 8px;">🗄️ Database Conflicts:</h4>
                                        <div style="max-height: 150px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6;">
                                            <ul style="margin: 0; padding-left: 20px; color: #dc3545; font-size: 14px;">
                                                <?php foreach ($previewData['validation']['database_validation'] as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Warnings -->
                                <?php if (!empty($previewData['validation']['warnings'])): ?>
                                    <div style="margin-bottom: 15px;">
                                        <h4 style="color: #856404; margin-bottom: 8px;">⚠️ Warnings:</h4>
                                        <div style="background: #fff3cd; padding: 10px; border-radius: 5px; border: 1px solid #ffeaa7;">
                                            <ul style="margin: 0; padding-left: 20px; color: #856404; font-size: 14px;">
                                                <?php foreach ($previewData['validation']['warnings'] as $warning): ?>
                                                    <li><?php echo htmlspecialchars($warning); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- How to Fix Errors -->
                                <div style="margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 5px; border-left: 4px solid #007bff;">
                                    <h4 style="margin-top: 0; color: #007bff;">💡 How to Fix Errors:</h4>
                                    <ul style="margin: 10px 0; padding-left: 20px; color: #495057;">
                                        <li><strong>Missing Columns:</strong> Ensure your file has all required column headers</li>
                                        <li><strong>Data Format Errors:</strong> Check that MLA codes are numeric, serial numbers are positive integers</li>
                                        <li><strong>Missing MLA Codes:</strong> Add the missing MLA constituency codes to the MLA Master first</li>
                                        <li><strong>Duplicate Records:</strong> Remove duplicate polling stations from your file</li>
                                        <li><strong>Database Conflicts:</strong> Update existing records or remove conflicting entries</li>
                                        <li><strong>Invalid Station Types:</strong> Use only: Regular, Auxiliary, Special, Mobile</li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Polling Booths -->
        <div class="records-container">
            <div class="section-header">
                <h3>🏛️ Polling Booths in this MLA Constituency</h3>
            </div>
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>Sl No</th>
                            <th>Station No</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Areas</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($boothRecords)): ?>
                            <tr>
                                <td colspan="8" class="no-data">No polling booths found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($boothRecords as $booth): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booth['sl_no']); ?></td>
                                    <td><?php echo htmlspecialchars($booth['polling_station_no']); ?></td>
                                    <td><?php echo htmlspecialchars($booth['location_name_of_building']); ?></td>
                                    <td>
                                        <span class="station-type <?php echo strtolower($booth['polling_station_type']); ?>">
                                            <?php echo htmlspecialchars($booth['polling_station_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($booth['polling_areas'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($booth['created_by']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($booth['created_datetime'])); ?></td>
                                    <td class="actions">
                                        <a href="booth_detail.php?mp_id=<?php echo $mpId; ?>&mla_id=<?php echo $mlaId; ?>&booth_id=<?php echo $booth['booth_id']; ?>" 
                                           class="btn btn-primary">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <style>
        .detail-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .detail-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-info p {
            margin: 8px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        
        .station-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .station-type.regular {
            background: #d4edda;
            color: #155724;
        }
        
        .station-type.auxiliary {
            background: #fff3cd;
            color: #856404;
        }
        
        .station-type.special {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .station-type.mobile {
            background: #f8d7da;
            color: #721c24;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-actions {
            display: flex;
            gap: 10px;
        }
    </style>
    
    <script>
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
