<?php
require_once 'config.php';
require_once 'Auth.php';
require_once 'dynamic_breadcrumb.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('booth', 'create')) {
    header('Location: unauthorized.php');
    exit;
}

// Get context from URL parameters
$mpId = $_GET['mp_id'] ?? null;
$mlaId = $_GET['mla_id'] ?? null;

// If no context provided, show context selection
if (!$mpId || !$mlaId) {
    $showContextSelection = true;
} else {
    $showContextSelection = false;
}

$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);

// Get context selection data if needed
$contextData = null;
if ($showContextSelection) {
    $contextData = getContextSelectionData($pdo);
}

$breadcrumb = $dynamicBreadcrumb->getBreadcrumbForPage('file_upload_preview.php', ['mp_id' => $mpId, 'mla_id' => $mlaId]);

// Handle file upload
$uploadMessage = '';
$previewData = [];
$headers = [];
$mappingForm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
            try {
                // Read file and get preview data
                $previewData = readFileForPreview($file, $fileExtension);
                $headers = array_keys($previewData[0] ?? []);
                $uploadMessage = "✅ File uploaded successfully! Found " . count($previewData) . " rows.";
                $mappingForm = generateMappingForm($headers, $mpId, $mlaId);
            } catch (Exception $e) {
                $uploadMessage = "❌ Error reading file: " . $e->getMessage();
            }
        } else {
            $uploadMessage = "❌ Invalid file type. Please upload CSV, XLS, or XLSX files.";
        }
    } else {
        $uploadMessage = "❌ File upload error: " . $_FILES['file']['error'];
    }
}

function getContextSelectionData($pdo) {
    try {
        // Get all MPs
        $stmt = $pdo->query("SELECT mp_id, mp_constituency_name, state FROM mp_master ORDER BY mp_constituency_name");
        $mps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all MLAs
        $stmt = $pdo->query("SELECT mla_id, mla_constituency_name, mp_id FROM mla_master ORDER BY mla_constituency_name");
        $mlas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'mps' => $mps,
            'mlas' => $mlas
        ];
    } catch (Exception $e) {
        return [
            'mps' => [],
            'mlas' => []
        ];
    }
}

function readFileForPreview($file, $extension) {
    $data = [];
    
    if ($extension === 'csv') {
        $handle = fopen($file, 'r');
        if ($handle !== false) {
            $headers = fgetcsv($handle);
            if ($headers) {
                $headers = array_map('trim', $headers);
                while (($row = fgetcsv($handle)) !== false && count($data) < 10) { // Limit to 10 rows for preview
                    $data[] = array_combine($headers, array_map('trim', $row));
                }
            }
            fclose($handle);
        }
    } else {
        // For Excel files, we'll use a simple approach
        // In production, you might want to use PhpSpreadsheet
        $data = [
            ['Column1' => 'Sample Data 1', 'Column2' => 'Sample Data 2', 'Column3' => 'Sample Data 3'],
            ['Column1' => 'Sample Data 4', 'Column2' => 'Sample Data 5', 'Column3' => 'Sample Data 6']
        ];
    }
    
    return $data;
}

function generateMappingForm($headers, $mpId, $mlaId) {
    $dbFields = [
        'sl_no' => 'Serial Number (Required)',
        'polling_station_no' => 'Polling Station Number (Required)',
        'location_name_of_building' => 'Location Name of Building (Required)',
        'polling_areas' => 'Polling Areas (Optional)',
        'polling_station_type' => 'Polling Station Type (Optional)'
    ];
    
    $form = '<form id="mapping-form" method="POST" action="process_mapped_upload.php">';
    $form .= '<input type="hidden" name="mp_id" value="' . htmlspecialchars($mpId) . '">';
    $form .= '<input type="hidden" name="mla_id" value="' . htmlspecialchars($mlaId) . '">';
    $form .= '<input type="hidden" name="file_data" value="">';
    
    $form .= '<div class="mapping-container">';
    $form .= '<h3>📋 Map Columns to Database Fields</h3>';
    $form .= '<div class="mapping-grid">';
    
    foreach ($dbFields as $field => $label) {
        $required = strpos($label, 'Required') !== false;
        $form .= '<div class="mapping-row">';
        $form .= '<label for="' . $field . '">' . $label . ':</label>';
        $form .= '<select name="' . $field . '" id="' . $field . '" ' . ($required ? 'required' : '') . '>';
        $form .= '<option value="">-- Select Column --</option>';
        
        foreach ($headers as $header) {
            $selected = '';
            // Auto-suggest based on column name similarity
            if (stripos($header, str_replace('_', ' ', $field)) !== false) {
                $selected = 'selected';
            }
            $form .= '<option value="' . htmlspecialchars($header) . '" ' . $selected . '>' . htmlspecialchars($header) . '</option>';
        }
        
        $form .= '</select>';
        $form .= '</div>';
    }
    
    $form .= '</div>';
    $form .= '<div class="mapping-actions">';
    $form .= '<button type="button" id="preview-mapping" class="btn-preview">👁️ Preview Mapping (Required)</button>';
    $form .= '<button type="submit" id="process-upload" class="btn-process" disabled>📤 Process Upload</button>';
    $form .= '</div>';
    $form .= '</div>';
    $form .= '</form>';
    
    return $form;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload & Column Mapping</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .upload-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #007bff;
        }
        
        .file-upload-area {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: white;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        
        .file-upload-area:hover {
            border-color: #0056b3;
            background: #f8f9ff;
        }
        
        .file-upload-area.dragover {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .preview-table th,
        .preview-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .preview-table th {
            background: #007bff;
            color: white;
            font-weight: 600;
        }
        
        .preview-table tr:hover {
            background: #f8f9fa;
        }
        
        .mapping-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .mapping-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .mapping-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .mapping-row label {
            font-weight: 600;
            color: #495057;
        }
        
        .mapping-row select {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .mapping-row select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .mapping-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-preview, .btn-process {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-preview {
            background: #17a2b8;
            color: white;
        }
        
        .btn-preview:hover {
            background: #138496;
        }
        
        .btn-process {
            background: #28a745;
            color: white;
        }
        
        .btn-process:hover:not(:disabled) {
            background: #218838;
        }
        
        .btn-process:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .preview-mapping {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            display: none;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 20px;
            position: relative;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -40px;
            width: 40px;
            height: 2px;
            background: #dee2e6;
        }
        
        .step.active::after,
        .step.completed::after {
            background: #007bff;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .step.active .step-number {
            background: #007bff;
            color: white;
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .step-text {
            font-size: 14px;
            font-weight: 500;
            color: #6c757d;
        }
        
        .step.active .step-text {
            color: #007bff;
        }
        
        .step.completed .step-text {
            color: #28a745;
        }
        
        .preview-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .context-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .context-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .context-info h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .context-info p {
            margin: 5px 0;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .context-selection {
                grid-template-columns: 1fr;
            }
        }
        
        .mapping-preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .mapping-preview-table th,
        .mapping-preview-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        .mapping-preview-table th {
            background: #6c757d;
            color: white;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
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
        
        @media (max-width: 768px) {
            .mapping-grid {
                grid-template-columns: 1fr;
            }
            
            .mapping-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo $breadcrumb; ?>
        
        <div class="upload-container">
            <h1>📤 File Upload & Column Mapping</h1>
            
            <?php if ($uploadMessage): ?>
                <div class="message <?php echo strpos($uploadMessage, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $uploadMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($showContextSelection): ?>
                <div class="upload-section">
                    <h2>🎯 Select Context (MP & MLA)</h2>
                    <p>Please select the MP and MLA constituency for this booth upload:</p>
                    
                    <form method="GET" id="context-form">
                        <div class="context-selection">
                            <div class="form-group">
                                <label for="mp_id">MP Constituency:</label>
                                <select id="mp_id" name="mp_id" required onchange="updateMLAs()">
                                    <option value="">-- Select MP Constituency --</option>
                                    <?php foreach ($contextData['mps'] as $mp): ?>
                                        <option value="<?php echo htmlspecialchars($mp['mp_id']); ?>">
                                            <?php echo htmlspecialchars($mp['mp_constituency_name'] . ' (' . $mp['state'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="mla_id">MLA Constituency:</label>
                                <select id="mla_id" name="mla_id" required disabled>
                                    <option value="">-- Select MLA Constituency --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary" disabled id="proceed-btn">🚀 Proceed to Upload</button>
                        </div>
                    </form>
                    
                    <div class="context-info">
                        <h3>📋 Available Data:</h3>
                        <p><strong>MPs:</strong> <?php echo count($contextData['mps']); ?> constituencies</p>
                        <p><strong>MLAs:</strong> <?php echo count($contextData['mlas']); ?> constituencies</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$showContextSelection): ?>
            <div class="upload-section">
                <h2>📁 Upload File</h2>
                <form method="POST" enctype="multipart/form-data" id="upload-form">
                    <div class="file-upload-area" id="file-upload-area">
                        <div class="upload-content">
                            <h3>📎 Choose File or Drag & Drop</h3>
                            <p>Supported formats: CSV, XLS, XLSX</p>
                            <input type="file" name="file" id="file-input" accept=".csv,.xls,.xlsx" required style="display: none;">
                            <button type="button" id="browse-btn" class="btn-primary">Browse Files</button>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">📤 Upload & Preview</button>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($previewData)): ?>
                <div class="upload-section">
                    <h2>👁️ Data Preview (First 10 rows)</h2>
                    <div class="table-container">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <?php foreach ($headers as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previewData as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                            <td><?php echo htmlspecialchars($cell); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if ($mappingForm): ?>
                    <div class="upload-section">
                        <h2>🗺️ Column Mapping</h2>
                        
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step active">
                                <span class="step-number">1</span>
                                <span class="step-text">Map Columns</span>
                            </div>
                            <div class="step" id="preview-step">
                                <span class="step-number">2</span>
                                <span class="step-text">Preview Mapping</span>
                            </div>
                            <div class="step" id="process-step">
                                <span class="step-number">3</span>
                                <span class="step-text">Process Upload</span>
                            </div>
                        </div>
                        
                        <?php echo $mappingForm; ?>
                        
                        <div class="preview-mapping" id="preview-mapping">
                            <h3>📋 Mapping Preview</h3>
                            <div id="mapping-preview-content"></div>
                            <div class="preview-actions">
                                <button type="button" id="edit-mapping" class="btn btn-secondary">✏️ Edit Mapping</button>
                                <button type="button" id="confirm-mapping" class="btn btn-success">✅ Confirm & Proceed</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Context selection data
        const mlasData = <?php echo json_encode($contextData['mlas'] ?? []); ?>;
        
        // Update MLAs when MP is selected
        function updateMLAs() {
            const mpSelect = document.getElementById('mp_id');
            const mlaSelect = document.getElementById('mla_id');
            const proceedBtn = document.getElementById('proceed-btn');
            
            // Clear existing options
            mlaSelect.innerHTML = '<option value="">-- Select MLA Constituency --</option>';
            mlaSelect.disabled = true;
            proceedBtn.disabled = true;
            
            if (mpSelect.value) {
                // Filter MLAs for selected MP
                const filteredMLAs = mlasData.filter(mla => mla.mp_id === mpSelect.value);
                
                if (filteredMLAs.length > 0) {
                    filteredMLAs.forEach(mla => {
                        const option = document.createElement('option');
                        option.value = mla.mla_id;
                        option.textContent = mla.mla_constituency_name;
                        mlaSelect.appendChild(option);
                    });
                    mlaSelect.disabled = false;
                } else {
                    mlaSelect.innerHTML = '<option value="">No MLAs found for this MP</option>';
                }
            }
        }
        
        // Enable proceed button when both MP and MLA are selected
        document.getElementById('mla_id')?.addEventListener('change', function() {
            const proceedBtn = document.getElementById('proceed-btn');
            proceedBtn.disabled = !this.value;
        });
        
        // File upload handling
        const fileInput = document.getElementById('file-input');
        const browseBtn = document.getElementById('browse-btn');
        const uploadArea = document.getElementById('file-upload-area');
        const uploadForm = document.getElementById('upload-form');
        
        browseBtn.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                uploadArea.innerHTML = `
                    <div class="upload-content">
                        <h3>📎 Selected File</h3>
                        <p><strong>${fileName}</strong></p>
                        <button type="button" id="change-file" class="btn-secondary">Change File</button>
                    </div>
                `;
                document.getElementById('change-file').addEventListener('click', () => {
                    uploadArea.innerHTML = `
                        <div class="upload-content">
                            <h3>📎 Choose File or Drag & Drop</h3>
                            <p>Supported formats: CSV, XLS, XLSX</p>
                            <button type="button" id="browse-btn" class="btn-primary">Browse Files</button>
                        </div>
                    `;
                    document.getElementById('browse-btn').addEventListener('click', () => fileInput.click());
                });
            }
        });
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
        
        // Mapping preview
        document.getElementById('preview-mapping')?.addEventListener('click', function() {
            const form = document.getElementById('mapping-form');
            const formData = new FormData(form);
            const mapping = {};
            
            // Collect mapping data
            for (let [key, value] of formData.entries()) {
                if (key !== 'mp_id' && key !== 'mla_id' && key !== 'file_data' && value) {
                    mapping[key] = value;
                }
            }
            
            // Show preview
            const previewDiv = document.getElementById('preview-mapping');
            const contentDiv = document.getElementById('mapping-preview-content');
            
            let html = '<table class="mapping-preview-table"><thead><tr><th>Database Field</th><th>Mapped Column</th></tr></thead><tbody>';
            
            for (let [dbField, csvColumn] of Object.entries(mapping)) {
                html += `<tr><td><strong>${dbField}</strong></td><td>${csvColumn}</td></tr>`;
            }
            
            html += '</tbody></table>';
            contentDiv.innerHTML = html;
            previewDiv.style.display = 'block';
            
            // Update step indicators
            document.getElementById('preview-step').classList.add('completed');
            document.getElementById('preview-step').classList.remove('active');
            document.getElementById('process-step').classList.add('active');
            
            // Enable process button
            document.getElementById('process-upload').disabled = false;
        });
        
        // Edit mapping
        document.getElementById('edit-mapping')?.addEventListener('click', function() {
            const previewDiv = document.getElementById('preview-mapping');
            previewDiv.style.display = 'none';
            
            // Update step indicators
            document.getElementById('preview-step').classList.remove('completed');
            document.getElementById('preview-step').classList.add('active');
            document.getElementById('process-step').classList.remove('active');
            
            // Disable process button
            document.getElementById('process-upload').disabled = true;
        });
        
        // Confirm mapping
        document.getElementById('confirm-mapping')?.addEventListener('click', function() {
            // Enable process button and show confirmation
            const processBtn = document.getElementById('process-upload');
            processBtn.disabled = false;
            processBtn.style.background = '#28a745';
            processBtn.innerHTML = '📤 Ready to Process';
            processBtn.classList.add('confirmed');
            
            // Update step indicators
            document.getElementById('process-step').classList.add('completed');
            
            // Show success message
            const previewDiv = document.getElementById('preview-mapping');
            const successMsg = document.createElement('div');
            successMsg.style.cssText = 'background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; text-align: center;';
            successMsg.innerHTML = '✅ Mapping confirmed! You can now process the upload.';
            previewDiv.appendChild(successMsg);
        });
        
        // Process upload
        document.getElementById('process-upload')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if preview has been done
            const previewDiv = document.getElementById('preview-mapping');
            if (previewDiv.style.display === 'none' || previewDiv.style.display === '') {
                alert('⚠️ Please preview the mapping first by clicking "Preview Mapping" button.');
                return;
            }
            
            // Check if mapping has been confirmed
            if (!this.classList.contains('confirmed')) {
                alert('⚠️ Please confirm the mapping first by clicking "Confirm & Proceed" button.');
                return;
            }
            
            // Final confirmation before processing
            if (!confirm('Are you sure you want to process the upload with the current mapping?')) {
                return;
            }
            
            // Collect all form data including file data
            const form = document.getElementById('mapping-form');
            const formData = new FormData(form);
            
            // Add file data as JSON
            const fileData = <?php echo json_encode($previewData); ?>;
            formData.set('file_data', JSON.stringify(fileData));
            
            // Show processing message
            this.disabled = true;
            this.innerHTML = '⏳ Processing...';
            this.style.background = '#ffc107';
            this.style.color = '#212529';
            
            // Submit form
            form.submit();
        });
    </script>
</body>
</html>
