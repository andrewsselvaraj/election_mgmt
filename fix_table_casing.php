<?php
require_once 'config.php';

try {
    echo "Checking current table structure...\n";
    
    // Check current tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current tables: " . implode(', ', $tables) . "\n";
    
    // Check Booth_master table structure
    if (in_array('Booth_master', $tables)) {
        echo "\nCurrent Booth_master structure:\n";
        $stmt = $pdo->query("DESCRIBE Booth_master");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
    echo "\nFixing table and column casing...\n";
    
    // Drop and recreate Booth_master with correct casing
    $pdo->exec("DROP TABLE IF EXISTS Booth_master");
    echo "✓ Dropped old Booth_master table\n";
    
    // Create booth_master table with correct casing
    $boothTable = "CREATE TABLE booth_master (
        booth_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        mla_id VARCHAR(36) NOT NULL COMMENT 'Foreign key to MLA_Master table',
        sl_no INT NOT NULL COMMENT 'Serial Number',
        polling_station_no VARCHAR(50) NOT NULL COMMENT 'Polling Station Number',
        location_name_of_building VARCHAR(255) NOT NULL COMMENT 'Location name of building',
        polling_areas TEXT COMMENT 'Polling Areas description',
        polling_station_type ENUM('Regular', 'Auxiliary', 'Special', 'Mobile') DEFAULT 'Regular' COMMENT 'Type of polling station',
        created_by VARCHAR(100) DEFAULT 'SYSTEM' COMMENT 'User who created the record',
        created_datetime DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
        updated_by VARCHAR(100) DEFAULT NULL COMMENT 'User who last updated the record',
        updated_datetime DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
        status ENUM('ACTIVE', 'INACTIVE', 'DELETED') DEFAULT 'ACTIVE' COMMENT 'Record status',
        FOREIGN KEY (mla_id) REFERENCES MLA_Master(mla_id) ON DELETE CASCADE,
        UNIQUE KEY unique_booth_per_mla (mla_id, polling_station_no)
    )";
    $pdo->exec($boothTable);
    echo "✓ Created booth_master table with correct casing\n";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX idx_booth_mla_id ON booth_master(mla_id)",
        "CREATE INDEX idx_booth_station_no ON booth_master(polling_station_no)",
        "CREATE INDEX idx_booth_status ON booth_master(status)"
    ];
    
    foreach ($indexes as $indexSql) {
        $pdo->exec($indexSql);
    }
    echo "✓ Created indexes\n";
    
    echo "\n=== Table Casing Fixed ===\n";
    echo "✓ booth_master table created with correct casing\n";
    echo "✓ All column names are now lowercase\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
