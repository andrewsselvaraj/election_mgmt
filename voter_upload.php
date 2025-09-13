<?php
require_once 'config.php';
require_once 'VoterMaster.php';
require_once 'MLAMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('voter', 'create')) {
    header('Location: voter_view.php?error=no_permission');
    exit;
}

$voterMaster = new VoterMaster($pdo);
$mlaMaster = new MLAMaster($pdo);
$currentUser = $auth->getCurrentUser();

$message = '';
$messageType = '';
$previewData = null;
$fileName = '';
$fileType = '';
$skipFirstRow = false;

// Get MLAs for dropdown
$mlas = $mlaMaster->readAll();

// Handle file upload for preview
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_preview'])) {
    try {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please select a valid file');
        }
        
        $file = $_FILES['excel_file'];
        $fileName = $file['name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $skipFirstRow = isset($_POST['skip_first_row']);
        
        // Validate file type
        if (!in_array($fileType, ['xlsx', 'xls', 'csv'])) {
            throw new Exception('Please upload a valid Excel (.xlsx, .xls) or CSV file');
        }
        
        // Move uploaded file
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filePath = $uploadDir . uniqid() . '_' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to upload file');
        }
        
        // Process file for preview
        $previewData = processFileForPreview($filePath, $fileType, $skipFirstRow);
        
        if ($previewData['error']) {
            throw new Exception($previewData['error']);
        }
        
        // Store file path in session for final upload
        $_SESSION['uploaded_file_path'] = $filePath;
        $_SESSION['skip_first_row'] = $skipFirstRow;
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Handle final upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_upload'])) {
    try {
        $filePath = $_SESSION['uploaded_file_path'] ?? '';
        $skipFirstRow = $_SESSION['skip_first_row'] ?? false;
        
        if (empty($filePath) || !file_exists($filePath)) {
            throw new Exception('File not found. Please upload again.');
        }
        
        $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Process file for upload
        $uploadData = processFileForUpload($filePath, $fileType, $skipFirstRow);
        
        if ($uploadData['error']) {
            throw new Exception($uploadData['error']);
        }
        
        // Bulk insert voters
        $result = $voterMaster->bulkInsert($uploadData['voters']);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
            
            // Clear session data
            unset($_SESSION['uploaded_file_path']);
            unset($_SESSION['skip_first_row']);
            
            // Clean up uploaded file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// File processing functions
function processFileForPreview($filePath, $fileType, $skipFirstRow = false) {
    $data = [];
    
    if ($fileType === 'csv') {
        $data = processVoterCSVForPreview($filePath, $skipFirstRow);
    } else {
        $data = processVoterExcelForPreview($filePath, $fileType, $skipFirstRow);
    }
    
    return $data;
}

function processFileForUpload($filePath, $fileType, $skipFirstRow = false) {
    $data = [];
    
    if ($fileType === 'csv') {
        $data = processVoterCSVForUpload($filePath, $skipFirstRow);
    } else {
        $data = processVoterExcelForUpload($filePath, $fileType, $skipFirstRow);
    }
    
    return $data;
}

function processVoterCSVForPreview($filePath, $skipFirstRow = false) {
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
                    $data['headers'] = ['voter_id', 'mla_id', 'voter_name', 'father_name', 'mother_name', 'husband_name', 'age', 'gender', 'address', 'phone', 'email', 'booth_id', 'ward_no', 'part_no'];
                } else {
                    $data['rows'][] = $row;
                }
            } else {
                // First row contains headers
                if ($rowCount === 0) {
                    $data['headers'] = $row;
                } else {
                    $data['rows'][] = $row;
                }
            }
            $rowCount++;
        }
        
        fclose($handle);
        
        if (empty($data['rows'])) {
            $data['error'] = 'No data found in file';
        }
    } else {
        $data['error'] = 'Could not read file';
    }
    
    return $data;
}

function processVoterCSVForUpload($filePath, $skipFirstRow = false) {
    global $currentUser;
    
    $data = [];
    $handle = fopen($filePath, 'r');
    
    if ($handle !== false) {
        $rowCount = 0;
        $headers = [];
        
        while (($row = fgetcsv($handle)) !== false) {
            if ($skipFirstRow) {
                // Skip first row, treat all rows as data
                if ($rowCount === 0) {
                    $headers = ['voter_id', 'mla_id', 'voter_name', 'father_name', 'mother_name', 'husband_name', 'age', 'gender', 'address', 'phone', 'email', 'booth_id', 'ward_no', 'part_no'];
                } else {
                    $voterData = mapCSVRowToVoterData($headers, $row, $currentUser['first_name'] . ' ' . $currentUser['last_name']);
                    if ($voterData) {
                        $data['voters'][] = $voterData;
                    }
                }
            } else {
                // First row contains headers
                if ($rowCount === 0) {
                    $headers = $row;
                } else {
                    $voterData = mapCSVRowToVoterData($headers, $row, $currentUser['first_name'] . ' ' . $currentUser['last_name']);
                    if ($voterData) {
                        $data['voters'][] = $voterData;
                    }
                }
            }
            $rowCount++;
        }
        
        fclose($handle);
        
        if (empty($data['voters'])) {
            $data['error'] = 'No valid voter data found in file';
        }
    } else {
        $data['error'] = 'Could not read file';
    }
    
    return $data;
}

function processVoterExcelForPreview($filePath, $fileType, $skipFirstRow = false) {
    // For now, return error for Excel files
    return ['error' => 'Excel processing not available. Please convert to CSV format.'];
}

function processVoterExcelForUpload($filePath, $fileType, $skipFirstRow = false) {
    // For now, return error for Excel files
    return ['error' => 'Excel processing not available. Please convert to CSV format.'];
}

function mapCSVRowToVoterData($headers, $row, $createdBy) {
    $data = [];
    
    // Map columns by position for reliability
    for ($i = 0; $i < count($headers); $i++) {
        $value = isset($row[$i]) ? trim($row[$i]) : '';
        
        if ($i == 0) {
            $data['voter_id'] = $value;
        } elseif ($i == 1) {
            $data['mla_id'] = $value;
        } elseif ($i == 2) {
            $data['voter_name'] = $value;
        } elseif ($i == 3) {
            $data['father_name'] = $value;
        } elseif ($i == 4) {
            $data['mother_name'] = $value;
        } elseif ($i == 5) {
            $data['husband_name'] = $value;
        } elseif ($i == 6) {
            $data['age'] = is_numeric($value) ? (int)$value : 0;
        } elseif ($i == 7) {
            $data['gender'] = $value;
        } elseif ($i == 8) {
            $data['address'] = $value;
        } elseif ($i == 9) {
            $data['phone'] = $value;
        } elseif ($i == 10) {
            $data['email'] = $value;
        } elseif ($i == 11) {
            $data['booth_id'] = $value ?: null;
        } elseif ($i == 12) {
            $data['ward_no'] = $value;
        } elseif ($i == 13) {
            $data['part_no'] = $value;
        }
    }
    
    // Validate required fields
    if (empty($data['voter_id']) || empty($data['mla_id']) || empty($data['voter_name']) || empty($data['father_name'])) {
        return null;
    }
    
    $data['created_by'] = $createdBy;
    
    return $data;
}

$pageTitle = 'Bulk Voter Upload - Election Management System';
include 'header.php';
?>

<style>
    .upload-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .upload-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .upload-section h3 {
        margin: 0 0 20px 0;
        color: #333;
        border-bottom: 2px solid #f8f9fa;
        padding-bottom: 10px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: auto;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: 500;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }
    
    .help-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .preview-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 12px;
    }
    
    .preview-table th {
        background: #343a40;
        color: white;
        padding: 8px;
        text-align: left;
        font-weight: 600;
    }
    
    .preview-table td {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }
    
    .preview-table tr:hover {
        background: #f8f9fa;
    }
    
    .file-info {
        background: #e3f2fd;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #bbdefb;
    }
    
    .file-info h4 {
        margin: 0 0 10px 0;
        color: #1976d2;
    }
    
    .template-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .template-section h3 {
        margin: 0 0 15px 0;
        color: #333;
    }
    
    .template-links {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .template-link {
        background: #007bff;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
    }
    
    .template-link:hover {
        background: #0056b3;
        color: white;
    }
</style>

<div class="page-header">
    <h1>📤 Bulk Voter Upload</h1>
    <p>Upload voter data from Excel or CSV files</p>
</div>

<div class="upload-container">
    <!-- Template Section -->
    <div class="template-section">
        <h3>📋 Download Templates</h3>
        <p>Download the appropriate template to ensure your data is formatted correctly:</p>
        <div class="template-links">
            <a href="voter_template.csv" class="template-link" download>📄 CSV Template</a>
            <a href="voter_sample.csv" class="template-link" download>📊 Sample Data</a>
            <a href="voter_format_guide.pdf" class="template-link" download>📖 Format Guide</a>
        </div>
    </div>
    
    <!-- Upload Section -->
    <div class="upload-section">
        <h3>📤 Upload File</h3>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file">Select File</label>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                <div class="help-text">Supported formats: Excel (.xlsx, .xls) or CSV (.csv)</div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="skip_first_row" name="skip_first_row" <?php echo $skipFirstRow ? 'checked' : ''; ?>>
                    <label for="skip_first_row">Skip first row (if file contains headers)</label>
                </div>
                <div class="help-text">Check this if your file has column headers in the first row</div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="upload_preview" class="btn btn-primary">📋 Preview Data</button>
            </div>
        </form>
    </div>
    
    <!-- Preview Section -->
    <?php if ($previewData && !isset($previewData['error'])): ?>
        <div class="preview-section">
            <h3>👀 Data Preview</h3>
            
            <div class="file-info">
                <h4>File Information</h4>
                <p><strong>File:</strong> <?php echo htmlspecialchars($fileName); ?></p>
                <p><strong>Type:</strong> <?php echo strtoupper($fileType); ?></p>
                <p><strong>Rows:</strong> <?php echo count($previewData['rows']); ?> (showing first 20)</p>
                <p><strong>Skip First Row:</strong> <?php echo $skipFirstRow ? 'Yes' : 'No'; ?></p>
            </div>
            
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
            
            <div class="form-actions">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="confirm_upload" class="btn btn-success">✅ Upload Data</button>
                </form>
                <a href="voter_upload.php" class="btn btn-secondary">🔄 Upload Different File</a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Instructions -->
    <div class="upload-section">
        <h3>📖 Upload Instructions</h3>
        <div style="line-height: 1.6;">
            <h4>Required Columns (in order):</h4>
            <ol>
                <li><strong>voter_id</strong> - Unique voter ID within MLA constituency</li>
                <li><strong>mla_id</strong> - MLA constituency ID</li>
                <li><strong>voter_name</strong> - Full name of the voter</li>
                <li><strong>father_name</strong> - Father's name</li>
                <li><strong>mother_name</strong> - Mother's name (optional)</li>
                <li><strong>husband_name</strong> - Husband's name (optional, for married women)</li>
                <li><strong>age</strong> - Age in years (18-120)</li>
                <li><strong>gender</strong> - Male, Female, or Other</li>
                <li><strong>address</strong> - Voter's address (optional)</li>
                <li><strong>phone</strong> - Phone number (optional)</li>
                <li><strong>email</strong> - Email address (optional)</li>
                <li><strong>booth_id</strong> - Polling booth ID (optional)</li>
                <li><strong>ward_no</strong> - Ward number (optional)</li>
                <li><strong>part_no</strong> - Part number (optional)</li>
            </ol>
            
            <h4>Important Notes:</h4>
            <ul>
                <li>Voter ID must be unique within each MLA constituency</li>
                <li>MLA ID must exist in the system</li>
                <li>Age must be between 18 and 120</li>
                <li>Gender must be exactly "Male", "Female", or "Other"</li>
                <li>Empty cells are allowed for optional fields</li>
                <li>Maximum file size: 10MB</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
