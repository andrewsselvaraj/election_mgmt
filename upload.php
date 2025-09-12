<?php
require_once 'config.php';
require_once 'ExcelProcessor.php';

$message = '';
$messageType = '';
$results = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    try {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = $_FILES['excel_file']['name'];
        $fileTmpName = $_FILES['excel_file']['tmp_name'];
        $fileSize = $_FILES['excel_file']['size'];
        $fileError = $_FILES['excel_file']['error'];
        
        // Validate file
        if ($fileError !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $fileError);
        }
        
        if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }
        
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only CSV, XLS, and XLSX files are allowed.');
        }
        
        // Generate unique filename
        $newFileName = uniqid() . '_' . $fileName;
        $uploadPath = $uploadDir . $newFileName;
        
        if (!move_uploaded_file($fileTmpName, $uploadPath)) {
            throw new Exception('Failed to move uploaded file.');
        }
        
        // Process the file
        $excelProcessor = new ExcelProcessor($pdo);
        $createdBy = $_POST['created_by'] ?? 'System';
        
        // Validate file format first
        $validation = $excelProcessor->validateFile($uploadPath);
        if (!$validation['valid']) {
            unlink($uploadPath); // Delete uploaded file
            throw new Exception($validation['message']);
        }
        
        // Process the file
        $results = $excelProcessor->processExcelFile($uploadPath, $createdBy);
        
        // Clean up uploaded file
        unlink($uploadPath);
        
        if ($results['errors'] > 0) {
            $message = "Import completed with {$results['success']} successful records and {$results['errors']} errors.";
            $messageType = 'warning';
        } else {
            $message = "Successfully imported {$results['success']} records!";
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle template download
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    $excelProcessor = new ExcelProcessor($pdo);
    $templatePath = $excelProcessor->generateTemplate();
    
    if ($templatePath && file_exists($templatePath)) {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="MP_Master_Template.csv"');
        readfile($templatePath);
        unlink($templatePath); // Delete template after download
        exit;
    } else {
        $message = 'Error generating template file.';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload MP Data - Excel Import</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .upload-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: #3498db;
            background-color: #f8f9fa;
        }
        
        .file-upload-area.dragover {
            border-color: #27ae60;
            background-color: #d4edda;
        }
        
        .file-input {
            display: none;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .upload-text {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .upload-subtext {
            color: #999;
            font-size: 14px;
        }
        
        .file-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            display: none;
        }
        
        .template-section {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .template-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .template-section p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .results-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .error-item {
            color: #721c24;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to MP Master Management</a>
        
        <h1>Upload MP Data from Excel</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Template Section -->
        <div class="template-section">
            <h3>📋 Download Template</h3>
            <p>Download the Excel template to see the required format for uploading MP data.</p>
            <a href="?action=download_template" class="btn btn-primary">Download Template (CSV)</a>
        </div>
        
        <!-- Upload Form -->
        <div class="upload-form">
            <h2>📤 Upload Excel File</h2>
            <form method="POST" enctype="multipart/form-data" id="upload-form">
                <div class="form-group">
                    <label for="created_by">Created By:</label>
                    <input type="text" id="created_by" name="created_by" required 
                           placeholder="Enter your name" value="<?php echo htmlspecialchars($_POST['created_by'] ?? ''); ?>">
                </div>
                
                <div class="file-upload-area" id="file-upload-area">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">Click to select file or drag and drop</div>
                    <div class="upload-subtext">Supports CSV, XLS, XLSX files (Max 5MB)</div>
                    <input type="file" id="excel_file" name="excel_file" class="file-input" 
                           accept=".csv,.xlsx,.xls" required>
                </div>
                
                <div class="file-info" id="file-info">
                    <strong>Selected File:</strong> <span id="file-name"></span><br>
                    <strong>Size:</strong> <span id="file-size"></span>
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="upload-btn" disabled>Upload and Process</button>
                    <button type="button" id="clear-btn" style="display: none;">Clear</button>
                </div>
            </form>
        </div>
        
        <!-- Results Section -->
        <?php if ($results): ?>
            <div class="results-section">
                <h3>📊 Import Results</h3>
                <p><strong>Successful Records:</strong> <?php echo $results['success']; ?></p>
                <p><strong>Errors:</strong> <?php echo $results['errors']; ?></p>
                
                <?php if (!empty($results['messages'])): ?>
                    <div class="error-list">
                        <strong>Error Details:</strong>
                        <?php foreach ($results['messages'] as $error): ?>
                            <div class="error-item"><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px;">
                    <a href="index.php" class="btn btn-primary">View All Records</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // File upload handling
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('excel_file');
        const fileInfo = document.getElementById('file-info');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const uploadBtn = document.getElementById('upload-btn');
        const clearBtn = document.getElementById('clear-btn');
        
        // Click to select file
        fileUploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // File selection
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                showFileInfo(file);
            }
        });
        
        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showFileInfo(files[0]);
            }
        });
        
        // Show file information
        function showFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
            uploadBtn.disabled = false;
            clearBtn.style.display = 'inline-block';
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Clear file
        clearBtn.addEventListener('click', () => {
            fileInput.value = '';
            fileInfo.style.display = 'none';
            uploadBtn.disabled = true;
            clearBtn.style.display = 'none';
        });
        
        // Form submission
        document.getElementById('upload-form').addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            if (!file) {
                e.preventDefault();
                alert('Please select a file to upload.');
                return;
            }
            
            // Show loading state
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Processing...';
        });
    </script>
</body>
</html>
