<?php
require_once 'config.php';

class MLAMaster {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new MLA record
    public function create($data) {
        try {
            $sql = "INSERT INTO MLA_Master (mp_id, mla_constituency_code, mla_constituency_name, created_by) 
                    VALUES (:mp_id, :code, :name, :created_by)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mp_id', $data['mp_id']);
            $stmt->bindParam(':code', $data['mla_constituency_code']);
            $stmt->bindParam(':name', $data['mla_constituency_name']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error creating MLA record: " . $e->getMessage());
        }
    }
    
    // Read all MLA records with MP details
    public function readAll() {
        try {
            $sql = "SELECT mla.*, mp.mp_constituency_name, mp.state 
                    FROM MLA_Master mla 
                    JOIN MP_Master mp ON mla.mp_id = mp.mp_id 
                    ORDER BY mp.mp_constituency_code, mla.mla_constituency_code";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error reading MLA records: " . $e->getMessage());
        }
    }
    
    // Read a single MLA record by ID
    public function readById($id) {
        try {
            $sql = "SELECT mla.*, mp.mp_constituency_name, mp.state 
                    FROM MLA_Master mla 
                    JOIN MP_Master mp ON mla.mp_id = mp.mp_id 
                    WHERE mla.mla_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error reading MLA record: " . $e->getMessage());
        }
    }
    
    // Update an MLA record
    public function update($id, $data) {
        try {
            $sql = "UPDATE MLA_Master SET 
                    mp_id = :mp_id,
                    mla_constituency_code = :code,
                    mla_constituency_name = :name,
                    updated_by = :updated_by
                    WHERE mla_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mp_id', $data['mp_id']);
            $stmt->bindParam(':code', $data['mla_constituency_code']);
            $stmt->bindParam(':name', $data['mla_constituency_name']);
            $stmt->bindParam(':updated_by', $data['updated_by']);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error updating MLA record: " . $e->getMessage());
        }
    }
    
    // Delete an MLA record
    public function delete($id) {
        try {
            $sql = "DELETE FROM MLA_Master WHERE mla_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error deleting MLA record: " . $e->getMessage());
        }
    }
    
    // Search MLA records
    public function search($searchTerm) {
        try {
            $sql = "SELECT mla.*, mp.mp_constituency_name, mp.state 
                    FROM MLA_Master mla 
                    JOIN MP_Master mp ON mla.mp_id = mp.mp_id 
                    WHERE mla.mla_constituency_name LIKE :search 
                    OR mp.mp_constituency_name LIKE :search
                    OR mp.state LIKE :search
                    ORDER BY mp.mp_constituency_code, mla.mla_constituency_code";
            $stmt = $this->pdo->prepare($sql);
            $searchPattern = "%$searchTerm%";
            $stmt->bindParam(':search', $searchPattern);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error searching MLA records: " . $e->getMessage());
        }
    }
    
    // Get MLA records by MP ID
    public function getByMPId($mpId) {
        try {
            $sql = "SELECT * FROM MLA_Master WHERE mp_id = :mp_id ORDER BY mla_constituency_code";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mp_id', $mpId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error getting MLA records by MP ID: " . $e->getMessage());
        }
    }
    
    // Get all MP records for dropdown
    public function getMPRecords() {
        try {
            $sql = "SELECT mp_id, mp_constituency_name, state FROM MP_Master ORDER BY mp_constituency_code";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error getting MP records: " . $e->getMessage());
        }
    }
    
    // Check if MLA code exists within an MP constituency
    public function codeExists($mpId, $code, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM MLA_Master WHERE mp_id = :mp_id AND mla_constituency_code = :code";
            if ($excludeId) {
                $sql .= " AND mla_id != :exclude_id";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mp_id', $mpId);
            $stmt->bindParam(':code', $code);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId);
            }
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            throw new Exception("Error checking MLA code: " . $e->getMessage());
        }
    }
    
    // Get statistics
    public function getStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_mlas,
                        COUNT(DISTINCT mp_id) as total_mp_constituencies
                    FROM MLA_Master";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error getting MLA statistics: " . $e->getMessage());
        }
    }
    
    // Get statistics for MLA records (compatible with view pages)
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total MLA constituencies
            $sql = "SELECT COUNT(*) as total FROM MLA_Master";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['total_mla_constituencies'] = $stmt->fetchColumn();
            
            // Active records (assuming all are active for now)
            $stats['active_records'] = $stats['total_mla_constituencies'];
            
            // Total MP constituencies
            $sql = "SELECT COUNT(DISTINCT mp_id) as total FROM MLA_Master";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['total_mp_constituencies'] = $stmt->fetchColumn();
            
            return $stats;
        } catch(PDOException $e) {
            throw new Exception("Error getting statistics: " . $e->getMessage());
        }
    }
    
    // Read all MLA records with MP details (alias for readAll)
    public function readAllWithMP() {
        return $this->readAll();
    }
}
?>
