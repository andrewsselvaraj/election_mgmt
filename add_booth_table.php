<?php
require_once 'config.php';

try {
    echo "Adding Booth_master table...\n";
    
    // Create Booth_master table
    $sql = "CREATE TABLE IF NOT EXISTS Booth_master (
        -- Primary key (UUID - automatically generated)
        Booth_ID VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        
        -- Foreign key to MLA_Master
        mla_id VARCHAR(36) NOT NULL COMMENT 'Foreign key to MLA_Master table',
        
        -- Data columns
        Sl_No INT NOT NULL COMMENT 'Serial Number',
        Polling_station_No VARCHAR(50) NOT NULL COMMENT 'Polling Station Number',
        Location_name_of_buiding VARCHAR(255) NOT NULL COMMENT 'Location name of building',
        Polling_Areas TEXT COMMENT 'Polling Areas description',
        Polling_Station_Type ENUM('Regular', 'Auxiliary', 'Special', 'Mobile') DEFAULT 'Regular' COMMENT 'Type of polling station',
        
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
    )";
    
    $pdo->exec($sql);
    echo "✓ Booth_master table created successfully\n";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX idx_booth_mla_id ON Booth_master(mla_id)",
        "CREATE INDEX idx_booth_station_no ON Booth_master(Polling_station_No)",
        "CREATE INDEX idx_booth_status ON Booth_master(status)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $pdo->exec($indexSql);
            echo "✓ Index created: " . substr($indexSql, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "⚠ Index may already exist: " . $e->getMessage() . "\n";
        }
    }
    
    // Update roles to include booth permissions
    $updateRoles = [
        "UPDATE roles SET permissions = '{\"mp\":[\"create\",\"read\",\"update\",\"delete\"],\"mla\":[\"create\",\"read\",\"update\",\"delete\"],\"booth\":[\"create\",\"read\",\"update\",\"delete\"],\"users\":[\"create\",\"read\",\"update\",\"delete\"],\"roles\":[\"create\",\"read\",\"update\",\"delete\"]}' WHERE role_name = 'superadmin'",
        "UPDATE roles SET permissions = '{\"mp\":[\"create\",\"read\",\"update\",\"delete\"],\"mla\":[\"create\",\"read\",\"update\",\"delete\"],\"booth\":[\"create\",\"read\",\"update\",\"delete\"]}' WHERE role_name = 'admin'",
        "UPDATE roles SET permissions = '{\"mp\":[\"read\",\"update\"],\"mla\":[\"read\",\"update\"],\"booth\":[\"read\",\"update\"]}' WHERE role_name = 'editor'",
        "UPDATE roles SET permissions = '{\"mp\":[\"read\"],\"mla\":[\"read\"],\"booth\":[\"read\"]}' WHERE role_name = 'viewer'"
    ];
    
    foreach ($updateRoles as $updateSql) {
        try {
            $pdo->exec($updateSql);
            echo "✓ Role permissions updated\n";
        } catch (PDOException $e) {
            echo "⚠ Role update failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Booth Master Table Setup Complete ===\n";
    echo "You can now access the booth management system at: http://localhost:8000/booth_index.php\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
