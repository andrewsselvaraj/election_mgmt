<?php
/**
 * Database Export Script for Election Management System
 * This script exports the complete database structure and data
 */

require_once 'config.php';

try {
    // Set headers for file download
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="election_mgmt_complete_dump_' . date('Y-m-d_H-i-s') . '.sql"');
    
    // Start output buffering
    ob_start();
    
    echo "-- Election Management System Complete Database Dump\n";
    echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Database: " . DB_NAME . "\n\n";
    
    echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    echo "START TRANSACTION;\n";
    echo "SET time_zone = \"+00:00\";\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "-- --------------------------------------------------------\n\n";
        echo "-- Table structure for table `$table`\n";
        
        // Get table structure
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        echo $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        $data = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data)) {
            echo "-- Dumping data for table `$table`\n";
            
            // Get column names
            $columns = array_keys($data[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            foreach ($data as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $valueList = implode(', ', $values);
                echo "INSERT INTO `$table` ($columnList) VALUES ($valueList);\n";
            }
            echo "\n";
        }
    }
    
    echo "COMMIT;\n";
    echo "-- End of database dump\n";
    
    // Flush output
    ob_end_flush();
    
} catch (Exception $e) {
    echo "Error exporting database: " . $e->getMessage();
}
?>
