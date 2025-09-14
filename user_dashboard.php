<?php
require_once 'config.php';
require_once 'UserAuth.php';

$userAuth = new UserAuth($pdo);
$userAuth->requireLogin();

$currentUser = $userAuth->getCurrentUser();

$pageTitle = 'User Dashboard - Election Management System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .user-details h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .user-details p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-icon {
            font-size: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .role-badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .permissions-list {
            list-style: none;
            padding: 0;
        }
        
        .permissions-list li {
            padding: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .permissions-list li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-section h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            font-size: 16px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏛️ Election Management System</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h3>
                    <p><?php echo htmlspecialchars($currentUser['username']); ?></p>
                </div>
                <a href="user_logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h2>
            <p>You are logged in as <?php echo htmlspecialchars($currentUser['role']); ?></p>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3><span class="card-icon">👤</span> User Information</h3>
                <div class="info-item">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Role:</span>
                    <span class="info-value">
                        <span class="role-badge"><?php echo htmlspecialchars($currentUser['role']); ?></span>
                    </span>
                </div>
            </div>
            
            <div class="card">
                <h3><span class="card-icon">🔐</span> Permissions</h3>
                <ul class="permissions-list">
                    <?php 
                    $permissions = $currentUser['permissions'];
                    if (!empty($permissions)) {
                        foreach ($permissions as $module => $actions) {
                            if (is_array($actions)) {
                                foreach ($actions as $action => $allowed) {
                                    if ($allowed) {
                                        echo "<li>" . ucfirst($module) . " - " . ucfirst($action) . "</li>";
                                    }
                                }
                            }
                        }
                    } else {
                        echo "<li>No specific permissions assigned</li>";
                    }
                    ?>
                </ul>
            </div>
            
            <div class="card">
                <h3><span class="card-icon">🏛️</span> System Access</h3>
                <div class="actions">
                    <?php if ($userAuth->hasPermission('mp', 'read')): ?>
                        <a href="mp_view.php" class="btn btn-primary">MP Master</a>
                    <?php endif; ?>
                    
                    <?php if ($userAuth->hasPermission('mla', 'read')): ?>
                        <a href="mla_index.php" class="btn btn-primary">MLA Master</a>
                    <?php endif; ?>
                    
                    <?php if ($userAuth->hasPermission('booth', 'read')): ?>
                        <a href="booth_index.php" class="btn btn-primary">Booth Master</a>
                    <?php endif; ?>
                    
                    <?php if ($userAuth->hasPermission('voter', 'read')): ?>
                        <a href="voter_view.php" class="btn btn-primary">Voter Information</a>
                    <?php endif; ?>
                    
                    <?php if ($userAuth->hasPermission('user', 'read')): ?>
                        <a href="user_view.php" class="btn btn-primary">User Management</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3><span class="card-icon">ℹ️</span> System Information</h3>
            <div class="info-item">
                <span class="info-label">Login Time:</span>
                <span class="info-value"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Session Status:</span>
                <span class="info-value">Active</span>
            </div>
            <div class="info-item">
                <span class="info-label">System Version:</span>
                <span class="info-value">Election Management v1.0</span>
            </div>
        </div>
    </div>
</body>
</html>
