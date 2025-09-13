<?php
require_once 'config.php';

class UserMaster {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new User record
    public function create($data) {
        try {
            $sql = "INSERT INTO user_master (username, email, password_hash, first_name, last_name, 
                    phone, address, role, permissions, is_active, created_by) 
                    VALUES (:username, :email, :password_hash, :first_name, :last_name, 
                    :phone, :address, :role, :permissions, :is_active, :created_by)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password_hash', $data['password_hash']);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':permissions', $data['permissions']);
            $stmt->bindParam(':is_active', $data['is_active']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User record created successfully',
                    'user_id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create user record'
                ];
            }
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error creating user record: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all User records
    public function readAll() {
        try {
            $sql = "SELECT * FROM user_master WHERE status = 'ACTIVE' ORDER BY created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Read user by ID
    public function readById($userId) {
        try {
            $sql = "SELECT * FROM user_master WHERE user_id = :user_id AND status = 'ACTIVE'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Update user record
    public function update($userId, $data) {
        try {
            $sql = "UPDATE user_master SET 
                    username = :username, 
                    email = :email, 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    phone = :phone, 
                    address = :address, 
                    role = :role, 
                    permissions = :permissions, 
                    is_active = :is_active, 
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':permissions', $data['permissions']);
            $stmt->bindParam(':is_active', $data['is_active']);
            $stmt->bindParam(':updated_by', $data['updated_by']);
            $stmt->bindParam(':user_id', $userId);
            
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User record updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update user record'
                ];
            }
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating user record: ' . $e->getMessage()
            ];
        }
    }
    
    // Soft delete user record
    public function delete($userId, $deletedBy) {
        try {
            $sql = "UPDATE user_master SET 
                    status = 'DELETED', 
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':updated_by', $deletedBy);
            $stmt->bindParam(':user_id', $userId);
            
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User record deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete user record'
                ];
            }
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deleting user record: ' . $e->getMessage()
            ];
        }
    }
    
    // Get user statistics
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total users
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM user_master WHERE status = 'ACTIVE'");
            $stats['total'] = $stmt->fetchColumn();
            
            // Users by role
            $stmt = $this->pdo->query("SELECT role, COUNT(*) as count FROM user_master WHERE status = 'ACTIVE' GROUP BY role");
            $stats['by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Active users
            $stmt = $this->pdo->query("SELECT COUNT(*) as active FROM user_master WHERE status = 'ACTIVE' AND is_active = 1");
            $stats['active'] = $stmt->fetchColumn();
            
            // Inactive users
            $stmt = $this->pdo->query("SELECT COUNT(*) as inactive FROM user_master WHERE status = 'ACTIVE' AND is_active = 0");
            $stats['inactive'] = $stmt->fetchColumn();
            
            return $stats;
        } catch(PDOException $e) {
            return [
                'total' => 0,
                'by_role' => [],
                'active' => 0,
                'inactive' => 0
            ];
        }
    }
    
    // Search users
    public function search($searchTerm) {
        try {
            $sql = "SELECT * FROM user_master WHERE status = 'ACTIVE' AND 
                    (username LIKE :search OR email LIKE :search OR first_name LIKE :search OR last_name LIKE :search)
                    ORDER BY created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $searchPattern = '%' . $searchTerm . '%';
            $stmt->bindParam(':search', $searchPattern);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Get users by role
    public function getByRole($role) {
        try {
            $sql = "SELECT * FROM user_master WHERE status = 'ACTIVE' AND role = :role ORDER BY created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Update last login
    public function updateLastLogin($userId) {
        try {
            $sql = "UPDATE user_master SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Check if username exists
    public function usernameExists($username, $excludeUserId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM user_master WHERE username = :username AND status = 'ACTIVE'";
            if ($excludeUserId) {
                $sql .= " AND user_id != :exclude_user_id";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            if ($excludeUserId) {
                $stmt->bindParam(':exclude_user_id', $excludeUserId);
            }
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Check if email exists
    public function emailExists($email, $excludeUserId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM user_master WHERE email = :email AND status = 'ACTIVE'";
            if ($excludeUserId) {
                $sql .= " AND user_id != :exclude_user_id";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            if ($excludeUserId) {
                $stmt->bindParam(':exclude_user_id', $excludeUserId);
            }
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
