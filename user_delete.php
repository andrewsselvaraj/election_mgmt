<?php
require_once 'config.php';
require_once 'UserMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('user', 'delete')) {
    header('Location: user_view.php?error=no_permission');
    exit;
}

$userMaster = new UserMaster($pdo);
$userId = $_GET['id'] ?? '';

if (empty($userId)) {
    header('Location: user_view.php?error=invalid_user');
    exit;
}

$user = $userMaster->readById($userId);

if (!$user) {
    header('Location: user_view.php?error=user_not_found');
    exit;
}

$currentUser = $auth->getCurrentUser();

// Prevent users from deleting themselves
if ($userId === $currentUser['user_id']) {
    header('Location: user_view.php?error=cannot_delete_self');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $result = $userMaster->delete($userId, $currentUser['first_name'] . ' ' . $currentUser['last_name']);
    
    if ($result['success']) {
        header('Location: user_view.php?message=user_deleted');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<?php
$pageTitle = 'Delete User - ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
include 'header.php';
?>
<style>
        .delete-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .warning-section {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .warning-section h3 {
            margin: 0 0 10px 0;
            color: #721c24;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .user-info h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-manager {
            background: #ffc107;
            color: #212529;
        }
        
        .role-user {
            background: #28a745;
            color: white;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .consequences {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .consequences h4 {
            margin: 0 0 15px 0;
            color: #856404;
        }
        
        .consequences ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .consequences li {
            margin-bottom: 8px;
        }
    </style>
    
    <div class="page-header">
        <h1>🗑️ Delete User</h1>
        <p>Confirm deletion of user account</p>
    </div>
        
        <div class="delete-container">
            <?php if (isset($error)): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="warning-section">
                <h3>⚠️ Warning: This action cannot be undone!</h3>
                <p>You are about to permanently delete this user account. This action will:</p>
                <ul>
                    <li>Remove the user's access to the system</li>
                    <li>Mark the user record as deleted (soft delete)</li>
                    <li>Preserve audit trail and historical data</li>
                </ul>
            </div>
            
            <div class="user-info">
                <h4>User Information</h4>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role:</span>
                    <span class="info-value">
                        <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                            <?php echo $user['role']; ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($user['created_datetime'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login:</span>
                    <span class="info-value">
                        <?php 
                        if ($user['last_login']) {
                            echo date('M j, Y g:i A', strtotime($user['last_login']));
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="consequences">
                <h4>📋 What happens after deletion:</h4>
                <ul>
                    <li>The user will be marked as "DELETED" in the database</li>
                    <li>They will no longer be able to log into the system</li>
                    <li>Their username and email will remain reserved</li>
                    <li>All audit records will be preserved</li>
                    <li>Any data they created will remain in the system</li>
                    <li>This action will be logged in the audit trail</li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="form-actions">
                    <a href="user_detail.php?id=<?php echo $user['user_id']; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="confirm_delete" class="btn btn-danger" 
                            onclick="return confirm('Are you absolutely sure you want to delete this user? This action cannot be undone!')">
                        🗑️ Delete User
                    </button>
                </div>
            </form>
        </div>
    
    <?php include 'footer.php'; ?>
