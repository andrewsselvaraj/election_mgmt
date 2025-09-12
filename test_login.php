<?php
require_once 'config.php';
require_once 'Auth.php';

echo "Testing Login Credentials...\n\n";

$auth = new Auth($pdo);

// Test credentials
$username = 'superadmin';
$password = 'admin123';

echo "Testing username: $username\n";
echo "Testing password: $password\n\n";

// Check if user exists
try {
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ User found in database\n";
        echo "User ID: " . $user['user_id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "First Name: " . $user['first_name'] . "\n";
        echo "Last Name: " . $user['last_name'] . "\n";
        echo "Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
        echo "Password Hash: " . substr($user['password_hash'], 0, 20) . "...\n\n";
        
        // Test password verification
        if (password_verify($password, $user['password_hash'])) {
            echo "✓ Password verification successful!\n";
        } else {
            echo "✗ Password verification failed!\n";
            echo "The stored hash does not match the password 'admin123'\n";
        }
        
        // Test login method
        echo "\nTesting Auth::login() method...\n";
        if ($auth->login($username, $password)) {
            echo "✓ Login successful!\n";
            $currentUser = $auth->getCurrentUser();
            echo "Current user: " . $currentUser['first_name'] . " " . $currentUser['last_name'] . "\n";
            echo "Roles: " . implode(', ', $currentUser['roles']) . "\n";
        } else {
            echo "✗ Login failed!\n";
        }
        
    } else {
        echo "✗ User not found in database\n";
        echo "Please run setup_database.php first\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
