<?php
require_once 'config.php';
require_once 'MLAMaster.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Record ID is required');
    }
    
    $mlaMaster = new MLAMaster($pdo);
    $record = $mlaMaster->readById($_GET['id']);
    
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
