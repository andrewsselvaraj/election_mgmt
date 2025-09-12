<?php
require_once 'config.php';
require_once 'BoothMaster.php';
require_once 'Auth.php';

header('Content-Type: application/json');

try {
    $auth = new Auth($pdo);
    $auth->requireLogin();
    
    if (!$auth->hasPermission('booth', 'read')) {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to read booth records'
        ]);
        exit;
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Booth ID is required'
        ]);
        exit;
    }
    
    $boothMaster = new BoothMaster($pdo);
    $record = $boothMaster->readById($_GET['id']);
    
    if (!$record) {
        echo json_encode([
            'success' => false,
            'message' => 'Booth record not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'record' => $record
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
