<?php
require_once 'config.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requirePermission('users', 'read');

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_user':
                try {
                    $userData = [
                        'username' => $_POST['username'],
                        'email' => $_POST['email'],
                        'password' => $_POST['password'],
                        'first_name' => $_POST['first_name'],
                        'last_name' => $_POST['last_name']
                    ];
                    
                    if ($auth->createUser($userData)) {
                        $message = 'User created successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to create user!';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'assign_role':
                try {
                    $userId = $_POST['user_id'];
                    $roleId = $_POST['role_id'];
                    $assignedBy = $auth->getCurrentUser()['user_id'];
                    
                    if ($auth->assignRole($userId, $roleId, $assignedBy)) {
                        $message = 'Role assigned successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to assign role!';
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

// Get all users and roles
$users = $auth->getAllUsers();
$roles = $auth->getAllRoles();
$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>👥 User Management</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">🏠 Dashboard</a>
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
        
        <!-- Add User Form -->
        <?php if ($auth->hasPermission('users', 'create')): ?>
        <div class="form-container">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Users List -->
        <div class="records-container">
            <h2>All Users</h2>
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="no-data">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['roles'] ?? 'No roles'); ?></td>
                                    <td>
                                        <span class="status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td class="actions">
                                        <?php if ($auth->hasPermission('users', 'update')): ?>
                                            <button onclick="assignRole('<?php echo $user['user_id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" class="edit-btn">Assign Role</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Roles List -->
        <div class="records-container">
            <h2>Available Roles</h2>
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                <td><?php echo htmlspecialchars($role['role_description']); ?></td>
                                <td>
                                    <?php 
                                    $permissions = json_decode($role['permissions'], true);
                                    if ($permissions) {
                                        foreach ($permissions as $module => $actions) {
                                            echo "<strong>$module:</strong> " . implode(', ', $actions) . "<br>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($role['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Assign Role Modal -->
    <div id="role-modal" class="modal">
        <div class="modal-content">
            <h3>Assign Role</h3>
            <p>Assign role to: <span id="assign-user-name"></span></p>
            <form method="POST" id="assign-role-form">
                <input type="hidden" name="action" value="assign_role">
                <input type="hidden" name="user_id" id="assign-user-id">
                
                <div class="form-group">
                    <label for="role_id">Select Role:</label>
                    <select id="role_id" name="role_id" required>
                        <option value="">Select a role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>">
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Assign Role</button>
                    <button type="button" onclick="closeRoleModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function assignRole(userId, username) {
            document.getElementById('assign-user-id').value = userId;
            document.getElementById('assign-user-name').textContent = username;
            document.getElementById('role-modal').style.display = 'block';
        }
        
        function closeRoleModal() {
            document.getElementById('role-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('role-modal');
            if (e.target === modal) {
                closeRoleModal();
            }
        });
    </script>
    
    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .user-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status.inactive {
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
