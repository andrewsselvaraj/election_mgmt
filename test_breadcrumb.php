<?php
require_once 'breadcrumb_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breadcrumb Test</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Breadcrumb Navigation Test</h1>
        
        <h2>Main Pages</h2>
        <h3>MP Master</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('index.php'); ?>
        
        <h3>MLA Master</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('mla_index.php'); ?>
        
        <h3>Booth Master</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('booth_index.php'); ?>
        
        <h2>Upload Pages</h2>
        <h3>MP Upload</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('upload.php'); ?>
        
        <h3>MLA Upload</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('mla_upload.php'); ?>
        
        <h3>Booth Upload</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('booth_upload.php'); ?>
        
        <h2>Other Pages</h2>
        <h3>User Management</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('user_management.php'); ?>
        
        <h3>Login</h3>
        <?php echo BreadcrumbHelper::getBreadcrumbForPage('login.php'); ?>
    </div>
</body>
</html>
