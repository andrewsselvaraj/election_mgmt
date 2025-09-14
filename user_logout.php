<?php
require_once 'config.php';
require_once 'UserAuth.php';

$userAuth = new UserAuth($pdo);

// Logout user
$userAuth->logout();

// Redirect to login page
header('Location: user_login.php');
exit;
?>
