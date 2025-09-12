<?php
require_once 'config.php';
require_once 'Auth.php';
require_once 'BoothMaster.php';
require_once 'dynamic_breadcrumb.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('booth', 'create')) {
    header('Location: unauthorized.php');
    exit;
}

// Get context and mapping data
$mpId = $_POST['mp_id'] ?? null;
$mlaId = $_POST['mla_id'] ?? null;
$fileData = json_decode($_POST['file_data'] ?? '[]', true);

// Validate context
if (!$mpId || !$mlaId) {
    die("Error: Missing MP or MLA context.");
}

if (empty($fileData)) {
    die("Error: No file data received.");
}

// Get mapping from form
$mapping = [
    'sl_no' => $_POST['sl_no'] ?? '',
    'polling_station_no' => $_POST['polling_station_no'] ?? '',
    'location_name_of_building' => $_POST['location_name_of_building'] ?? '',
    'polling_areas' => $_POST['polling_areas'] ?? '',
    'polling_station_type' => $_POST['polling_station_type'] ?? ''
];

// Validate required mappings
$requiredFields = ['sl_no', 'polling_station_no', 'location_name_of_building'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($mapping[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    die("Error: Missing required field mappings: " . implode(', ', $missingFields));
}

$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);
$breadcrumb = $dynamicBreadcrumb->getBreadcrumbForPage('process_mapped_upload.php', ['mp_id' => $mpId, 'mla_id' => $mlaId]);

// Process the data
$boothMaster = new BoothMaster($pdo);
$results = [
    'success' => 0,
    'errors' => [],
    'skipped' => 0
];

foreach ($fileData as $index => $row) {
    try {
        // Map CSV columns to database fields
        $boothData = [
            'mla_id' => $mlaId,
            'sl_no' => $row[$mapping['sl_no']] ?? '',
            'polling_station_no' => $row[$mapping['polling_station_no']] ?? '',
            'location_name_of_building' => $row[$mapping['location_name_of_building']] ?? '',
            'polling_areas' => $row[$mapping['polling_areas']] ?? '',
            'polling_station_type' => $row[$mapping['polling_station_type']] ?? 'Regular',
            'created_by' => $auth->getCurrentUser()['username'] ?? 'SYSTEM'
        ];
        
        // Validate required fields
        if (empty($boothData['sl_no']) || empty($boothData['polling_station_no']) || empty($boothData['location_name_of_building'])) {
            $results['skipped']++;
            $results['errors'][] = "Row " . ($index + 1) . ": Missing required data";
            continue;
        }
        
        // Check if booth already exists
        if ($boothMaster->stationExists($mlaId, $boothData['polling_station_no'])) {
            $results['skipped']++;
            $results['errors'][] = "Row " . ($index + 1) . ": Polling station '{$boothData['polling_station_no']}' already exists";
            continue;
        }
        
        // Create booth record
        $boothId = $boothMaster->create($boothData);
        $results['success']++;
        
    } catch (Exception $e) {
        $results['errors'][] = "Row " . ($index + 1) . ": " . $e->getMessage();
    }
}

// Get updated statistics
$stats = $boothMaster->getStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .results-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .result-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .result-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .result-stat {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        
        .result-stat.success {
            background: #28a745;
        }
        
        .result-stat.error {
            background: #dc3545;
        }
        
        .result-stat.skipped {
            background: #ffc107;
            color: #212529;
        }
        
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .error-list h4 {
            color: #721c24;
            margin-bottom: 10px;
        }
        
        .error-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .error-list li {
            color: #721c24;
            margin: 5px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
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
        
        .mapping-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .mapping-info h4 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .mapping-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .mapping-table th,
        .mapping-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        .mapping-table th {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo $breadcrumb; ?>
        
        <div class="results-container">
            <h1>📊 Upload Results</h1>
            
            <div class="result-card">
                <h2>📈 Processing Summary</h2>
                <div class="result-summary">
                    <div class="result-stat success">
                        <div>✅ Success</div>
                        <div><?php echo $results['success']; ?></div>
                    </div>
                    <div class="result-stat error">
                        <div>❌ Errors</div>
                        <div><?php echo count($results['errors']); ?></div>
                    </div>
                    <div class="result-stat skipped">
                        <div>⏭️ Skipped</div>
                        <div><?php echo $results['skipped']; ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($results['errors'])): ?>
                <div class="result-card">
                    <div class="error-list">
                        <h4>⚠️ Error Details</h4>
                        <ul>
                            <?php foreach ($results['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="result-card">
                <h2>🗺️ Column Mapping Used</h2>
                <div class="mapping-info">
                    <table class="mapping-table">
                        <thead>
                            <tr>
                                <th>Database Field</th>
                                <th>Mapped CSV Column</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $fieldLabels = [
                                'sl_no' => 'Serial Number',
                                'polling_station_no' => 'Polling Station Number',
                                'location_name_of_building' => 'Location Name of Building',
                                'polling_areas' => 'Polling Areas',
                                'polling_station_type' => 'Polling Station Type'
                            ];
                            
                            $requiredFields = ['sl_no', 'polling_station_no', 'location_name_of_building'];
                            
                            foreach ($mapping as $field => $column): ?>
                                <tr>
                                    <td><strong><?php echo $fieldLabels[$field] ?? $field; ?></strong></td>
                                    <td><?php echo htmlspecialchars($column ?: 'Not mapped'); ?></td>
                                    <td>
                                        <?php if (in_array($field, $requiredFields)): ?>
                                            <span style="color: #dc3545;">Required</span>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="result-card">
                <h2>📊 Current Statistics</h2>
                <div class="mapping-info">
                    <p><strong>Total Booths:</strong> <?php echo $stats['total']; ?></p>
                    <p><strong>Active Booths:</strong> <?php echo $stats['active']; ?></p>
                    <p><strong>Inactive Booths:</strong> <?php echo $stats['inactive']; ?></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="file_upload_preview.php?mp_id=<?php echo urlencode($mpId); ?>&mla_id=<?php echo urlencode($mlaId); ?>" class="btn btn-primary">
                    📤 Upload More Files
                </a>
                <a href="mla_detail.php?mp_id=<?php echo urlencode($mpId); ?>&mla_id=<?php echo urlencode($mlaId); ?>" class="btn btn-secondary">
                    🏛️ View MLA Details
                </a>
                <a href="booth_index.php" class="btn btn-success">
                    📋 Manage All Booths
                </a>
            </div>
        </div>
    </div>
</body>
</html>
