<?php
require_once 'config.php';
require_once 'UserMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('user', 'create')) {
    header('Location: user_view.php?error=no_permission');
    exit;
}

$userMaster = new UserMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missingFields));
        }
        
        // Check if username already exists
        if ($userMaster->usernameExists($_POST['username'])) {
            throw new Exception('Username already exists');
        }
        
        // Check if email already exists
        if ($userMaster->emailExists($_POST['email'])) {
            throw new Exception('Email already exists');
        }
        
        // Validate password strength
        if (strlen($_POST['password']) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        
        // Prepare data
        $data = [
            'username' => trim($_POST['username']),
            'email' => trim($_POST['email']),
            'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'role' => $_POST['role'],
            'permissions' => json_encode($_POST['permissions'] ?? []),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
        ];
        
        $result = $userMaster->create($data);
        
        if ($result['success']) {
            $message = 'User created successfully!';
            $messageType = 'success';
            // Clear form data after successful creation
            $_POST = [];
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
?>

<?php
$pageTitle = 'Add New User - Election Management System';
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
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
    
    <div class="page-header">
        <h1>➕ Add New User</h1>
        <p>Create a new user account with appropriate permissions</p>
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
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required>
                        <div class="help-text">Must be unique. Only letters, numbers, and underscores allowed.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required>
                        <div class="help-text">Must be unique. Used for login and notifications.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                        <div class="help-text">Minimum 6 characters. Use a strong password.</div>
                        <div id="password-strength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="role">Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="">Select a role</option>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                        <?php echo ($_POST['role'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Role determines default permissions and access level.</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" 
                                   <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                            <label for="is_active">Active User</label>
                        </div>
                        <div class="help-text">Inactive users cannot log in to the system.</div>
                    </div>
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
                                                       <?php echo isset($_POST['permissions'][$module]) && in_array($permission, $_POST['permissions'][$module]) ? 'checked' : ''; ?>>
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
                    <a href="user_view.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength < 2) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthDiv.textContent = 'Medium strength password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const firstName = document.getElementById('first_name').value;
            const lastName = document.getElementById('last_name').value;
            const role = document.getElementById('role').value;
            
            if (!username || !email || !password || !firstName || !lastName || !role) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (password.length < 6) {
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
