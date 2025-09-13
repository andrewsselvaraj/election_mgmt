<?php
$pageTitle = 'Unauthorized Access - Election Management System';
include 'header.php';
?>

<style>
    .unauthorized-container {
        max-width: 600px;
        margin: 0 auto;
        text-align: center;
        padding: 50px 20px;
    }
    
    .unauthorized-icon {
        font-size: 4em;
        color: #dc3545;
        margin-bottom: 20px;
    }
    
    .unauthorized-title {
        font-size: 2em;
        color: #333;
        margin-bottom: 15px;
    }
    
    .unauthorized-message {
        font-size: 1.1em;
        color: #666;
        margin-bottom: 30px;
        line-height: 1.6;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>

<div class="unauthorized-container">
    <div class="unauthorized-icon">🚫</div>
    <h1 class="unauthorized-title">Access Denied</h1>
    <p class="unauthorized-message">
        You don't have permission to access this page. Please contact your administrator 
        if you believe this is an error.
    </p>
    
    <div class="action-buttons">
        <a href="javascript:history.back()" class="btn btn-secondary">← Go Back</a>
        <a href="mp_view.php" class="btn btn-primary">🏠 Home</a>
    </div>
</div>

<?php include 'footer.php'; ?>