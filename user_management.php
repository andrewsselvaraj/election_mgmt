<?php
require_once 'config.php';
require_once 'UserMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('user', 'read')) {
    header('Location: unauthorized.php');
    exit;
}

$userMaster = new UserMaster($pdo);
$currentUser = $auth->getCurrentUser();

// Get statistics
$stats = $userMaster->getStatistics();

// Get recent users
$recentUsers = $userMaster->readAll();
$recentUsers = array_slice($recentUsers, 0, 5); // Show only 5 most recent

$pageTitle = 'User Management Dashboard - Election Management System';
include 'header.php';
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .main-content {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .widget {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .widget h3 {
        margin: 0 0 15px 0;
        color: #333;
        border-bottom: 2px solid #f8f9fa;
        padding-bottom: 10px;
    }
    
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stat-item {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    
    .stat-item h4 {
        margin: 0 0 10px 0;
        font-size: 2em;
        font-weight: bold;
    }
    
    .stat-item p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .action-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .action-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
    }
    
    .action-btn:hover {
        background: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .action-btn .icon {
        font-size: 24px;
    }
    
    .action-btn .text {
        flex: 1;
    }
    
    .action-btn .text h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
    }
    
    .action-btn .text p {
        margin: 0;
        font-size: 12px;
        color: #666;
    }
    
    .recent-users {
        margin-top: 20px;
    }
    
    .user-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .user-item:last-child {
        border-bottom: none;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
    }
    
    .user-details h5 {
        margin: 0 0 2px 0;
        font-size: 14px;
        color: #333;
    }
    
    .user-details p {
        margin: 0;
        font-size: 12px;
        color: #666;
    }
    
    .role-badge {
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
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
    
    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #28a745;
    }
    
    .status-indicator.inactive {
        background: #dc3545;
    }
    
    .no-data {
        text-align: center;
        color: #6c757d;
        padding: 20px;
        font-style: italic;
    }
    
    .role-stats {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .role-stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }
    
    .role-stat-label {
        font-size: 14px;
        color: #666;
    }
    
    .role-stat-count {
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            grid-template-columns: 1fr;
        }
        
        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="page-header">
    <h1>👥 User Management Dashboard</h1>
    <p>Manage system users, roles, and permissions</p>
</div>

<!-- Quick Statistics -->
<div class="quick-stats">
    <div class="stat-item">
        <h4><?php echo $stats['total']; ?></h4>
        <p>Total Users</p>
    </div>
    <div class="stat-item">
        <h4><?php echo $stats['active']; ?></h4>
        <p>Active Users</p>
    </div>
    <div class="stat-item">
        <h4><?php echo $stats['inactive']; ?></h4>
        <p>Inactive Users</p>
    </div>
    <div class="stat-item">
        <h4><?php echo count($stats['by_role']); ?></h4>
        <p>User Roles</p>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Main Content -->
    <div class="main-content">
        <h2>🚀 Quick Actions</h2>
        <div class="action-buttons">
            <?php if ($auth->hasPermission('user', 'create')): ?>
                <a href="user_add.php" class="action-btn">
                    <div class="icon">➕</div>
                    <div class="text">
                        <h4>Add New User</h4>
                        <p>Create a new user account</p>
                    </div>
                </a>
            <?php endif; ?>
            
            <a href="user_view.php" class="action-btn">
                <div class="icon">👥</div>
                <div class="text">
                    <h4>View All Users</h4>
                    <p>Browse and manage users</p>
                </div>
            </a>
            
            <a href="user_view.php?role=ADMIN" class="action-btn">
                <div class="icon">👑</div>
                <div class="text">
                    <h4>Administrators</h4>
                    <p>View admin users</p>
                </div>
            </a>
            
            <a href="user_view.php?role=MANAGER" class="action-btn">
                <div class="icon">👨‍💼</div>
                <div class="text">
                    <h4>Managers</h4>
                    <p>View manager users</p>
                </div>
            </a>
        </div>
        
        <!-- Recent Users -->
        <div class="recent-users">
            <h3>📋 Recent Users</h3>
            <?php if (empty($recentUsers)): ?>
                <div class="no-data">No users found</div>
            <?php else: ?>
                <?php foreach ($recentUsers as $user): ?>
                    <div class="user-item">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                            <div class="user-details">
                                <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                <p><?php echo htmlspecialchars($user['username']); ?> • <?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                <?php echo $user['role']; ?>
                            </span>
                            <div class="status-indicator <?php echo $user['is_active'] ? '' : 'inactive'; ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Role Statistics -->
        <div class="widget">
            <h3>📊 Users by Role</h3>
            <div class="role-stats">
                <?php if (!empty($stats['by_role'])): ?>
                    <?php foreach ($stats['by_role'] as $roleStat): ?>
                        <div class="role-stat-item">
                            <span class="role-stat-label"><?php echo $roleStat['role']; ?></span>
                            <span class="role-stat-count"><?php echo $roleStat['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">No role data available</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="widget">
            <h3>ℹ️ System Info</h3>
            <div style="font-size: 14px; color: #666;">
                <p><strong>Current User:</strong><br><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></p>
                <p><strong>Your Role:</strong><br><?php echo $currentUser['role'] ?? 'USER'; ?></p>
                <p><strong>Last Login:</strong><br>
                    <?php 
                    if ($currentUser['last_login']) {
                        echo date('M j, Y g:i A', strtotime($currentUser['last_login']));
                    } else {
                        echo 'Never';
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="widget">
            <h3>🔗 Quick Links</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="user_view.php" style="color: #007bff; text-decoration: none; font-size: 14px;">📋 All Users</a>
                <a href="user_view.php?role=ADMIN" style="color: #007bff; text-decoration: none; font-size: 14px;">👑 Administrators</a>
                <a href="user_view.php?role=MANAGER" style="color: #007bff; text-decoration: none; font-size: 14px;">👨‍💼 Managers</a>
                <a href="user_view.php?role=USER" style="color: #007bff; text-decoration: none; font-size: 14px;">👤 Regular Users</a>
                <?php if ($auth->hasPermission('user', 'create')): ?>
                    <a href="user_add.php" style="color: #28a745; text-decoration: none; font-size: 14px;">➕ Add New User</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>