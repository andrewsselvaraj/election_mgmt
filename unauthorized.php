<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .unauthorized-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        
        .unauthorized-icon {
            font-size: 4em;
            color: #ff6b6b;
            margin-bottom: 20px;
        }
        
        .unauthorized-container h1 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .unauthorized-container p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="unauthorized-container">
        <div class="unauthorized-icon">🚫</div>
        <h1>Access Denied</h1>
        <p>You don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
        <a href="index.php" class="btn">Go to Dashboard</a>
        <a href="logout.php" class="btn" style="margin-left: 10px; background: #95a5a6;">Logout</a>
    </div>
</body>
</html>
