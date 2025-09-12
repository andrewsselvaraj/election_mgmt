<?php
require_once 'config.php';
require_once 'BoothExcelProcessor.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('booth', 'create')) {
    header('Location: unauthorized.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$message = '';
$messageType = '';

// Handle template download
if (isset($_GET['download_template'])) {
    $processor = new BoothExcelProcessor($pdo);
    $processor->downloadTemplate();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $uploadDir = 'uploads/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['excel_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Validate file
    if ($fileError !== UPLOAD_ERR_OK) {
        $message = 'File upload error: ' . $fileError;
        $messageType = 'error';
    } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
        $message = 'File size too large. Maximum size is 5MB.';
        $messageType = 'error';
    } else {
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
            $message = 'Invalid file type. Please upload a CSV or Excel file.';
            $messageType = 'error';
        } else {
            // Generate unique filename
            $uniqueFileName = uniqid() . '_' . $fileName;
            $filePath = $uploadDir . $uniqueFileName;
            
            if (move_uploaded_file($fileTmpName, $filePath)) {
                try {
                    $processor = new BoothExcelProcessor($pdo);
                    $result = $processor->processExcelFile($filePath, $currentUser['first_name'] . ' ' . $currentUser['last_name']);
                    
                    if ($result['success']) {
                        $message = "Upload successful! {$result['successCount']} records created.";
                        if ($result['errorCount'] > 0) {
                            $message .= " {$result['errorCount']} records failed.";
                            if (!empty($result['errors'])) {
                                $message .= " Errors: " . implode('; ', array_slice($result['errors'], 0, 5));
                                if (count($result['errors']) > 5) {
                                    $message .= " and " . (count($result['errors']) - 5) . " more...";
                                }
                            }
                        }
                        $messageType = $result['errorCount'] > 0 ? 'warning' : 'success';
                    } else {
                        $message = 'Upload failed: ' . $result['message'];
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error processing file: ' . $e->getMessage();
                    $messageType = 'error';
                }
                
                // Clean up uploaded file
                unlink($filePath);
            } else {
                $message = 'Failed to save uploaded file.';
                $messageType = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Booth Data</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>📤 Upload Booth Data</h1>
            <div class="header-actions">
                <a href="booth_index.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="index.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_index.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>
        
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb">
            <a href="index.php" class="breadcrumb-item">📊 MP Master</a>
            <span class="breadcrumb-separator">→</span>
            <a href="mla_index.php" class="breadcrumb-item">🏛️ MLA Master</a>
            <span class="breadcrumb-separator">→</span>
            <a href="booth_index.php" class="breadcrumb-item">🏛️ Booth Master</a>
            <span class="breadcrumb-separator">→</span>
            <span class="breadcrumb-item active">📤 Upload Booth Data</span>
        </nav>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-container">
            <div class="upload-info">
                <h2>📋 Upload Instructions</h2>
                <div class="instructions">
                    <h3>Required Columns:</h3>
                    <ul>
                        <li><strong>mla_constituency_code</strong> - MLA constituency code (must exist in MLA Master)</li>
                        <li><strong>sl_no</strong> - Serial number (integer)</li>
                        <li><strong>polling_station_no</strong> - Polling station number (unique within MLA constituency)</li>
                        <li><strong>location_name_of_buiding</strong> - Location name of building</li>
                    </ul>
                    
                    <h3>Optional Columns:</h3>
                    <ul>
                        <li><strong>polling_areas</strong> - Description of polling areas</li>
                        <li><strong>polling_station_type</strong> - Type: Regular, Auxiliary, Special, Mobile (default: Regular)</li>
                    </ul>
                    
                    <h3>File Requirements:</h3>
                    <ul>
                        <li>File format: CSV, XLS, or XLSX</li>
                        <li>Maximum file size: 5MB</li>
                        <li>First row should contain column headers</li>
                        <li>MLA constituency codes must exist in MLA Master table</li>
                    </ul>
                </div>
                
                <div class="template-section">
                    <h3>📥 Download Template</h3>
                    <p>Download a sample CSV template to see the correct format:</p>
                    <a href="?download_template=1" class="btn btn-primary">Download Template</a>
                </div>
            </div>
            
            <div class="upload-form-container">
                <h2>📤 Upload File</h2>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="excel_file">Select File:</label>
                        <input type="file" id="excel_file" name="excel_file" accept=".csv,.xlsx,.xls" required>
                        <small>Supported formats: CSV, XLS, XLSX (Max 5MB)</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Upload File</button>
                        <button type="button" onclick="clearForm()" class="btn btn-secondary">Clear</button>
                    </div>
                </form>
                
                <div class="upload-progress" id="upload-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p>Uploading and processing file...</p>
                </div>
            </div>
        </div>
        
        <div class="sample-data">
            <h3>📋 Sample Data Format</h3>
            <div class="sample-table">
                <table>
                    <thead>
                        <tr>
                            <th>mla_constituency_code</th>
                            <th>sl_no</th>
                            <th>polling_station_no</th>
                            <th>location_name_of_buiding</th>
                            <th>polling_areas</th>
                            <th>polling_station_type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>1</td>
                            <td>001</td>
                            <td>Government School Building</td>
                            <td>Area 1-5</td>
                            <td>Regular</td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>2</td>
                            <td>002</td>
                            <td>Community Hall</td>
                            <td>Area 6-10</td>
                            <td>Regular</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>1</td>
                            <td>001</td>
                            <td>Primary School</td>
                            <td>Area 1-3</td>
                            <td>Auxiliary</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Handle form submission
        document.querySelector('.upload-form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('excel_file');
            const progressDiv = document.getElementById('upload-progress');
            
            if (fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a file to upload');
                return;
            }
            
            // Show progress
            progressDiv.style.display = 'block';
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';
        });
        
        // Clear form
        function clearForm() {
            document.querySelector('.upload-form').reset();
            document.getElementById('upload-progress').style.display = 'none';
        }
        
        // File validation
        document.getElementById('excel_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    alert('File size too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                const validExtensions = ['csv', 'xlsx', 'xls'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!validExtensions.includes(fileExtension)) {
                    alert('Invalid file type. Please select a CSV or Excel file.');
                    this.value = '';
                    return;
                }
            }
        });
    </script>
    
    <style>
        .upload-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        
        .upload-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .upload-form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .instructions {
            margin: 15px 0;
        }
        
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 5px 0;
        }
        
        .template-section {
            margin-top: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 6px;
        }
        
        .upload-form {
            margin: 20px 0;
        }
        
        .upload-progress {
            margin-top: 20px;
            text-align: center;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            width: 0%;
            animation: progress 2s ease-in-out infinite;
        }
        
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        .sample-data {
            margin-top: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .sample-table {
            overflow-x: auto;
            margin-top: 15px;
        }
        
        .sample-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .sample-table th,
        .sample-table td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        .sample-table th {
            background: #e9ecef;
            font-weight: bold;
        }
        
        .sample-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .upload-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
