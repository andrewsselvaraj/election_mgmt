<?php
require_once 'config.php';
require_once 'BoothMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$boothMaster = new BoothMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (!$auth->hasPermission('booth', 'create')) {
                    $message = 'You do not have permission to create booth records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    $data = [
                        'mla_id' => $_POST['mla_id'],
                        'Sl_No' => $_POST['Sl_No'],
                        'Polling_station_No' => $_POST['Polling_station_No'],
                        'Location_name_of_buiding' => $_POST['Location_name_of_buiding'],
                        'Polling_Areas' => $_POST['Polling_Areas'],
                        'Polling_Station_Type' => $_POST['Polling_Station_Type'],
                        'created_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
                    ];
                    
                    if ($boothMaster->stationExists($data['mla_id'], $data['Polling_station_No'])) {
                        $message = 'Polling station number already exists in this MLA constituency!';
                        $messageType = 'error';
                    } else {
                        if ($boothMaster->create($data)) {
                            $message = 'Booth record created successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to create booth record!';
                            $messageType = 'error';
                        }
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'update':
                if (!$auth->hasPermission('booth', 'update')) {
                    $message = 'You do not have permission to update booth records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    $data = [
                        'mla_id' => $_POST['mla_id'],
                        'Sl_No' => $_POST['Sl_No'],
                        'Polling_station_No' => $_POST['Polling_station_No'],
                        'Location_name_of_buiding' => $_POST['Location_name_of_buiding'],
                        'Polling_Areas' => $_POST['Polling_Areas'],
                        'Polling_Station_Type' => $_POST['Polling_Station_Type'],
                        'updated_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
                    ];
                    
                    if ($boothMaster->stationExists($data['mla_id'], $data['Polling_station_No'], $_POST['booth_id'])) {
                        $message = 'Polling station number already exists in this MLA constituency!';
                        $messageType = 'error';
                    } else {
                        if ($boothMaster->update($_POST['booth_id'], $data)) {
                            $message = 'Booth record updated successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update booth record!';
                            $messageType = 'error';
                        }
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                if (!$auth->hasPermission('booth', 'delete')) {
                    $message = 'You do not have permission to delete booth records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    if ($boothMaster->delete($_POST['booth_id'])) {
                        $message = 'Booth record deleted successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete booth record!';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Handle search
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$records = $searchTerm ? $boothMaster->search($searchTerm) : $boothMaster->readAll();

// Get MLA records for dropdown
$mlaRecords = $boothMaster->getMLARecords();

// Get statistics
$stats = $boothMaster->getStats();

// Get polling station types
$stationTypes = $boothMaster->getPollingStationTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booth Master Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>🏛️ Booth Master Management System</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_index.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="booth_upload.php" class="btn btn-secondary">📤 Upload Excel</a>
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
                <h3>MLA Constituencies</h3>
                <p class="stat-number"><?php echo $stats['total_mla_constituencies']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Regular Booths</h3>
                <p class="stat-number"><?php echo $stats['regular_booths']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Special Booths</h3>
                <p class="stat-number"><?php echo $stats['special_booths']; ?></p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <?php if ($auth->hasPermission('booth', 'create') || $auth->hasPermission('booth', 'update')): ?>
        <div class="form-container">
            <h2 id="form-title">Add New Booth Record</h2>
            <form id="booth-form" method="POST">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="booth_id" id="booth_id">
                
                <div class="form-group">
                    <label for="mla_id">MLA Constituency:</label>
                    <select id="mla_id" name="mla_id" required>
                        <option value="">Select MLA Constituency</option>
                        <?php foreach ($mlaRecords as $mla): ?>
                            <option value="<?php echo $mla['mla_id']; ?>">
                                <?php echo htmlspecialchars($mla['mla_constituency_name'] . ' (' . $mla['mp_constituency_name'] . ', ' . $mla['state'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="Sl_No">Serial Number:</label>
                        <input type="number" id="Sl_No" name="Sl_No" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="Polling_station_No">Polling Station No:</label>
                        <input type="text" id="Polling_station_No" name="Polling_station_No" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="Location_name_of_buiding">Location Name of Building:</label>
                    <input type="text" id="Location_name_of_buiding" name="Location_name_of_buiding" required>
                </div>
                
                <div class="form-group">
                    <label for="Polling_Areas">Polling Areas:</label>
                    <textarea id="Polling_Areas" name="Polling_Areas" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="Polling_Station_Type">Polling Station Type:</label>
                    <select id="Polling_Station_Type" name="Polling_Station_Type" required>
                        <?php foreach ($stationTypes as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="submit-btn">Add Record</button>
                    <button type="button" id="cancel-btn" style="display: none;">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Search and Records -->
        <div class="records-container">
            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search by station no, location, MLA constituency..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit">Search</button>
                    <?php if ($searchTerm): ?>
                        <a href="booth_index.php" class="clear-search">Clear Search</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>Sl No</th>
                            <th>Station No</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>MLA Constituency</th>
                            <th>MP Constituency</th>
                            <th>State</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="10" class="no-data">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['Sl_No']); ?></td>
                                    <td><?php echo htmlspecialchars($record['Polling_station_No']); ?></td>
                                    <td><?php echo htmlspecialchars($record['Location_name_of_buiding']); ?></td>
                                    <td>
                                        <span class="station-type <?php echo strtolower($record['Polling_Station_Type']); ?>">
                                            <?php echo htmlspecialchars($record['Polling_Station_Type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['mla_constituency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['mp_constituency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['state']); ?></td>
                                    <td><?php echo htmlspecialchars($record['created_by']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($record['created_datetime'])); ?></td>
                                    <td class="actions">
                                        <?php if ($auth->hasPermission('booth', 'update')): ?>
                                            <button onclick="editRecord('<?php echo $record['Booth_ID']; ?>')" class="edit-btn">Edit</button>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('booth', 'delete')): ?>
                                            <button onclick="deleteRecord('<?php echo $record['Booth_ID']; ?>', '<?php echo htmlspecialchars($record['Polling_station_No']); ?>')" class="delete-btn">Delete</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete this booth record?</p>
            <p id="delete-record-name"></p>
            <form method="POST" id="delete-form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="booth_id" id="delete-booth-id">
                <div class="modal-actions">
                    <button type="submit" class="confirm-delete">Yes, Delete</button>
                    <button type="button" onclick="closeModal()" class="cancel-delete">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="booth_script.js"></script>
    
    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
