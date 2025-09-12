<?php
require_once 'config.php';
require_once 'MPMaster.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Record ID is required');
    }
    
    $mpMaster = new MPMaster($pdo);
    $record = $mpMaster->readById($_GET['id']);
    
    if ($record) {
        echo json_encode([
            'success' => true,
            'record' => $record
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Record not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
