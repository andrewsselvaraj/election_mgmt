<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('mp', 'delete')) {
    header('Location: mp_view.php?error=no_permission');
    exit;
}

$mpMaster = new MPMaster($pdo);
$mpId = $_POST['mp_id'] ?? '';

if (!$mpId) {
    header('Location: mp_view.php?error=no_id');
    exit;
}

// Check if MP has associated MLAs
$mlaCount = $mpMaster->getAssociatedMLACount($mpId);
if ($mlaCount > 0) {
    header('Location: mp_view.php?error=has_mlas&count=' . $mlaCount);
    exit;
}

// Delete the MP record
if ($mpMaster->delete($mpId)) {
    header('Location: mp_view.php?success=deleted');
} else {
    header('Location: mp_view.php?error=delete_failed');
}
exit;
