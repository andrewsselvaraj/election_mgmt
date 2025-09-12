<?php
require_once 'config.php';

try {
    echo "Creating Election Management Database...\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS election_mgmt_v1");
    $pdo->exec("USE election_mgmt_v1");
    echo "✓ Database created/selected\n";
    
    // Drop existing tables in correct order (due to foreign keys)
    $dropTables = [
        "DROP TABLE IF EXISTS user_roles",
        "DROP TABLE IF EXISTS users", 
        "DROP TABLE IF EXISTS roles",
        "DROP TABLE IF EXISTS Booth_master",
        "DROP TABLE IF EXISTS MLA_Master",
        "DROP TABLE IF EXISTS MP_Master"
    ];
    
    foreach ($dropTables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Ignore errors when dropping non-existent tables
        }
    }
    echo "✓ Existing tables dropped\n";
    
    // Create MP_Master table
    $mpTable = "CREATE TABLE MP_Master (
        mp_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        mp_constituency_code INT NOT NULL UNIQUE COMMENT 'MP Constituency Code',
        mp_constituency_name VARCHAR(255) NOT NULL COMMENT 'MP Constituency Name',
        state VARCHAR(100) DEFAULT 'Tamil Nadu' COMMENT 'State',
        created_by VARCHAR(100) NOT NULL COMMENT 'User who created the record',
        updated_by VARCHAR(100) DEFAULT NULL COMMENT 'User who last updated the record',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp'
    )";
    $pdo->exec($mpTable);
    echo "✓ MP_Master table created\n";
    
    // Create MLA_Master table
    $mlaTable = "CREATE TABLE MLA_Master (
        mla_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        mp_id VARCHAR(36) NOT NULL COMMENT 'Foreign key to MP_Master table',
        mla_constituency_code INT NOT NULL COMMENT 'MLA Constituency Code',
        mla_constituency_name VARCHAR(255) NOT NULL COMMENT 'MLA Constituency Name',
        created_by VARCHAR(100) NOT NULL COMMENT 'User who created the record',
        updated_by VARCHAR(100) DEFAULT NULL COMMENT 'User who last updated the record',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
        FOREIGN KEY (mp_id) REFERENCES MP_Master(mp_id) ON DELETE CASCADE,
        UNIQUE KEY unique_mla_code_per_mp (mp_id, mla_constituency_code)
    )";
    $pdo->exec($mlaTable);
    echo "✓ MLA_Master table created\n";
    
    // Create Booth_master table
    $boothTable = "CREATE TABLE Booth_master (
        Booth_ID VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        mla_id VARCHAR(36) NOT NULL COMMENT 'Foreign key to MLA_Master table',
        Sl_No INT NOT NULL COMMENT 'Serial Number',
        Polling_station_No VARCHAR(50) NOT NULL COMMENT 'Polling Station Number',
        Location_name_of_buiding VARCHAR(255) NOT NULL COMMENT 'Location name of building',
        Polling_Areas TEXT COMMENT 'Polling Areas description',
        Polling_Station_Type ENUM('Regular', 'Auxiliary', 'Special', 'Mobile') DEFAULT 'Regular' COMMENT 'Type of polling station',
        created_by VARCHAR(100) DEFAULT 'SYSTEM' COMMENT 'User who created the record',
        created_datetime DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
        updated_by VARCHAR(100) DEFAULT NULL COMMENT 'User who last updated the record',
        updated_datetime DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
        status ENUM('ACTIVE', 'INACTIVE', 'DELETED') DEFAULT 'ACTIVE' COMMENT 'Record status',
        FOREIGN KEY (mla_id) REFERENCES MLA_Master(mla_id) ON DELETE CASCADE,
        UNIQUE KEY unique_booth_per_mla (mla_id, Polling_station_No)
    )";
    $pdo->exec($boothTable);
    echo "✓ Booth_master table created\n";
    
    // Create roles table
    $rolesTable = "CREATE TABLE roles (
        role_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        role_name VARCHAR(50) NOT NULL UNIQUE,
        role_description TEXT,
        permissions JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($rolesTable);
    echo "✓ roles table created\n";
    
    // Create users table
    $usersTable = "CREATE TABLE users (
        user_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($usersTable);
    echo "✓ users table created\n";
    
    // Create user_roles table
    $userRolesTable = "CREATE TABLE user_roles (
        user_role_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id VARCHAR(36) NOT NULL,
        role_id VARCHAR(36) NOT NULL,
        assigned_by VARCHAR(36),
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL,
        UNIQUE KEY unique_user_role (user_id, role_id)
    )";
    $pdo->exec($userRolesTable);
    echo "✓ user_roles table created\n";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX idx_mp_constituency_code ON MP_Master(mp_constituency_code)",
        "CREATE INDEX idx_mp_state ON MP_Master(state)",
        "CREATE INDEX idx_mla_constituency_code ON MLA_Master(mla_constituency_code)",
        "CREATE INDEX idx_mla_mp_id ON MLA_Master(mp_id)",
        "CREATE INDEX idx_booth_mla_id ON Booth_master(mla_id)",
        "CREATE INDEX idx_booth_station_no ON Booth_master(Polling_station_No)",
        "CREATE INDEX idx_booth_status ON Booth_master(status)",
        "CREATE INDEX idx_users_username ON users(username)",
        "CREATE INDEX idx_users_email ON users(email)",
        "CREATE INDEX idx_user_roles_user_id ON user_roles(user_id)",
        "CREATE INDEX idx_user_roles_role_id ON user_roles(role_id)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $pdo->exec($indexSql);
        } catch (PDOException $e) {
            echo "⚠ Index may already exist: " . substr($indexSql, 0, 50) . "...\n";
        }
    }
    echo "✓ Indexes created\n";
    
    // Insert default roles
    $roles = [
        "INSERT INTO roles (role_name, role_description, permissions) VALUES ('superadmin', 'Super Administrator - Full system access', '{\"mp\":[\"create\",\"read\",\"update\",\"delete\"],\"mla\":[\"create\",\"read\",\"update\",\"delete\"],\"booth\":[\"create\",\"read\",\"update\",\"delete\"],\"users\":[\"create\",\"read\",\"update\",\"delete\"],\"roles\":[\"create\",\"read\",\"update\",\"delete\"]}')",
        "INSERT INTO roles (role_name, role_description, permissions) VALUES ('admin', 'Administrator - Manage MP, MLA and Booth data', '{\"mp\":[\"create\",\"read\",\"update\",\"delete\"],\"mla\":[\"create\",\"read\",\"update\",\"delete\"],\"booth\":[\"create\",\"read\",\"update\",\"delete\"]}')",
        "INSERT INTO roles (role_name, role_description, permissions) VALUES ('editor', 'Editor - Read and update data', '{\"mp\":[\"read\",\"update\"],\"mla\":[\"read\",\"update\"],\"booth\":[\"read\",\"update\"]}')",
        "INSERT INTO roles (role_name, role_description, permissions) VALUES ('viewer', 'Viewer - Read only access', '{\"mp\":[\"read\"],\"mla\":[\"read\"],\"booth\":[\"read\"]}')"
    ];
    
    foreach ($roles as $roleSql) {
        $pdo->exec($roleSql);
    }
    echo "✓ Default roles inserted\n";
    
    // Insert superadmin user
    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $userSql = "INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES ('superadmin', 'admin@election.com', '$passwordHash', 'Super', 'Admin')";
    $pdo->exec($userSql);
    echo "✓ Superadmin user created\n";
    
    // Assign superadmin role
    $assignRoleSql = "INSERT INTO user_roles (user_id, role_id) 
                      SELECT u.user_id, r.role_id 
                      FROM users u, roles r 
                      WHERE u.username = 'superadmin' AND r.role_name = 'superadmin'";
    $pdo->exec($assignRoleSql);
    echo "✓ Superadmin role assigned\n";
    
    echo "\n=== Database Setup Complete ===\n";
    echo "✓ All tables created successfully\n";
    echo "✓ Default data inserted\n";
    echo "✓ Ready to use the system!\n";
    echo "\nAccess the system at: http://localhost:8000\n";
    echo "Login: superadmin / admin123\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
