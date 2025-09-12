-- Election Management System Database v1
-- Database and MP Master Table for managing MP constituencies

-- Create database
CREATE DATABASE IF NOT EXISTS election_mgmt_v1;
USE election_mgmt_v1;

-- Drop existing tables if they exist (for clean setup)
DROP TABLE IF EXISTS Booth_master;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS MLA_Master;
DROP TABLE IF EXISTS MP_Master;

-- Create MP_Master table (constituencies)
CREATE TABLE MP_Master (
    -- Primary key (UUID - automatically generated)
    mp_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Data columns from Excel file
    mp_constituency_code INT NOT NULL UNIQUE COMMENT 'Original MP ID from Excel file',
    mp_constituency_name VARCHAR(255) NOT NULL COMMENT 'MP Constituency name',
    
    -- Additional fields for better management
    state VARCHAR(100) NOT NULL DEFAULT 'Tamil Nadu' COMMENT 'State where constituency is located',
    created_by VARCHAR(100) NOT NULL COMMENT 'User who created the record',
    updated_by VARCHAR(100) COMMENT 'User who last updated the record',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create MLA_Master table (MLA constituencies)
CREATE TABLE MLA_Master (
    -- Primary key (UUID - automatically generated)
    mla_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Foreign key to MP_Master
    mp_id VARCHAR(36) NOT NULL COMMENT 'Foreign key to MP_Master table',
    
    -- Data columns from Excel file
    mla_constituency_code INT NOT NULL COMMENT 'Original MLA ID from Excel file',
    mla_constituency_name VARCHAR(255) NOT NULL COMMENT 'MLA Constituency name',
    
    -- Additional fields for better management
    created_by VARCHAR(100) NOT NULL COMMENT 'User who created the record',
    updated_by VARCHAR(100) COMMENT 'User who last updated the record',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (mp_id) REFERENCES MP_Master(mp_id) ON DELETE CASCADE,
    
    -- Unique constraint for MLA constituency code within each MP constituency
    UNIQUE KEY unique_mla_code_per_mp (mp_id, mla_constituency_code)
);

-- Create Roles table
CREATE TABLE roles (
    role_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Users table
CREATE TABLE users (
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
);

-- Create User_Roles junction table
CREATE TABLE user_roles (
    user_role_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    role_id VARCHAR(36) NOT NULL,
    assigned_by VARCHAR(36),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id)
);

-- Create Booth_master table (Polling Booths)
CREATE TABLE Booth_master (
    -- Primary key (UUID - automatically generated)
    Booth_ID VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Foreign key to MLA_Master
    mla_id VARCHAR(36) NOT NULL COMMENT 'Foreign key to MLA_Master table',
    
    -- Data columns
    Sl_No INT NOT NULL COMMENT 'Serial Number',
    Polling_station_No VARCHAR(50) NOT NULL COMMENT 'Polling Station Number',
    Location_name_of_buiding VARCHAR(255) NOT NULL COMMENT 'Location name of building',
    Polling_Areas TEXT COMMENT 'Polling Areas description',
    Polling_Station_Type VARCHAR(255) DEFAULT 'Regular' COMMENT 'Type of polling station',
    
    -- Audit fields
    created_by VARCHAR(100) DEFAULT 'SYSTEM' COMMENT 'User who created the record',
    created_datetime DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_by VARCHAR(100) DEFAULT NULL COMMENT 'User who last updated the record',
    updated_datetime DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
    status ENUM('ACTIVE', 'INACTIVE', 'DELETED') DEFAULT 'ACTIVE' COMMENT 'Record status',
    
    -- Foreign key constraint
    FOREIGN KEY (mla_id) REFERENCES MLA_Master(mla_id) ON DELETE CASCADE,
    
    -- Unique constraint for booth within MLA constituency
    UNIQUE KEY unique_booth_per_mla (mla_id, Polling_station_No)
);

-- Create indexes for better performance
CREATE INDEX idx_mp_constituency_code ON MP_Master(mp_constituency_code);
CREATE INDEX idx_mp_state ON MP_Master(state);
CREATE INDEX idx_mla_constituency_code ON MLA_Master(mla_constituency_code);
CREATE INDEX idx_mla_mp_id ON MLA_Master(mp_id);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX idx_user_roles_role_id ON user_roles(role_id);
CREATE INDEX idx_booth_mla_id ON Booth_master(mla_id);
CREATE INDEX idx_booth_station_no ON Booth_master(Polling_station_No);
CREATE INDEX idx_booth_status ON Booth_master(status);

-- Insert default roles
INSERT INTO roles (role_name, role_description, permissions) VALUES
('superadmin', 'Super Administrator - Full system access', '{"mp":["create","read","update","delete"],"mla":["create","read","update","delete"],"booth":["create","read","update","delete"],"users":["create","read","update","delete"],"roles":["create","read","update","delete"]}'),
('admin', 'Administrator - Manage MP, MLA and Booth data', '{"mp":["create","read","update","delete"],"mla":["create","read","update","delete"],"booth":["create","read","update","delete"]}'),
('editor', 'Editor - Read and update data', '{"mp":["read","update"],"mla":["read","update"],"booth":["read","update"]}'),
('viewer', 'Viewer - Read only access', '{"mp":["read"],"mla":["read"],"booth":["read"]}');

-- Insert superadmin user (password: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES
('superadmin', 'admin@election.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'Super', 'Admin');

-- Assign superadmin role to superadmin user
INSERT INTO user_roles (user_id, role_id) 
SELECT u.user_id, r.role_id 
FROM users u, roles r 
WHERE u.username = 'superadmin' AND r.role_name = 'superadmin';