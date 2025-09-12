<?php
require_once 'config.php';
require_once 'BoothMaster.php';

class BoothExcelProcessor {
    private $pdo;
    private $boothMaster;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->boothMaster = new BoothMaster($pdo);
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
                    if ($this->boothMaster->stationExists($data['mla_id'], $data['Polling_station_No'])) {
                        $errors[] = "Row $rowNumber: Polling station '{$data['Polling_station_No']}' already exists in this MLA constituency";
                        $errorCount++;
                        continue;
                    }
                    
                    if ($this->boothMaster->create($data)) {
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
    
    private function validateFile($filePath) {
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
            'mla_constituency_code' => ['mla_constituency_code', 'mla_code', 'mla id', 'mla_id', 'assembly_code'],
            'sl_no' => ['sl_no', 'sl no', 'serial_no', 'serial no', 'serial_number', 'serial number'],
            'polling_station_no' => ['polling_station_no', 'polling station no', 'station_no', 'station no', 'booth_no', 'booth no'],
            'location_name_of_buiding' => ['location_name_of_buiding', 'location', 'building', 'location_name', 'location name', 'building_name', 'building name'],
            'polling_areas' => ['polling_areas', 'polling areas', 'areas', 'area', 'polling_area', 'polling area'],
            'polling_station_type' => ['polling_station_type', 'polling station type', 'station_type', 'station type', 'type', 'booth_type', 'booth type']
        ];
        
        $requiredColumns = ['mla_constituency_code', 'sl_no', 'polling_station_no', 'location_name_of_buiding'];
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
        
        // Map MLA constituency code to MLA ID
        $mlaCodeIndex = $this->findColumnIndex($cleanHeader, ['mla_constituency_code', 'mla_code', 'mla id', 'mla_id', 'assembly_code']);
        if ($mlaCodeIndex === false) {
            throw new Exception('MLA constituency code column not found');
        }
        
        $mlaCode = trim($row[$mlaCodeIndex]);
        if (empty($mlaCode)) {
            throw new Exception('MLA constituency code is required');
        }
        
        // Get MLA ID from code
        $mlaId = $this->getMLAIdByCode($mlaCode);
        if (!$mlaId) {
            throw new Exception("MLA constituency with code '$mlaCode' not found");
        }
        $data['mla_id'] = $mlaId;
        
        // Map serial number
        $slNoIndex = $this->findColumnIndex($cleanHeader, ['sl_no', 'sl no', 'serial_no', 'serial no', 'serial_number', 'serial number']);
        if ($slNoIndex === false) {
            throw new Exception('Serial number column not found');
        }
        $data['Sl_No'] = (int)$row[$slNoIndex];
        
        // Map polling station number
        $stationNoIndex = $this->findColumnIndex($cleanHeader, ['polling_station_no', 'polling station no', 'station_no', 'station no', 'booth_no', 'booth no']);
        if ($stationNoIndex === false) {
            throw new Exception('Polling station number column not found');
        }
        $data['Polling_station_No'] = trim($row[$stationNoIndex]);
        
        // Map location name
        $locationIndex = $this->findColumnIndex($cleanHeader, ['location_name_of_buiding', 'location', 'building', 'location_name', 'location name', 'building_name', 'building name']);
        if ($locationIndex === false) {
            throw new Exception('Location name column not found');
        }
        $data['Location_name_of_buiding'] = trim($row[$locationIndex]);
        
        // Map polling areas (optional)
        $areasIndex = $this->findColumnIndex($cleanHeader, ['polling_areas', 'polling areas', 'areas', 'area', 'polling_area', 'polling area']);
        $data['Polling_Areas'] = $areasIndex !== false ? trim($row[$areasIndex]) : '';
        
        // Map polling station type (optional, default to Regular)
        $typeIndex = $this->findColumnIndex($cleanHeader, ['polling_station_type', 'polling station type', 'station_type', 'station type', 'type', 'booth_type', 'booth type']);
        $data['Polling_Station_Type'] = $typeIndex !== false ? trim($row[$typeIndex]) : 'Regular';
        
        // Validate polling station type
        $validTypes = ['Regular', 'Auxiliary', 'Special', 'Mobile'];
        if (!in_array($data['Polling_Station_Type'], $validTypes)) {
            $data['Polling_Station_Type'] = 'Regular'; // Default to Regular if invalid
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
    
    private function getMLAIdByCode($mlaCode) {
        try {
            $sql = "SELECT mla_id FROM MLA_Master WHERE mla_constituency_code = :code";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':code', $mlaCode);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? $result['mla_id'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function generateTemplate() {
        $template = "mla_constituency_code,sl_no,polling_station_no,location_name_of_buiding,polling_areas,polling_station_type\n";
        $template .= "1,1,001,Government School Building,Area 1-5,Regular\n";
        $template .= "1,2,002,Community Hall,Area 6-10,Regular\n";
        $template .= "2,1,001,Primary School,Area 1-3,Auxiliary\n";
        $template .= "2,2,002,High School,Area 4-6,Regular\n";
        $template .= "3,1,001,Panchayat Office,Area 1-4,Special\n";
        
        return $template;
    }
    
    public function downloadTemplate() {
        $template = $this->generateTemplate();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="booth_template.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo $template;
        exit;
    }
}
?>
