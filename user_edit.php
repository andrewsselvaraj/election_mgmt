<?php
require_once 'config.php';
require_once 'UserMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('user', 'update')) {
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

$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['username', 'email', 'first_name', 'last_name', 'role'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missingFields));
        }
        
        // Check if username already exists (excluding current user)
        if ($userMaster->usernameExists($_POST['username'], $userId)) {
            throw new Exception('Username already exists');
        }
        
        // Check if email already exists (excluding current user)
        if ($userMaster->emailExists($_POST['email'], $userId)) {
            throw new Exception('Email already exists');
        }
        
        // Prepare data
        $data = [
            'username' => trim($_POST['username']),
            'email' => trim($_POST['email']),
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'role' => $_POST['role'],
            'permissions' => json_encode($_POST['permissions'] ?? []),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
        ];
        
        // Handle password change if provided
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) {
                throw new Exception('Password must be at least 6 characters long');
            }
            $data['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $result = $userMaster->update($userId, $data);
        
        if ($result['success']) {
            $message = 'User updated successfully!';
            $messageType = 'success';
            // Refresh user data
            $user = $userMaster->readById($userId);
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Available roles
$roles = [
    'ADMIN' => 'Administrator',
    'MANAGER' => 'Manager',
    'USER' => 'Regular User'
];

// Available permissions
$availablePermissions = [
    'user' => ['create', 'read', 'update', 'delete'],
    'booth' => ['create', 'read', 'update', 'delete'],
    'mla' => ['create', 'read', 'update', 'delete'],
    'mp' => ['create', 'read', 'update', 'delete'],
    'voter' => ['create', 'read', 'update', 'delete'],
    'report' => ['view', 'export']
];

// Get current permissions
$currentPermissions = json_decode($user['permissions'], true) ?: [];
?>

<?php
$pageTitle = 'Edit User - ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
include 'header.php';
?>
<style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .permissions-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .permission-group {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .permission-group h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        
        .permission-checkboxes {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .permission-checkboxes label {
            font-size: 12px;
            font-weight: normal;
            display: flex;
            align-items: center;
            gap: 5px;
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
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .password-section {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin-bottom: 20px;
        }
        
        .password-section h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .password-section p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
    </style>
    
    <div class="page-header">
        <h1>✏️ Edit User</h1>
        <p>Update user information and permissions</p>
    </div>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" 
                               required>
                        <div class="help-text">Must be unique. Only letters, numbers, and underscores allowed.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               required>
                        <div class="help-text">Must be unique. Used for login and notifications.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="password-section">
                    <h4>🔐 Password</h4>
                    <p>Leave password field empty to keep current password. Enter new password to change it.</p>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password">
                        <div class="help-text">Minimum 6 characters. Leave empty to keep current password.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                        <?php echo $user['role'] === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Role determines default permissions and access level.</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" 
                               <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active">Active User</label>
                    </div>
                    <div class="help-text">Inactive users cannot log in to the system.</div>
                </div>
                
                <div class="form-group">
                    <label>Permissions</label>
                    <div class="permissions-section">
                        <p>Select specific permissions for this user. Leave unchecked to use role defaults.</p>
                        <div class="permissions-grid">
                            <?php foreach ($availablePermissions as $module => $permissions): ?>
                                <div class="permission-group">
                                    <h4><?php echo ucfirst($module); ?></h4>
                                    <div class="permission-checkboxes">
                                        <?php foreach ($permissions as $permission): ?>
                                            <label>
                                                <input type="checkbox" 
                                                       name="permissions[<?php echo $module; ?>][]" 
                                                       value="<?php echo $permission; ?>"
                                                       <?php echo isset($currentPermissions[$module]) && in_array($permission, $currentPermissions[$module]) ? 'checked' : ''; ?>>
                                                <?php echo ucfirst($permission); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="user_detail.php?id=<?php echo $user['user_id']; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const firstName = document.getElementById('first_name').value;
            const lastName = document.getElementById('last_name').value;
            const role = document.getElementById('role').value;
            const password = document.getElementById('password').value;
            
            if (!username || !email || !firstName || !lastName || !role) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }
            
            if (!email.includes('@')) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    </script>
    
    <?php include 'footer.php'; ?>
