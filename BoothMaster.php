<?php
require_once 'config.php';

class BoothMaster {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new Booth record
    public function create($data) {
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
    
    // Read all Booth records with MLA and MP details
    public function readAll() {
        try {
            $sql = "SELECT b.*, mla.mla_constituency_name, mp.mp_constituency_name, mp.state 
                    FROM booth_master b 
                    JOIN mla_master mla ON b.mla_id = mla.mla_id 
                    JOIN mp_master mp ON mla.mp_id = mp.mp_id 
                    WHERE b.status = 'ACTIVE'
                    ORDER BY mp.mp_constituency_name, mla.mla_constituency_name, b.sl_no";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error reading booth records: " . $e->getMessage());
        }
    }
    
    // Read a single Booth record by ID
    public function readById($id) {
        try {
            $sql = "SELECT b.*, mla.mla_constituency_name, mp.mp_constituency_name, mp.state 
                    FROM booth_master b 
                    JOIN mla_master mla ON b.mla_id = mla.mla_id 
                    JOIN mp_master mp ON mla.mp_id = mp.mp_id 
                    WHERE b.booth_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error reading booth record: " . $e->getMessage());
        }
    }
    
    // Update a Booth record
    public function update($id, $data) {
        try {
            $sql = "UPDATE booth_master SET 
                    mla_id = :mla_id,
                    sl_no = :sl_no,
                    polling_station_no = :station_no,
                    location_name_of_building = :location,
                    polling_areas = :areas,
                    polling_station_type = :type,
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE booth_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $data['mla_id']);
            $stmt->bindParam(':sl_no', $data['sl_no']);
            $stmt->bindParam(':station_no', $data['polling_station_no']);
            $stmt->bindParam(':location', $data['location_name_of_building']);
            $stmt->bindParam(':areas', $data['polling_areas']);
            $stmt->bindParam(':type', $data['polling_station_type']);
            $stmt->bindParam(':updated_by', $data['updated_by']);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error updating booth record: " . $e->getMessage());
        }
    }
    
    // Soft delete a Booth record (set status to DELETED)
    public function delete($id) {
        try {
            $sql = "UPDATE booth_master SET 
                    status = 'DELETED',
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE booth_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error deleting booth record: " . $e->getMessage());
        }
    }
    
    // Search Booth records
    public function search($searchTerm) {
        try {
            $sql = "SELECT b.*, mla.mla_constituency_name, mp.mp_constituency_name, mp.state 
                    FROM booth_master b 
                    JOIN mla_master mla ON b.mla_id = mla.mla_id 
                    JOIN mp_master mp ON mla.mp_id = mp.mp_id 
                    WHERE b.status = 'ACTIVE' AND (
                        b.polling_station_no LIKE :search 
                        OR b.location_name_of_building LIKE :search
                        OR b.polling_areas LIKE :search
                        OR mla.mla_constituency_name LIKE :search
                        OR mp.mp_constituency_name LIKE :search
                        OR mp.state LIKE :search
                    )
                    ORDER BY mp.mp_constituency_name, mla.mla_constituency_name, b.sl_no";
            $stmt = $this->pdo->prepare($sql);
            $searchPattern = "%$searchTerm%";
            $stmt->bindParam(':search', $searchPattern);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error searching booth records: " . $e->getMessage());
        }
    }
    
    // Get Booth records by MLA ID
    public function getByMLAId($mlaId) {
        try {
            $sql = "SELECT * FROM booth_master 
                    WHERE mla_id = :mla_id AND status = 'ACTIVE' 
                    ORDER BY sl_no";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $mlaId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error getting booth records by MLA ID: " . $e->getMessage());
        }
    }
    
    // Get all MLA records for dropdown
    public function getMLARecords() {
        try {
            $sql = "SELECT mla.mla_id, mla.mla_constituency_name, mp.mp_constituency_name, mp.state 
                    FROM mla_master mla 
                    JOIN mp_master mp ON mla.mp_id = mp.mp_id 
                    ORDER BY mp.mp_constituency_name, mla.mla_constituency_name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error getting MLA records: " . $e->getMessage());
        }
    }
    
    // Check if polling station number exists within an MLA constituency
    public function stationExists($mlaId, $stationNo, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM booth_master 
                    WHERE mla_id = :mla_id AND polling_station_no = :station_no AND status = 'ACTIVE'";
            if ($excludeId) {
                $sql .= " AND booth_id != :exclude_id";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $mlaId);
            $stmt->bindParam(':station_no', $stationNo);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId);
            }
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            throw new Exception("Error checking polling station: " . $e->getMessage());
        }
    }
    
    // Get statistics
    public function getStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_booths,
                        COUNT(DISTINCT mla_id) as total_mla_constituencies,
                        COUNT(CASE WHEN polling_station_type = 'Regular' THEN 1 END) as regular_booths,
                        COUNT(CASE WHEN polling_station_type = 'Auxiliary' THEN 1 END) as auxiliary_booths,
                        COUNT(CASE WHEN polling_station_type = 'Special' THEN 1 END) as special_booths,
                        COUNT(CASE WHEN polling_station_type = 'Mobile' THEN 1 END) as mobile_booths
                    FROM booth_master 
                    WHERE status = 'ACTIVE'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error getting booth statistics: " . $e->getMessage());
        }
    }
    
    // Get polling station types
    public function getPollingStationTypes() {
        // Common polling station types (suggestions for dropdown)
        return [
            'Regular' => 'Regular',
            'Auxiliary' => 'Auxiliary', 
            'Special' => 'Special',
            'Mobile' => 'Mobile',
            'Temporary' => 'Temporary',
            'Emergency' => 'Emergency',
            'Remote' => 'Remote',
            'Urban' => 'Urban',
            'Rural' => 'Rural'
        ];
    }
    
    public function getCommonStationTypes() {
        // Get commonly used station types from database
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT polling_station_type FROM booth_master WHERE polling_station_type IS NOT NULL AND polling_station_type != '' ORDER BY polling_station_type");
            $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Add common types if not in database
            $commonTypes = ['Regular', 'Auxiliary', 'Special', 'Mobile'];
            $allTypes = array_unique(array_merge($commonTypes, $types));
            
            return array_combine($allTypes, $allTypes);
        } catch (PDOException $e) {
            // Fallback to default types
            return $this->getPollingStationTypes();
        }
    }
}
?>
