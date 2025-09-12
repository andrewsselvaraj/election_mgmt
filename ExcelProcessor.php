<?php
require_once 'config.php';
require_once 'MPMaster.php';

class ExcelProcessor {
    private $pdo;
    private $mpMaster;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->mpMaster = new MPMaster($pdo);
    }
    
    // Process uploaded Excel file (CSV format)
    public function processExcelFile($filePath, $createdBy) {
        $results = [
            'success' => 0,
            'errors' => 0,
            'messages' => []
        ];
        
        if (!file_exists($filePath)) {
            $results['messages'][] = 'File not found';
            return $results;
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $results['messages'][] = 'Cannot open file';
            return $results;
        }
        
        $lineNumber = 0;
        $header = null;
        $columnMappings = null;
        
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $lineNumber++;
            
            // Skip empty lines
            if (empty(array_filter($data))) {
                continue;
            }
            
            // First line is header
            if ($lineNumber === 1) {
                $header = $data;
                $columnMappings = $this->getColumnMappings($header);
                continue;
            }
            
            // Process data row
            $rowData = $this->mapRowData($header, $data, $columnMappings);
            $result = $this->processRow($rowData, $createdBy, $lineNumber);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['errors']++;
                $results['messages'][] = "Line $lineNumber: " . $result['message'];
            }
        }
        
        fclose($handle);
        return $results;
    }
    
    // Process individual row
    private function processRow($rowData, $createdBy, $lineNumber) {
        try {
            // Validate required fields
            $requiredFields = ['mp_constituency_code', 'mp_constituency_name', 'state'];
            foreach ($requiredFields as $field) {
                if (empty($rowData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ];
                }
            }
            
            // Clean and validate data
            $data = [
                'mp_constituency_code' => (int)trim($rowData['mp_constituency_code']),
                'mp_constituency_name' => trim($rowData['mp_constituency_name']),
                'state' => trim($rowData['state']),
                'created_by' => $createdBy
            ];
            
            // Validate constituency code
            if ($data['mp_constituency_code'] <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid constituency code'
                ];
            }
            
            // Check if code already exists
            if ($this->mpMaster->codeExists($data['mp_constituency_code'])) {
                return [
                    'success' => false,
                    'message' => 'Constituency code already exists'
                ];
            }
            
            // Insert record
            if ($this->mpMaster->create($data)) {
                return [
                    'success' => true,
                    'message' => 'Record created successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create record'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Generate sample Excel template
    public function generateTemplate() {
        $filename = 'MP_Master_Template.csv';
        $filepath = 'uploads/' . $filename;
        
        // Create uploads directory if it doesn't exist
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            return false;
        }
        
        // Write header
        fputcsv($handle, [
            'constituency_code',
            'constituency_name', 
            'state'
        ]);
        
        // Write sample data
        fputcsv($handle, [
            '1',
            'Chennai Central',
            'Tamil Nadu'
        ]);
        
        fputcsv($handle, [
            '2',
            'Chennai North',
            'Tamil Nadu'
        ]);
        
        fclose($handle);
        return $filepath;
    }
    
    // Validate Excel file format
    public function validateFile($filePath) {
        if (!file_exists($filePath)) {
            return ['valid' => false, 'message' => 'File not found'];
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['valid' => false, 'message' => 'Cannot open file'];
        }
        
        // Read first line (header)
        $header = fgetcsv($handle, 1000, ',');
        fclose($handle);
        
        if (!$header) {
            return ['valid' => false, 'message' => 'Empty file'];
        }
        
        // Clean header names (remove spaces, convert to lowercase)
        $cleanHeader = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);
        
        // Define possible column name variations
        $columnMappings = [
            'mp_constituency_code' => ['mp_constituency_code', 'constituency_code', 'code', 'mp_code', 'constituency id', 'id'],
            'mp_constituency_name' => ['mp_constituency_name', 'constituency_name', 'name', 'constituency', 'constituency name'],
            'state' => ['state', 'state_name', 'state name']
        ];
        
        $foundColumns = [];
        $missingColumns = [];
        
        foreach ($columnMappings as $requiredColumn => $possibleNames) {
            $found = false;
            foreach ($possibleNames as $possibleName) {
                if (in_array($possibleName, $cleanHeader)) {
                    $foundColumns[$requiredColumn] = $possibleName;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingColumns[] = $requiredColumn;
            }
        }
        
        if (!empty($missingColumns)) {
            return [
                'valid' => false, 
                'message' => 'Missing required columns: ' . implode(', ', $missingColumns) . 
                           '. Found columns: ' . implode(', ', $cleanHeader) .
                           '. Please use: constituency_code, constituency_name, state'
            ];
        }
        
        return ['valid' => true, 'message' => 'File format is valid', 'mappings' => $foundColumns];
    }
    
    // Get column mappings from header
    private function getColumnMappings($header) {
        $cleanHeader = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);
        
        $columnMappings = [
            'mp_constituency_code' => ['mp_constituency_code', 'constituency_code', 'code', 'mp_code', 'constituency id', 'id'],
            'mp_constituency_name' => ['mp_constituency_name', 'constituency_name', 'name', 'constituency', 'constituency name'],
            'state' => ['state', 'state_name', 'state name']
        ];
        
        $foundColumns = [];
        foreach ($columnMappings as $requiredColumn => $possibleNames) {
            foreach ($possibleNames as $possibleName) {
                if (in_array($possibleName, $cleanHeader)) {
                    $foundColumns[$requiredColumn] = $possibleName;
                    break;
                }
            }
        }
        
        return $foundColumns;
    }
    
    // Map row data using column mappings
    private function mapRowData($header, $data, $columnMappings) {
        $mappedData = [];
        
        foreach ($columnMappings as $standardColumn => $actualColumn) {
            $headerIndex = array_search($actualColumn, array_map('strtolower', array_map('trim', $header)));
            if ($headerIndex !== false && isset($data[$headerIndex])) {
                $mappedData[$standardColumn] = $data[$headerIndex];
            }
        }
        
        return $mappedData;
    }
}
?>
