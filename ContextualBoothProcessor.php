<?php
require_once 'config.php';

class ContextualBoothProcessor {
    private $pdo;
    private $mlaId;
    
    public function __construct($pdo, $mlaId) {
        $this->pdo = $pdo;
        $this->mlaId = $mlaId;
    }
    
    public function processExcelFile($filePath, $createdBy) {
        try {
            // Validate file
            $validation = $this->validateFile($filePath);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                throw new Exception("Could not open file");
            }
            
            // Read header
            $header = fgetcsv($handle, 1000, ',');
            $header = array_map('trim', $header);
            
            // Process data rows
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $rowNumber = 1; // Start from 1 since header is row 0
            
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                try {
                    $data = $this->mapRowData($header, $row, $createdBy);
                    
                    // Check if polling station already exists
                    if ($this->stationExists($data['polling_station_no'])) {
                        $errors[] = "Row $rowNumber: Polling station '{$data['polling_station_no']}' already exists in this MLA constituency";
                        $errorCount++;
                        continue;
                    }
                    
                    if ($this->createBooth($data)) {
                        $successCount++;
                    } else {
                        $errors[] = "Row $rowNumber: Failed to create booth record";
                        $errorCount++;
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Row $rowNumber: " . $e->getMessage();
                    $errorCount++;
                }
            }
            
            fclose($handle);
            
            return [
                'success' => true,
                'successCount' => $successCount,
                'errorCount' => $errorCount,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function validateFile($filePath) {
        if (!file_exists($filePath)) {
            return ['valid' => false, 'message' => 'File does not exist'];
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['valid' => false, 'message' => 'Could not open file'];
        }
        
        $header = fgetcsv($handle, 1000, ',');
        fclose($handle);
        
        if (!$header || empty($header)) {
            return ['valid' => false, 'message' => 'File is empty or invalid'];
        }
        
        // Clean header
        $cleanHeader = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);
        
        // Define column mappings for flexible matching
        $columnMappings = [
            'sl_no' => ['sl_no', 'sl no', 'serial_no', 'serial no', 'serial_number', 'serial number'],
            'polling_station_no' => ['polling_station_no', 'polling station no', 'station_no', 'station no', 'booth_no', 'booth no'],
            'location_name_of_building' => ['location_name_of_building', 'location', 'building', 'location_name', 'location name', 'building_name', 'building name'],
            'polling_areas' => ['polling_areas', 'polling areas', 'areas', 'area', 'polling_area', 'polling area'],
            'polling_station_type' => ['polling_station_type', 'polling station type', 'station_type', 'station type', 'type', 'booth_type', 'booth type']
        ];
        
        $requiredColumns = ['sl_no', 'polling_station_no', 'location_name_of_building'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $required) {
            $found = false;
            foreach ($columnMappings[$required] as $mapping) {
                if (in_array($mapping, $cleanHeader)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingColumns[] = $required;
            }
        }
        
        if (!empty($missingColumns)) {
            $foundColumns = implode(', ', $cleanHeader);
            $suggestedColumns = implode(', ', $requiredColumns);
            return [
                'valid' => false, 
                'message' => "Missing required columns: " . implode(', ', $missingColumns) . 
                           ". Found columns: $foundColumns. Suggested columns: $suggestedColumns"
            ];
        }
        
        return ['valid' => true];
    }
    
    private function mapRowData($header, $row, $createdBy) {
        $cleanHeader = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);
        
        $data = [];
        
        // Set MLA ID from context
        $data['mla_id'] = $this->mlaId;
        
        // Map serial number
        $slNoIndex = $this->findColumnIndex($cleanHeader, ['sl_no', 'sl no', 'serial_no', 'serial no', 'serial_number', 'serial number']);
        if ($slNoIndex === false) {
            throw new Exception('Serial number column not found');
        }
        $data['sl_no'] = (int)$row[$slNoIndex];
        
        // Map polling station number
        $stationNoIndex = $this->findColumnIndex($cleanHeader, ['polling_station_no', 'polling station no', 'station_no', 'station no', 'booth_no', 'booth no']);
        if ($stationNoIndex === false) {
            throw new Exception('Polling station number column not found');
        }
        $data['polling_station_no'] = trim($row[$stationNoIndex]);
        
        // Map location name
        $locationIndex = $this->findColumnIndex($cleanHeader, ['location_name_of_building', 'location', 'building', 'location_name', 'location name', 'building_name', 'building name']);
        if ($locationIndex === false) {
            throw new Exception('Location name column not found');
        }
        $data['location_name_of_building'] = trim($row[$locationIndex]);
        
        // Map polling areas (optional)
        $areasIndex = $this->findColumnIndex($cleanHeader, ['polling_areas', 'polling areas', 'areas', 'area', 'polling_area', 'polling area']);
        $data['polling_areas'] = $areasIndex !== false ? trim($row[$areasIndex]) : '';
        
        // Map polling station type (optional, default to Regular)
        $typeIndex = $this->findColumnIndex($cleanHeader, ['polling_station_type', 'polling station type', 'station_type', 'station type', 'type', 'booth_type', 'booth type']);
        $data['polling_station_type'] = $typeIndex !== false ? trim($row[$typeIndex]) : 'Regular';
        
        // Validate polling station type (now accepts any text up to 255 characters)
        if (empty($data['polling_station_type'])) {
            $data['polling_station_type'] = 'Regular'; // Default to Regular if empty
        }
        
        $data['created_by'] = $createdBy;
        
        return $data;
    }
    
    private function findColumnIndex($header, $possibleNames) {
        foreach ($possibleNames as $name) {
            $index = array_search($name, $header);
            if ($index !== false) {
                return $index;
            }
        }
        return false;
    }
    
    private function stationExists($stationNo) {
        try {
            $sql = "SELECT COUNT(*) FROM booth_master 
                    WHERE mla_id = :mla_id AND polling_station_no = :station_no AND status = 'ACTIVE'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $this->mlaId);
            $stmt->bindParam(':station_no', $stationNo);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function createBooth($data) {
        try {
            $sql = "INSERT INTO booth_master (mla_id, sl_no, polling_station_no, location_name_of_building, 
                    polling_areas, polling_station_type, created_by) 
                    VALUES (:mla_id, :sl_no, :station_no, :location, :areas, :type, :created_by)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $data['mla_id']);
            $stmt->bindParam(':sl_no', $data['sl_no']);
            $stmt->bindParam(':station_no', $data['polling_station_no']);
            $stmt->bindParam(':location', $data['location_name_of_building']);
            $stmt->bindParam(':areas', $data['polling_areas']);
            $stmt->bindParam(':type', $data['polling_station_type']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error creating booth record: " . $e->getMessage());
        }
    }
    
    public function generateTemplate() {
        $template = "sl_no,polling_station_no,location_name_of_building,polling_areas,polling_station_type\n";
        $template .= "1,001,Government School Building,Area 1-5,Regular\n";
        $template .= "2,002,Community Hall,Area 6-10,Regular\n";
        $template .= "3,003,Primary School,Area 11-15,Auxiliary\n";
        $template .= "4,004,High School,Area 16-20,Special\n";
        $template .= "5,005,Panchayat Office,Area 21-25,Mobile\n";
        
        return $template;
    }
    
    public function downloadTemplate() {
        $template = $this->generateTemplate();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="booth_template_' . $this->mlaId . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo $template;
        exit;
    }
}
?>
