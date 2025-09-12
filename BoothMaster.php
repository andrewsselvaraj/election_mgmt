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
            $sql = "INSERT INTO Booth_master (mla_id, Sl_No, Polling_station_No, Location_name_of_buiding, 
                    Polling_Areas, Polling_Station_Type, created_by) 
                    VALUES (:mla_id, :sl_no, :station_no, :location, :areas, :type, :created_by)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $data['mla_id']);
            $stmt->bindParam(':sl_no', $data['Sl_No']);
            $stmt->bindParam(':station_no', $data['Polling_station_No']);
            $stmt->bindParam(':location', $data['Location_name_of_buiding']);
            $stmt->bindParam(':areas', $data['Polling_Areas']);
            $stmt->bindParam(':type', $data['Polling_Station_Type']);
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
                    FROM Booth_master b 
                    JOIN MLA_Master mla ON b.mla_id = mla.mla_id 
                    JOIN MP_Master mp ON mla.mp_id = mp.mp_id 
                    WHERE b.status = 'ACTIVE'
                    ORDER BY mp.mp_constituency_name, mla.mla_constituency_name, b.Sl_No";
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
                    FROM Booth_master b 
                    JOIN MLA_Master mla ON b.mla_id = mla.mla_id 
                    JOIN MP_Master mp ON mla.mp_id = mp.mp_id 
                    WHERE b.Booth_ID = :id";
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
            $sql = "UPDATE Booth_master SET 
                    mla_id = :mla_id,
                    Sl_No = :sl_no,
                    Polling_station_No = :station_no,
                    Location_name_of_buiding = :location,
                    Polling_Areas = :areas,
                    Polling_Station_Type = :type,
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE Booth_ID = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $data['mla_id']);
            $stmt->bindParam(':sl_no', $data['Sl_No']);
            $stmt->bindParam(':station_no', $data['Polling_station_No']);
            $stmt->bindParam(':location', $data['Location_name_of_buiding']);
            $stmt->bindParam(':areas', $data['Polling_Areas']);
            $stmt->bindParam(':type', $data['Polling_Station_Type']);
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
            $sql = "UPDATE Booth_master SET 
                    status = 'DELETED',
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE Booth_ID = :id";
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
                    FROM Booth_master b 
                    JOIN MLA_Master mla ON b.mla_id = mla.mla_id 
                    JOIN MP_Master mp ON mla.mp_id = mp.mp_id 
                    WHERE b.status = 'ACTIVE' AND (
                        b.Polling_station_No LIKE :search 
                        OR b.Location_name_of_buiding LIKE :search
                        OR b.Polling_Areas LIKE :search
                        OR mla.mla_constituency_name LIKE :search
                        OR mp.mp_constituency_name LIKE :search
                        OR mp.state LIKE :search
                    )
                    ORDER BY mp.mp_constituency_name, mla.mla_constituency_name, b.Sl_No";
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
            $sql = "SELECT * FROM Booth_master 
                    WHERE mla_id = :mla_id AND status = 'ACTIVE' 
                    ORDER BY Sl_No";
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
                    FROM MLA_Master mla 
                    JOIN MP_Master mp ON mla.mp_id = mp.mp_id 
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
            $sql = "SELECT COUNT(*) FROM Booth_master 
                    WHERE mla_id = :mla_id AND Polling_station_No = :station_no AND status = 'ACTIVE'";
            if ($excludeId) {
                $sql .= " AND Booth_ID != :exclude_id";
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
                        COUNT(CASE WHEN Polling_Station_Type = 'Regular' THEN 1 END) as regular_booths,
                        COUNT(CASE WHEN Polling_Station_Type = 'Auxiliary' THEN 1 END) as auxiliary_booths,
                        COUNT(CASE WHEN Polling_Station_Type = 'Special' THEN 1 END) as special_booths,
                        COUNT(CASE WHEN Polling_Station_Type = 'Mobile' THEN 1 END) as mobile_booths
                    FROM Booth_master 
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
        return [
            'Regular' => 'Regular',
            'Auxiliary' => 'Auxiliary', 
            'Special' => 'Special',
            'Mobile' => 'Mobile'
        ];
    }
}
?>
