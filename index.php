<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$mpMaster = new MPMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (!$auth->hasPermission('mp', 'create')) {
                    $message = 'You do not have permission to create MP records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    $data = [
                        'mp_constituency_code' => $_POST['mp_constituency_code'],
                        'mp_constituency_name' => $_POST['mp_constituency_name'],
                        'state' => $_POST['state'],
                        'created_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
                    ];
                    
                    if ($mpMaster->codeExists($data['mp_constituency_code'])) {
                        $message = 'Constituency code already exists!';
                        $messageType = 'error';
                    } else {
                        if ($mpMaster->create($data)) {
                            $message = 'MP record created successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to create record!';
                            $messageType = 'error';
                        }
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'update':
                if (!$auth->hasPermission('mp', 'update')) {
                    $message = 'You do not have permission to update MP records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    $data = [
                        'mp_constituency_code' => $_POST['mp_constituency_code'],
                        'mp_constituency_name' => $_POST['mp_constituency_name'],
                        'state' => $_POST['state'],
                        'updated_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
                    ];
                    
                    if ($mpMaster->codeExists($data['mp_constituency_code'], $_POST['mp_id'])) {
                        $message = 'Constituency code already exists!';
                        $messageType = 'error';
                    } else {
                        if ($mpMaster->update($_POST['mp_id'], $data)) {
                            $message = 'MP record updated successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update record!';
                            $messageType = 'error';
                        }
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                if (!$auth->hasPermission('mp', 'delete')) {
                    $message = 'You do not have permission to delete MP records!';
                    $messageType = 'error';
                    break;
                }
                
                try {
                    if ($mpMaster->delete($_POST['mp_id'])) {
                        $message = 'MP record deleted successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete record!';
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
$records = $searchTerm ? $mpMaster->search($searchTerm) : $mpMaster->readAll();

// Get states for dropdown
$states = $mpMaster->getStates();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MP Master Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>MP Master Management System</h1>
            <div class="header-actions">
                <a href="upload.php" class="btn btn-secondary">📤 Upload Excel</a>
                <a href="mla_index.php" class="btn btn-primary">🏛️ MLA Master</a>
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
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <?php if ($auth->hasPermission('mp', 'create') || $auth->hasPermission('mp', 'update')): ?>
        <div class="form-container">
            <h2 id="form-title">Add New MP Record</h2>
            <form id="mp-form" method="POST">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="mp_id" id="mp_id">
                
                <div class="form-group">
                    <label for="mp_constituency_code">Constituency Code:</label>
                    <input type="number" id="mp_constituency_code" name="mp_constituency_code" required>
                </div>
                
                <div class="form-group">
                    <label for="mp_constituency_name">Constituency Name:</label>
                    <input type="text" id="mp_constituency_name" name="mp_constituency_name" required>
                </div>
                
                <div class="form-group">
                    <label for="state">State:</label>
                    <input type="text" id="state" name="state" list="states" value="Tamil Nadu" required>
                    <datalist id="states">
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo htmlspecialchars($state); ?>">
                        <?php endforeach; ?>
                    </datalist>
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
        <?php endif; ?>
        
        <!-- Search and Records -->
        <div class="records-container">
            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search by name or state..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit">Search</button>
                    <?php if ($searchTerm): ?>
                        <a href="index.php" class="clear-search">Clear Search</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Constituency Name</th>
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
                                <td colspan="7" class="no-data">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['mp_constituency_code']); ?></td>
                                    <td><?php echo htmlspecialchars($record['mp_constituency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['state']); ?></td>
                                    <td><?php echo htmlspecialchars($record['created_by']); ?></td>
                                    <td><?php echo htmlspecialchars($record['updated_by'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($record['created_at'])); ?></td>
                                    <td class="actions">
                                        <?php if ($auth->hasPermission('mp', 'update')): ?>
                                            <button onclick="editRecord('<?php echo $record['mp_id']; ?>')" class="edit-btn">Edit</button>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('mp', 'delete')): ?>
                                            <button onclick="deleteRecord('<?php echo $record['mp_id']; ?>', '<?php echo htmlspecialchars($record['mp_constituency_name']); ?>')" class="delete-btn">Delete</button>
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
            <p>Are you sure you want to delete this record?</p>
            <p id="delete-record-name"></p>
            <form method="POST" id="delete-form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="mp_id" id="delete-mp-id">
                <div class="modal-actions">
                    <button type="submit" class="confirm-delete">Yes, Delete</button>
                    <button type="button" onclick="closeModal()" class="cancel-delete">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>