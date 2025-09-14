<?php
require_once 'config.php';

class UserAuth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->startSession();
    }
    
    // Start session if not already started
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Login user
    public function login($username, $password) {
        try {
            $sql = "SELECT * FROM user_master 
                    WHERE (username = :username OR email = :username) 
                    AND is_active = 1 AND status = 'ACTIVE'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if account is locked
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    return [
                        'success' => false,
                        'message' => 'Account is locked. Please try again later.'
                    ];
                }
                
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Reset login attempts on successful login
                    $this->resetLoginAttempts($user['user_id']);
                    
                    // Update last login
                    $this->updateLastLogin($user['user_id']);
                    
                    // Set session data
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['permissions'] = $this->parsePermissions($user['permissions']);
                    $_SESSION['is_logged_in'] = true;
                    
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => $user
                    ];
                } else {
                    // Increment login attempts
                    $this->incrementLoginAttempts($user['user_id']);
                    return [
                        'success' => false,
                        'message' => 'Invalid username or password'
                    ];
                }
            }
            
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }
    
    // Logout user
    public function logout() {
        session_destroy();
        return true;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }
    
    // Get current user data
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'role' => $_SESSION['role'],
            'permissions' => $_SESSION['permissions']
        ];
    }
    
    // Check if user has permission
    public function hasPermission($module, $action) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $permissions = $_SESSION['permissions'];
        
        // Super admin has all permissions
        if ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin') {
            return true;
        }
        
        // Check specific permission
        if (isset($permissions[$module]) && is_array($permissions[$module])) {
            if (isset($permissions[$module][$action])) {
                return $permissions[$module][$action] === true;
            }
            if (in_array($action, $permissions[$module])) {
                return true;
            }
        }
        
        return false;
    }
    
    // Check if user has role
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['role'], $roles);
    }
    
    // Require login (redirect if not logged in)
    public function requireLogin($redirectTo = 'user_login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    // Require permission (redirect if no permission)
    public function requirePermission($module, $action, $redirectTo = 'unauthorized.php') {
        $this->requireLogin();
        
        if (!$this->hasPermission($module, $action)) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    // Update last login timestamp
    private function updateLastLogin($userId) {
        try {
            $sql = "UPDATE user_master SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    // Increment login attempts
    private function incrementLoginAttempts($userId) {
        try {
            $sql = "UPDATE user_master 
                    SET login_attempts = login_attempts + 1,
                        locked_until = CASE 
                            WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                            ELSE locked_until
                        END
                    WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Increment login attempts error: " . $e->getMessage());
        }
    }
    
    // Reset login attempts
    private function resetLoginAttempts($userId) {
        try {
            $sql = "UPDATE user_master 
                    SET login_attempts = 0, locked_until = NULL 
                    WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Reset login attempts error: " . $e->getMessage());
        }
    }
    
    // Parse permissions from JSON
    private function parsePermissions($permissionsJson) {
        if (empty($permissionsJson)) {
            return [];
        }
        
        $permissions = json_decode($permissionsJson, true);
        return is_array($permissions) ? $permissions : [];
    }
    
    // Create new user
    public function createUser($userData) {
        try {
            $sql = "INSERT INTO user_master (username, email, password_hash, first_name, last_name, role, permissions, created_by) 
                    VALUES (:username, :email, :password_hash, :first_name, :last_name, :role, :permissions, :created_by)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password_hash', password_hash($userData['password'], PASSWORD_DEFAULT));
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':role', $userData['role'] ?? 'user');
            $stmt->bindParam(':permissions', json_encode($userData['permissions'] ?? []));
            $stmt->bindParam(':created_by', $userData['created_by'] ?? 'SYSTEM');
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get all users
    public function getAllUsers() {
        try {
            $sql = "SELECT user_id, username, email, first_name, last_name, role, is_active, 
                           last_login, created_datetime, status
                    FROM user_master 
                    WHERE status != 'DELETED'
                    ORDER BY created_datetime DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get user by ID
    public function getUserById($userId) {
        try {
            $sql = "SELECT * FROM user_master WHERE user_id = :user_id AND status != 'DELETED'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    // Update user
    public function updateUser($userId, $userData) {
        try {
            $sql = "UPDATE user_master SET 
                    username = :username,
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    role = :role,
                    permissions = :permissions,
                    is_active = :is_active,
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':permissions', json_encode($userData['permissions'] ?? []));
            $stmt->bindParam(':is_active', $userData['is_active']);
            $stmt->bindParam(':updated_by', $userData['updated_by']);
            $stmt->bindParam(':user_id', $userId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete user (soft delete)
    public function deleteUser($userId, $deletedBy) {
        try {
            $sql = "UPDATE user_master SET 
                    status = 'DELETED',
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':updated_by', $deletedBy);
            $stmt->bindParam(':user_id', $userId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
}
?>
