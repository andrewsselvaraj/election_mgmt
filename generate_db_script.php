<?php
/**
 * Generate Database Script for Election Management System
 * This script creates a complete SQL dump file with structure and sample data
 */

require_once 'config.php';

try {
    $outputFile = 'election_mgmt_database_complete.sql';
    $file = fopen($outputFile, 'w');
    
    if (!$file) {
        throw new Exception("Could not create output file: $outputFile");
    }
    
    // Write header
    fwrite($file, "-- Election Management System Complete Database Dump\n");
    fwrite($file, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
    fwrite($file, "-- Database: " . DB_NAME . "\n\n");
    
    fwrite($file, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($file, "START TRANSACTION;\n");
    fwrite($file, "SET time_zone = \"+00:00\";\n\n");
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        fwrite($file, "-- --------------------------------------------------------\n\n");
        fwrite($file, "-- Table structure for table `$table`\n");
        
        // Get table structure
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        fwrite($file, $createTable['Create Table'] . ";\n\n");
        
        // Get table data
        $data = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data)) {
            fwrite($file, "-- Dumping data for table `$table`\n");
            
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
                fwrite($file, "INSERT INTO `$table` ($columnList) VALUES ($valueList);\n");
            }
            fwrite($file, "\n");
        }
    }
    
    fwrite($file, "COMMIT;\n");
    fwrite($file, "-- End of database dump\n");
    
    fclose($file);
    
    echo "✅ Database script generated successfully!\n";
    echo "📁 File: $outputFile\n";
    echo "📊 Tables exported: " . count($tables) . "\n";
    
    // Show file size
    $fileSize = filesize($outputFile);
    echo "📏 File size: " . number_format($fileSize / 1024, 2) . " KB\n";
    
    // List tables
    echo "\n📋 Tables included:\n";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "   - $table ($count records)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error generating database script: " . $e->getMessage() . "\n";
}
?>
