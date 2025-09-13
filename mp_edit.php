<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('mp', 'update')) {
    header('Location: mp_view.php?error=no_permission');
    exit;
}

$mpMaster = new MPMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Get MP ID from URL
$mpId = $_GET['mp_id'] ?? '';
if (!$mpId) {
    header('Location: mp_view.php?error=no_id');
    exit;
}

// Get MP record
$mp = $mpMaster->readById($mpId);
if (!$mp) {
    header('Location: mp_view.php?error=not_found');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = [
            'mp_id' => $mpId,
            'mp_constituency_code' => $_POST['mp_constituency_code'],
            'mp_constituency_name' => $_POST['mp_constituency_name'],
            'state' => $_POST['state'],
            'updated_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
        ];
        
        // Check if code exists for other records
        if ($mpMaster->codeExists($data['mp_constituency_code'], $mpId)) {
            $message = 'Constituency code already exists for another record!';
            $messageType = 'error';
        } else {
            if ($mpMaster->update($data)) {
                $message = 'MP record updated successfully!';
                $messageType = 'success';
                // Refresh the MP data
                $mp = $mpMaster->readById($mpId);
            } else {
                $message = 'Failed to update record!';
                $messageType = 'error';
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MP Master - Edit Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>📊 MP Master - Edit Record</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-secondary">📊 View MPs</a>
                <a href="mp_add.php" class="btn btn-secondary">➕ Add MP</a>
                <a href="mla_index.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="booth_index.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="upload.php" class="btn btn-secondary">📤 Upload Excel</a>
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

        <!-- Edit Form -->
        <div class="form-container">
            <h2>Edit MP Record</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="mp_constituency_code">Constituency Code: <span class="required">*</span></label>
                    <input type="number" id="mp_constituency_code" name="mp_constituency_code" 
                           value="<?php echo htmlspecialchars($mp['mp_constituency_code']); ?>" 
                           required>
                    <small class="form-help">Enter a unique constituency code</small>
                </div>
                
                <div class="form-group">
                    <label for="mp_constituency_name">Constituency Name: <span class="required">*</span></label>
                    <input type="text" id="mp_constituency_name" name="mp_constituency_name" 
                           value="<?php echo htmlspecialchars($mp['mp_constituency_name']); ?>" 
                           required>
                    <small class="form-help">Enter the full name of the constituency</small>
                </div>
                
                <div class="form-group">
                    <label for="state">State: <span class="required">*</span></label>
                    <input type="text" id="state" name="state" 
                           value="<?php echo htmlspecialchars($mp['state']); ?>" 
                           required>
                    <small class="form-help">Enter the state name</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">💾 Update MP Record</button>
                    <a href="mp_view.php" class="btn btn-secondary">Cancel</a>
                    <a href="mp_detail.php?mp_id=<?php echo $mp['mp_id']; ?>" class="btn btn-primary">View Details</a>
                </div>
            </form>
        </div>

        <!-- Record Info -->
        <div class="info-container">
            <h3>Record Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Record ID:</strong> <?php echo htmlspecialchars($mp['mp_id']); ?>
                </div>
                <div class="info-item">
                    <strong>Created By:</strong> <?php echo htmlspecialchars($mp['created_by']); ?>
                </div>
                <div class="info-item">
                    <strong>Created Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($mp['created_at'])); ?>
                </div>
                <div class="info-item">
                    <strong>Last Updated:</strong> <?php echo $mp['updated_at'] ? date('Y-m-d H:i:s', strtotime($mp['updated_at'])) : 'Never'; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const code = document.getElementById('mp_constituency_code').value;
            const name = document.getElementById('mp_constituency_name').value;
            const state = document.getElementById('state').value;
            
            if (!code || !name || !state) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            if (code <= 0) {
                e.preventDefault();
                alert('Constituency code must be a positive number');
                return;
            }
        });

        // Auto-focus on first field
        document.getElementById('mp_constituency_code').focus();
    </script>
</body>
</html>
