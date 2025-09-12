<?php
require_once 'config.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

// Redirect to main page
header('Location: index.php');
exit;
?>
