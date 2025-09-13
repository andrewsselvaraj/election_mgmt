<?php
require_once 'config.php';
require_once 'UserMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$userMaster = new UserMaster($pdo);
$currentUser = $auth->getCurrentUser();

// Handle search
$searchTerm = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

// Get users based on search and filter
if (!empty($searchTerm)) {
    $users = $userMaster->search($searchTerm);
} elseif (!empty($roleFilter)) {
    $users = $userMaster->getByRole($roleFilter);
} else {
    $users = $userMaster->readAll();
}

// Get statistics
$stats = $userMaster->getStatistics();

// Get all roles for filter dropdown
$roles = ['ADMIN', 'MANAGER', 'USER'];
?>

<?php
$pageTitle = 'User Management - Election Management System';
include 'header.php';
?>
<style>
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 2em;
            font-weight: bold;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        .search-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-filters input, .search-filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-filters button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .search-filters button:hover {
            background: #0056b3;
        }
        
        .user-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .user-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th {
            background: #343a40;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .user-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .user-table tr:hover {
            background: #f8f9fa;
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
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm:hover {
            opacity: 0.8;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .add-user-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .add-user-btn:hover {
            background: #218838;
            color: white;
        }
    </style>
    
    <div class="page-header">
        <h1>👥 User Management</h1>
        <p>Manage system users and their permissions</p>
    </div>
        
        <!-- Statistics Cards -->
        <div class="user-stats">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['active']; ?></h3>
                <p>Active Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['inactive']; ?></h3>
                <p>Inactive Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($users); ?></h3>
                <p>Filtered Results</p>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div class="search-filters">
            <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($searchTerm); ?>" style="min-width: 200px;">
                
                <select name="role">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role; ?>" <?php echo $roleFilter === $role ? 'selected' : ''; ?>>
                            <?php echo $role; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit">🔍 Search</button>
                <a href="user_view.php" class="btn-sm" style="background: #6c757d; color: white;">Clear</a>
            </form>
            
            <?php if ($auth->hasPermission('user', 'create')): ?>
                <a href="user_add.php" class="add-user-btn">➕ Add New User</a>
            <?php endif; ?>
        </div>
        
        <!-- Users Table -->
        <div class="user-table">
            <?php if (empty($users)): ?>
                <div class="no-data">
                    <h3>No users found</h3>
                    <p>No users match your search criteria.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($user['last_login']) {
                                        echo date('M j, Y g:i A', strtotime($user['last_login']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_datetime'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($auth->hasPermission('user', 'read')): ?>
                                            <a href="user_detail.php?id=<?php echo $user['user_id']; ?>" class="btn-sm btn-primary">View</a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('user', 'update')): ?>
                                            <a href="user_edit.php?id=<?php echo $user['user_id']; ?>" class="btn-sm btn-warning">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('user', 'delete')): ?>
                                            <a href="user_delete.php?id=<?php echo $user['user_id']; ?>" 
                                               class="btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Role Statistics -->
        <?php if (!empty($stats['by_role'])): ?>
            <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h3>Users by Role</h3>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <?php foreach ($stats['by_role'] as $roleStat): ?>
                        <div style="text-align: center;">
                            <div style="font-size: 2em; font-weight: bold; color: #007bff;">
                                <?php echo $roleStat['count']; ?>
                            </div>
                            <div style="color: #6c757d;">
                                <?php echo $roleStat['role']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
