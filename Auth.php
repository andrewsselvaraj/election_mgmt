<?php
require_once 'config.php';

class Auth {
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
            $sql = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles, GROUP_CONCAT(r.permissions) as permissions
                    FROM users u
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.role_id
                    WHERE u.username = :username AND u.is_active = 1
                    GROUP BY u.user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->updateLastLogin($user['user_id']);
                
                // Set session data
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['roles'] = explode(',', $user['roles']);
                $_SESSION['permissions'] = $this->parsePermissions($user['permissions']);
                $_SESSION['is_logged_in'] = true;
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
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
            'roles' => $_SESSION['roles'],
            'permissions' => $_SESSION['permissions']
        ];
    }
    
    // Check if user has permission
    public function hasPermission($module, $action) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $permissions = $_SESSION['permissions'];
        
        // Superadmin has all permissions
        if (in_array('superadmin', $_SESSION['roles'])) {
            return true;
        }
        
        // Check specific permission
        if (isset($permissions[$module]) && in_array($action, $permissions[$module])) {
            return true;
        }
        
        return false;
    }
    
    // Check if user has any of the specified roles
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return !empty(array_intersect($_SESSION['roles'], $roles));
    }
    
    // Require login (redirect if not logged in)
    public function requireLogin($redirectTo = 'login.php') {
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
            $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    // Parse permissions from JSON
    private function parsePermissions($permissionsJson) {
        $permissions = [];
        $permissionArrays = explode(',', $permissionsJson);
        
        foreach ($permissionArrays as $permJson) {
            $perm = json_decode(trim($permJson), true);
            if ($perm && is_array($perm)) {
                $permissions = array_merge_recursive($permissions, $perm);
            }
        }
        
        return $permissions;
    }
    
    // Create new user
    public function createUser($userData) {
        try {
            $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name) 
                    VALUES (:username, :email, :password_hash, :first_name, :last_name)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password_hash', password_hash($userData['password'], PASSWORD_DEFAULT));
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }
    
    // Assign role to user
    public function assignRole($userId, $roleId, $assignedBy = null) {
        try {
            $sql = "INSERT INTO user_roles (user_id, role_id, assigned_by) 
                    VALUES (:user_id, :role_id, :assigned_by)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':role_id', $roleId);
            $stmt->bindParam(':assigned_by', $assignedBy);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Assign role error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get all users
    public function getAllUsers() {
        try {
            $sql = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles
                    FROM users u
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.role_id
                    GROUP BY u.user_id
                    ORDER BY u.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get all roles
    public function getAllRoles() {
        try {
            $sql = "SELECT * FROM roles ORDER BY role_name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all roles error: " . $e->getMessage());
            return [];
        }
    }
}
?>
