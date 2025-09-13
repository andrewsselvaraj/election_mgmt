<?php
require_once 'config.php';

class VoterMaster {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new Voter record
    public function create($data) {
        try {
            $sql = "INSERT INTO voter_master (voter_id, mla_id, voter_name, father_name, mother_name, 
                    husband_name, age, gender, address, phone, email, booth_id, ward_no, part_no, 
                    created_by) 
                    VALUES (:voter_id, :mla_id, :voter_name, :father_name, :mother_name, 
                    :husband_name, :age, :gender, :address, :phone, :email, :booth_id, :ward_no, :part_no, 
                    :created_by)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':voter_id', $data['voter_id']);
            $stmt->bindParam(':mla_id', $data['mla_id']);
            $stmt->bindParam(':voter_name', $data['voter_name']);
            $stmt->bindParam(':father_name', $data['father_name']);
            $stmt->bindParam(':mother_name', $data['mother_name']);
            $stmt->bindParam(':husband_name', $data['husband_name']);
            $stmt->bindParam(':age', $data['age']);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':booth_id', $data['booth_id']);
            $stmt->bindParam(':ward_no', $data['ward_no']);
            $stmt->bindParam(':part_no', $data['part_no']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Voter record created successfully',
                    'voter_unique_id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create voter record'
                ];
            }
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error creating voter record: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all Voter records with MLA and Booth details
    public function readAll() {
        try {
            $sql = "SELECT v.*, m.mla_constituency_name, m.mla_name, b.polling_station_no, b.location_name_of_building
                    FROM voter_master v
                    LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id
                    LEFT JOIN booth_master b ON v.booth_id = b.booth_id
                    WHERE v.status = 'ACTIVE'
                    ORDER BY v.created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Read voters by MLA ID
    public function readByMLA($mlaId) {
        try {
            $sql = "SELECT v.*, m.mla_constituency_name, m.mla_name, b.polling_station_no, b.location_name_of_building
                    FROM voter_master v
                    LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id
                    LEFT JOIN booth_master b ON v.booth_id = b.booth_id
                    WHERE v.mla_id = :mla_id AND v.status = 'ACTIVE'
                    ORDER BY v.created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $mlaId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Read voters by Booth ID
    public function readByBooth($boothId) {
        try {
            $sql = "SELECT v.*, m.mla_constituency_name, m.mla_name, b.polling_station_no, b.location_name_of_building
                    FROM voter_master v
                    LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id
                    LEFT JOIN booth_master b ON v.booth_id = b.booth_id
                    WHERE v.booth_id = :booth_id AND v.status = 'ACTIVE'
                    ORDER BY v.created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':booth_id', $boothId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Read voter by ID
    public function readById($voterUniqueId) {
        try {
            $sql = "SELECT v.*, m.mla_constituency_name, m.mla_name, b.polling_station_no, b.location_name_of_building
                    FROM voter_master v
                    LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id
                    LEFT JOIN booth_master b ON v.booth_id = b.booth_id
                    WHERE v.voter_unique_ID = :voter_unique_id AND v.status = 'ACTIVE'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':voter_unique_id', $voterUniqueId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Update voter record
    public function update($voterUniqueId, $data) {
        try {
            $sql = "UPDATE voter_master SET 
                    voter_id = :voter_id, 
                    mla_id = :mla_id, 
                    voter_name = :voter_name, 
                    father_name = :father_name, 
                    mother_name = :mother_name, 
                    husband_name = :husband_name, 
                    age = :age, 
                    gender = :gender, 
                    address = :address, 
                    phone = :phone, 
                    email = :email, 
                    booth_id = :booth_id, 
                    ward_no = :ward_no, 
                    part_no = :part_no, 
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE voter_unique_ID = :voter_unique_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':voter_id', $data['voter_id']);
            $stmt->bindParam(':mla_id', $data['mla_id']);
            $stmt->bindParam(':voter_name', $data['voter_name']);
            $stmt->bindParam(':father_name', $data['father_name']);
            $stmt->bindParam(':mother_name', $data['mother_name']);
            $stmt->bindParam(':husband_name', $data['husband_name']);
            $stmt->bindParam(':age', $data['age']);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':booth_id', $data['booth_id']);
            $stmt->bindParam(':ward_no', $data['ward_no']);
            $stmt->bindParam(':part_no', $data['part_no']);
            $stmt->bindParam(':updated_by', $data['updated_by']);
            $stmt->bindParam(':voter_unique_id', $voterUniqueId);
            
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Voter record updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update voter record'
                ];
            }
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating voter record: ' . $e->getMessage()
            ];
        }
    }
    
    // Soft delete voter record
    public function delete($voterUniqueId, $deletedBy) {
        try {
            $sql = "UPDATE voter_master SET 
                    status = 'DELETED', 
                    updated_by = :updated_by,
                    updated_datetime = CURRENT_TIMESTAMP
                    WHERE voter_unique_ID = :voter_unique_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':updated_by', $deletedBy);
            $stmt->bindParam(':voter_unique_id', $voterUniqueId);
            
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Voter record deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete voter record'
                ];
            }
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deleting voter record: ' . $e->getMessage()
            ];
        }
    }
    
    // Get voter statistics
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total voters
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM voter_master WHERE status = 'ACTIVE'");
            $stats['total'] = $stmt->fetchColumn();
            
            // Voters by gender
            $stmt = $this->pdo->query("SELECT gender, COUNT(*) as count FROM voter_master WHERE status = 'ACTIVE' GROUP BY gender");
            $stats['by_gender'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Voters by MLA
            $stmt = $this->pdo->query("SELECT m.mla_name, COUNT(v.voter_unique_ID) as count 
                                     FROM voter_master v 
                                     LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id 
                                     WHERE v.status = 'ACTIVE' 
                                     GROUP BY v.mla_id, m.mla_name 
                                     ORDER BY count DESC");
            $stats['by_mla'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Voters by ward
            $stmt = $this->pdo->query("SELECT ward_no, COUNT(*) as count FROM voter_master WHERE status = 'ACTIVE' AND ward_no IS NOT NULL GROUP BY ward_no ORDER BY count DESC");
            $stats['by_ward'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Voters by part
            $stmt = $this->pdo->query("SELECT part_no, COUNT(*) as count FROM voter_master WHERE status = 'ACTIVE' AND part_no IS NOT NULL GROUP BY part_no ORDER BY count DESC");
            $stats['by_part'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch(PDOException $e) {
            return [
                'total' => 0,
                'by_gender' => [],
                'by_mla' => [],
                'by_ward' => [],
                'by_part' => []
            ];
        }
    }
    
    // Search voters
    public function search($searchTerm) {
        try {
            $sql = "SELECT v.*, m.mla_constituency_name, m.mla_name, b.polling_station_no, b.location_name_of_building
                    FROM voter_master v
                    LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id
                    LEFT JOIN booth_master b ON v.booth_id = b.booth_id
                    WHERE v.status = 'ACTIVE' AND 
                    (v.voter_name LIKE :search OR v.voter_id LIKE :search OR v.father_name LIKE :search OR 
                     v.mother_name LIKE :search OR v.husband_name LIKE :search OR v.address LIKE :search)
                    ORDER BY v.created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $searchPattern = '%' . $searchTerm . '%';
            $stmt->bindParam(':search', $searchPattern);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Get voters by ward
    public function getByWard($wardNo) {
        try {
            $sql = "SELECT v.*, m.mla_constituency_name, m.mla_name, b.polling_station_no, b.location_name_of_building
                    FROM voter_master v
                    LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id
                    LEFT JOIN booth_master b ON v.booth_id = b.booth_id
                    WHERE v.ward_no = :ward_no AND v.status = 'ACTIVE'
                    ORDER BY v.created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':ward_no', $wardNo);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Get voters by part
    public function getByPart($partNo) {
        try {
            $sql = "SELECT v.*, m.mla_constituency_name, m.mla_name, b.polling_station_no, b.location_name_of_building
                    FROM voter_master v
                    LEFT JOIN MLA_Master m ON v.mla_id = m.mla_id
                    LEFT JOIN booth_master b ON v.booth_id = b.booth_id
                    WHERE v.part_no = :part_no AND v.status = 'ACTIVE'
                    ORDER BY v.created_datetime DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':part_no', $partNo);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Check if voter ID exists
    public function voterIdExists($voterId, $mlaId, $excludeVoterUniqueId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM voter_master WHERE voter_id = :voter_id AND mla_id = :mla_id AND status = 'ACTIVE'";
            if ($excludeVoterUniqueId) {
                $sql .= " AND voter_unique_ID != :exclude_voter_unique_id";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':voter_id', $voterId);
            $stmt->bindParam(':mla_id', $mlaId);
            if ($excludeVoterUniqueId) {
                $stmt->bindParam(':exclude_voter_unique_id', $excludeVoterUniqueId);
            }
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Bulk insert voters
    public function bulkInsert($voters) {
        try {
            $this->pdo->beginTransaction();
            
            $sql = "INSERT INTO voter_master (voter_id, mla_id, voter_name, father_name, mother_name, 
                    husband_name, age, gender, address, phone, email, booth_id, ward_no, part_no, 
                    created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            $successCount = 0;
            $errors = [];
            
            foreach ($voters as $index => $voter) {
                try {
                    $result = $stmt->execute([
                        $voter['voter_id'],
                        $voter['mla_id'],
                        $voter['voter_name'],
                        $voter['father_name'],
                        $voter['mother_name'],
                        $voter['husband_name'],
                        $voter['age'],
                        $voter['gender'],
                        $voter['address'],
                        $voter['phone'],
                        $voter['email'],
                        $voter['booth_id'],
                        $voter['ward_no'],
                        $voter['part_no'],
                        $voter['created_by']
                    ]);
                    
                    if ($result) {
                        $successCount++;
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Failed to insert voter record";
                    }
                } catch (Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Bulk insert completed. Success: $successCount, Errors: " . count($errors),
                'success_count' => $successCount,
                'error_count' => count($errors),
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Bulk insert failed: ' . $e->getMessage(),
                'success_count' => 0,
                'error_count' => count($voters),
                'errors' => [$e->getMessage()]
            ];
        }
    }
}
?>
