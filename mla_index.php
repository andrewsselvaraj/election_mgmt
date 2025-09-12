<?php
require_once 'config.php';
require_once 'MLAMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$mlaMaster = new MLAMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (!$auth->hasPermission('mla', 'create')) {
                    $message = 'You do not have permission to create MLA records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    $data = [
                        'mp_id' => $_POST['mp_id'],
                        'mla_constituency_code' => $_POST['mla_constituency_code'],
                        'mla_constituency_name' => $_POST['mla_constituency_name'],
                        'created_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
                    ];
                    
                    if ($mlaMaster->codeExists($data['mp_id'], $data['mla_constituency_code'])) {
                        $message = 'MLA constituency code already exists in this MP constituency!';
                        $messageType = 'error';
                    } else {
                        if ($mlaMaster->create($data)) {
                            $message = 'MLA record created successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to create MLA record!';
                            $messageType = 'error';
                        }
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'update':
                if (!$auth->hasPermission('mla', 'update')) {
                    $message = 'You do not have permission to update MLA records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    $data = [
                        'mp_id' => $_POST['mp_id'],
                        'mla_constituency_code' => $_POST['mla_constituency_code'],
                        'mla_constituency_name' => $_POST['mla_constituency_name'],
                        'updated_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
                    ];
                    
                    if ($mlaMaster->codeExists($data['mp_id'], $data['mla_constituency_code'], $_POST['mla_id'])) {
                        $message = 'MLA constituency code already exists in this MP constituency!';
                        $messageType = 'error';
                    } else {
                        if ($mlaMaster->update($_POST['mla_id'], $data)) {
                            $message = 'MLA record updated successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update MLA record!';
                            $messageType = 'error';
                        }
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                if (!$auth->hasPermission('mla', 'delete')) {
                    $message = 'You do not have permission to delete MLA records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    if ($mlaMaster->delete($_POST['mla_id'])) {
                        $message = 'MLA record deleted successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete MLA record!';
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
$records = $searchTerm ? $mlaMaster->search($searchTerm) : $mlaMaster->readAll();

// Get MP records for dropdown
$mpRecords = $mlaMaster->getMPRecords();

// Get statistics
$stats = $mlaMaster->getStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLA Master Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>MLA Master Management System</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="booth_index.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="mla_upload.php" class="btn btn-secondary">📤 Upload Excel</a>
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
        
        <!-- Dynamic Breadcrumb Navigation -->
        <?php 
        require_once 'dynamic_breadcrumb.php';
        $dynamicBreadcrumb = new DynamicBreadcrumb($pdo);
        echo $dynamicBreadcrumb->getBreadcrumbForPage('mla_index.php');
        ?>
        
        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total MLAs</h3>
                <p class="stat-number"><?php echo $stats['total_mlas']; ?></p>
            </div>
            <div class="stat-card">
                <h3>MP Constituencies</h3>
                <p class="stat-number"><?php echo $stats['total_mp_constituencies']; ?></p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <div class="form-container">
            <h2 id="form-title">Add New MLA Record</h2>
            <form id="mla-form" method="POST">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="mla_id" id="mla_id">
                
                <div class="form-group">
                    <label for="mp_id">MP Constituency:</label>
                    <select id="mp_id" name="mp_id" required>
                        <option value="">Select MP Constituency</option>
                        <?php foreach ($mpRecords as $mp): ?>
                            <option value="<?php echo $mp['mp_id']; ?>">
                                <?php echo htmlspecialchars($mp['mp_constituency_name'] . ' (' . $mp['state'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="mla_constituency_code">MLA Constituency Code:</label>
                    <input type="number" id="mla_constituency_code" name="mla_constituency_code" required>
                </div>
                
                <div class="form-group">
                    <label for="mla_constituency_name">MLA Constituency Name:</label>
                    <input type="text" id="mla_constituency_name" name="mla_constituency_name" required>
                </div>
                
                <div class="form-group">
                    <label for="created_by">Created By:</label>
                    <input type="text" id="created_by" name="created_by" required>
                </div>
                
                <div class="form-group" id="updated_by_group" style="display: none;">
                    <label for="updated_by">Updated By:</label>
                    <input type="text" id="updated_by" name="updated_by">
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="submit-btn">Add Record</button>
                    <button type="button" id="cancel-btn" style="display: none;">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Search and Records -->
        <div class="records-container">
            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search by MLA name, MP constituency, or state..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit">Search</button>
                    <?php if ($searchTerm): ?>
                        <a href="mla_index.php" class="clear-search">Clear Search</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>MLA Code</th>
                            <th>MLA Constituency</th>
                            <th>MP Constituency</th>
                            <th>State</th>
                            <th>Created By</th>
                            <th>Updated By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="8" class="no-data">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['mla_constituency_code']); ?></td>
                                    <td><?php echo htmlspecialchars($record['mla_constituency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['mp_constituency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['state']); ?></td>
                                    <td><?php echo htmlspecialchars($record['created_by']); ?></td>
                                    <td><?php echo htmlspecialchars($record['updated_by'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($record['created_at'])); ?></td>
                                    <td class="actions">
                                        <a href="mla_detail.php?mp_id=<?php echo $record['mp_id']; ?>&mla_id=<?php echo $record['mla_id']; ?>" class="btn btn-primary">View Booths</a>
                                        <button onclick="editRecord('<?php echo $record['mla_id']; ?>')" class="edit-btn">Edit</button>
                                        <button onclick="deleteRecord('<?php echo $record['mla_id']; ?>', '<?php echo htmlspecialchars($record['mla_constituency_name']); ?>')" class="delete-btn">Delete</button>
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
            <p>Are you sure you want to delete this MLA record?</p>
            <p id="delete-record-name"></p>
            <form method="POST" id="delete-form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="mla_id" id="delete-mla-id">
                <div class="modal-actions">
                    <button type="submit" class="confirm-delete">Yes, Delete</button>
                    <button type="button" onclick="closeModal()" class="cancel-delete">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="mla_script.js"></script>
</body>
</html>
