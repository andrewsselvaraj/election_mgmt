<?php
require_once 'config.php';

class MPMaster {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new MP record
    public function create($data) {
        try {
            $sql = "INSERT INTO mp_master (mp_constituency_code, mp_constituency_name, state, created_by) 
                    VALUES (:code, :name, :state, :created_by)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':code', $data['mp_constituency_code']);
            $stmt->bindParam(':name', $data['mp_constituency_name']);
            $stmt->bindParam(':state', $data['state']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error creating record: " . $e->getMessage());
        }
    }
    
    // Read all MP records
    public function readAll() {
        try {
            $sql = "SELECT * FROM mp_master ORDER BY mp_constituency_code";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error reading records: " . $e->getMessage());
        }
    }
    
    // Read a single MP record by ID
    public function readById($id) {
        try {
            $sql = "SELECT * FROM mp_master WHERE mp_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error reading record: " . $e->getMessage());
        }
    }
    
    // Update an MP record
    public function update($data) {
        try {
            $sql = "UPDATE mp_master SET 
                    mp_constituency_code = :code,
                    mp_constituency_name = :name,
                    state = :state,
                    updated_by = :updated_by
                    WHERE mp_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':code', $data['mp_constituency_code']);
            $stmt->bindParam(':name', $data['mp_constituency_name']);
            $stmt->bindParam(':state', $data['state']);
            $stmt->bindParam(':updated_by', $data['updated_by']);
            $stmt->bindParam(':id', $data['mp_id']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error updating record: " . $e->getMessage());
        }
    }
    
    // Delete an MP record
    public function delete($id) {
        try {
            $sql = "DELETE FROM mp_master WHERE mp_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error deleting record: " . $e->getMessage());
        }
    }
    
    // Search MP records
    public function search($searchTerm) {
        try {
            $sql = "SELECT * FROM mp_master 
                    WHERE mp_constituency_name LIKE :search 
                    OR state LIKE :search
                    ORDER BY mp_constituency_code";
            $stmt = $this->pdo->prepare($sql);
            $searchPattern = "%$searchTerm%";
            $stmt->bindParam(':search', $searchPattern);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error searching records: " . $e->getMessage());
        }
    }
    
    // Get unique states
    public function getStates() {
        try {
            $sql = "SELECT DISTINCT state FROM mp_master ORDER BY state";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch(PDOException $e) {
            throw new Exception("Error getting states: " . $e->getMessage());
        }
    }
    
    // Check if constituency code already exists
    public function codeExists($code, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM mp_master WHERE mp_constituency_code = :code";
            if ($excludeId) {
                $sql .= " AND mp_id != :exclude_id";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':code', $code);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId);
            }
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            throw new Exception("Error checking code: " . $e->getMessage());
        }
    }
    
    // Get statistics for MP records
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total MP constituencies
            $sql = "SELECT COUNT(*) as total FROM mp_master";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['total_mp_constituencies'] = $stmt->fetchColumn();
            
            // Active records (assuming all are active for now)
            $stats['active_records'] = $stats['total_mp_constituencies'];
            
            // States covered
            $sql = "SELECT COUNT(DISTINCT state) as states FROM mp_master";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['states_covered'] = $stmt->fetchColumn();
            
            return $stats;
        } catch(PDOException $e) {
            throw new Exception("Error getting statistics: " . $e->getMessage());
        }
    }
    
    // Get count of associated MLA records
    public function getAssociatedMLACount($mpId) {
        try {
            $sql = "SELECT COUNT(*) FROM mla_master WHERE mp_id = :mp_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mp_id', $mpId);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch(PDOException $e) {
            throw new Exception("Error getting MLA count: " . $e->getMessage());
        }
    }
}
?>
