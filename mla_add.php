<?php
require_once 'config.php';
require_once 'MLAMaster.php';
require_once 'MPMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('mla', 'create')) {
    header('Location: mla_view.php?error=no_permission');
    exit;
}

$mlaMaster = new MLAMaster($pdo);
$mpMaster = new MPMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Get all MPs for dropdown
$mps = $mpMaster->readAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = [
            'mp_id' => $_POST['mp_id'],
            'mla_constituency_code' => $_POST['mla_constituency_code'],
            'mla_constituency_name' => $_POST['mla_constituency_name'],
            'created_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
        ];
        
        if ($mlaMaster->codeExists($data['mla_constituency_code'])) {
            $message = 'MLA constituency code already exists!';
            $messageType = 'error';
        } else {
            if ($mlaMaster->create($data)) {
                $message = 'MLA record created successfully!';
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
    <title>MLA Master - Add Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>🏛️ MLA Master - Add New Record</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_view.php" class="btn btn-secondary">🏛️ View MLAs</a>
                <a href="mla_add.php" class="btn btn-primary current-page">➕ Add MLA</a>
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

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add Form -->
        <div class="form-container">
            <h2>Add New MLA Record</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="mp_id">MP Constituency: <span class="required">*</span></label>
                    <select id="mp_id" name="mp_id" required>
                        <option value="">Select MP Constituency</option>
                        <?php foreach ($mps as $mp): ?>
                            <option value="<?php echo $mp['mp_id']; ?>" 
                                    <?php echo (($_POST['mp_id'] ?? '') == $mp['mp_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mp['mp_constituency_name'] . ' (' . $mp['mp_constituency_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-help">Select the MP constituency this MLA belongs to</small>
                </div>
                
                <div class="form-group">
                    <label for="mla_constituency_code">MLA Constituency Code: <span class="required">*</span></label>
                    <input type="number" id="mla_constituency_code" name="mla_constituency_code" 
                           value="<?php echo htmlspecialchars($_POST['mla_constituency_code'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter a unique MLA constituency code</small>
                </div>
                
                <div class="form-group">
                    <label for="mla_constituency_name">MLA Constituency Name: <span class="required">*</span></label>
                    <input type="text" id="mla_constituency_name" name="mla_constituency_name" 
                           value="<?php echo htmlspecialchars($_POST['mla_constituency_name'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter the full name of the MLA constituency</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">➕ Create MLA Record</button>
                    <a href="mla_view.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Quick Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Quick Info</h3>
                <p>Fill in the required fields to create a new MLA constituency record. Make sure to select the correct MP constituency.</p>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const mpId = document.getElementById('mp_id').value;
            const code = document.getElementById('mla_constituency_code').value;
            const name = document.getElementById('mla_constituency_name').value;
            
            if (!mpId || !code || !name) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            if (code <= 0) {
                e.preventDefault();
                alert('MLA constituency code must be a positive number');
                return;
            }
        });

        // Auto-focus on first field
        document.getElementById('mp_id').focus();
    </script>
</body>
</html>
