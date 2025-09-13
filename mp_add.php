<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('mp', 'create')) {
    header('Location: mp_view.php?error=no_permission');
    exit;
}

$mpMaster = new MPMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
                // Clear form data after successful creation
                $_POST = [];
            } else {
                $message = 'Failed to create record!';
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
    <title>MP Master - Add Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>📊 MP Master - Add New Record</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-secondary">📊 View MPs</a>
                <a href="mp_add.php" class="btn btn-primary current-page">➕ Add MP</a>
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

        <!-- Add Form -->
        <div class="form-container">
            <h2>Add New MP Record</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="mp_constituency_code">Constituency Code: <span class="required">*</span></label>
                    <input type="number" id="mp_constituency_code" name="mp_constituency_code" 
                           value="<?php echo htmlspecialchars($_POST['mp_constituency_code'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter a unique constituency code</small>
                </div>
                
                <div class="form-group">
                    <label for="mp_constituency_name">Constituency Name: <span class="required">*</span></label>
                    <input type="text" id="mp_constituency_name" name="mp_constituency_name" 
                           value="<?php echo htmlspecialchars($_POST['mp_constituency_name'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter the full name of the constituency</small>
                </div>
                
                <div class="form-group">
                    <label for="state">State: <span class="required">*</span></label>
                    <input type="text" id="state" name="state" 
                           value="<?php echo htmlspecialchars($_POST['state'] ?? 'Tamil Nadu'); ?>" 
                           required>
                    <small class="form-help">Enter the state name (default: Tamil Nadu)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">➕ Create MP Record</button>
                    <a href="mp_view.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Quick Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Quick Info</h3>
                <p>Fill in the required fields to create a new MP constituency record. The system will automatically assign a unique ID and timestamp.</p>
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
