<?php
require_once 'config.php';
require_once 'UserMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('user', 'read')) {
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
?>

<?php
$pageTitle = 'User Details - ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
include 'header.php';
?>
<style>
        .user-detail-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .user-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info h1 {
            margin: 0 0 10px 0;
            font-size: 2em;
        }
        
        .user-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .detail-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .detail-card h3 {
            margin: 0 0 20px 0;
            color: #333;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            min-width: 120px;
        }
        
        .detail-value {
            color: #333;
            text-align: right;
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 15px;
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
            padding: 4px 12px;
            border-radius: 15px;
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
        
        .permissions-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .permission-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .permission-group h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        
        .permission-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .permission-item {
            font-size: 12px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .permission-item.has-permission {
            color: #28a745;
            font-weight: 600;
        }
        
        .permission-item::before {
            content: '✗';
            color: #dc3545;
        }
        
        .permission-item.has-permission::before {
            content: '✓';
            color: #28a745;
        }
        
        .activity-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-info {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .activity-description {
            color: #666;
            font-size: 14px;
        }
        
        .activity-time {
            color: #999;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .no-activity {
            text-align: center;
            color: #6c757d;
            padding: 40px;
        }
    </style>
    
    <div class="user-detail-container">
            <!-- User Header -->
            <div class="user-header">
                <div class="user-info">
                    <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <p><?php echo htmlspecialchars($user['username']); ?> • <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="user-actions">
                    <?php if ($auth->hasPermission('user', 'update')): ?>
                        <a href="user_edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-warning">Edit User</a>
                    <?php endif; ?>
                    <a href="user_view.php" class="btn btn-primary">Back to Users</a>
                </div>
            </div>
            
            <!-- User Details Grid -->
            <div class="detail-grid">
                <!-- Basic Information -->
                <div class="detail-card">
                    <h3>👤 Basic Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></span>
                    </div>
                </div>
                
                <!-- Account Status -->
                <div class="detail-card">
                    <h3>🔐 Account Status</h3>
                    <div class="detail-row">
                        <span class="detail-label">Role:</span>
                        <span class="detail-value">
                            <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Login:</span>
                        <span class="detail-value">
                            <?php 
                            if ($user['last_login']) {
                                echo date('M j, Y g:i A', strtotime($user['last_login']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Login Attempts:</span>
                        <span class="detail-value"><?php echo $user['login_attempts']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Locked:</span>
                        <span class="detail-value">
                            <?php 
                            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                                echo 'Yes (until ' . date('M j, Y g:i A', strtotime($user['locked_until'])) . ')';
                            } else {
                                echo 'No';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Permissions -->
            <div class="permissions-section">
                <h3>🔑 Permissions</h3>
                <?php 
                $permissions = json_decode($user['permissions'] ?? '{}', true) ?: [];
                $availablePermissions = [
                    'user' => ['create', 'read', 'update', 'delete'],
                    'booth' => ['create', 'read', 'update', 'delete'],
                    'mla' => ['create', 'read', 'update', 'delete'],
                    'mp' => ['create', 'read', 'update', 'delete'],
                    'voter' => ['create', 'read', 'update', 'delete'],
                    'report' => ['view', 'export']
                ];
                ?>
                <div class="permissions-grid">
                    <?php foreach ($availablePermissions as $module => $modulePermissions): ?>
                        <div class="permission-group">
                            <h4><?php echo ucfirst($module); ?></h4>
                            <div class="permission-list">
                                <?php foreach ($modulePermissions as $permission): ?>
                                    <div class="permission-item <?php echo isset($permissions[$module]) && in_array($permission, $permissions[$module]) ? 'has-permission' : ''; ?>">
                                        <?php echo ucfirst($permission); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Activity Timeline -->
            <div class="activity-section">
                <h3>📊 Account Activity</h3>
                <div class="activity-item">
                    <div class="activity-info">
                        <div class="activity-title">Account Created</div>
                        <div class="activity-description">User account was created by <?php echo htmlspecialchars($user['created_by']); ?></div>
                    </div>
                    <div class="activity-time">
                        <?php echo date('M j, Y g:i A', strtotime($user['created_datetime'])); ?>
                    </div>
                </div>
                
                <?php if ($user['updated_datetime']): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title">Last Updated</div>
                            <div class="activity-description">Account was last modified by <?php echo htmlspecialchars($user['updated_by'] ?: 'System'); ?></div>
                        </div>
                        <div class="activity-time">
                            <?php echo date('M j, Y g:i A', strtotime($user['updated_datetime'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($user['last_login']): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title">Last Login</div>
                            <div class="activity-description">User last logged into the system</div>
                        </div>
                        <div class="activity-time">
                            <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$user['last_login'] && !$user['updated_datetime']): ?>
                    <div class="no-activity">
                        <p>No recent activity recorded for this user.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    
    <?php include 'footer.php'; ?>
