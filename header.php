<?php
// Common header for all pages
if (!isset($auth)) {
    require_once 'config.php';
    require_once 'Auth.php';
    $auth = new Auth($pdo);
    $auth->requireLogin();
}

$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Election Management System'; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header-section h1 {
            margin: 0;
            font-size: 2em;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        
        .current-page {
            background: rgba(255,255,255,0.3) !important;
            font-weight: bold;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .user-info p {
            margin: 0;
            color: #333;
        }
        
        .user-role {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .page-header p {
            margin: 0;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-actions {
                margin-top: 15px;
                width: 100%;
                justify-content: flex-start;
            }
            
            .user-info {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1><?php echo $pageTitle ?? 'Election Management System'; ?></h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-primary">🏠 Home</a>
                <a href="mp_view.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_index.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="booth_index.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="voter_view.php" class="btn btn-secondary">🗳️ Voter Information</a>
                <?php if ($auth->hasPermission('user', 'read')): ?>
                    <a href="user_view.php" class="btn btn-warning">👥 Users</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong></p>
            <div>
                <span class="user-role"><?php echo $currentUser['role'] ?? 'USER'; ?></span>
            </div>
        </div>
