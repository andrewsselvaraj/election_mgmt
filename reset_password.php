<?php
require_once 'config.php';

// Generate password hash for 'admin123'
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";

// Update the superadmin user with the correct hash
try {
    $sql = "UPDATE users SET password_hash = :hash WHERE username = 'superadmin'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':hash', $hash);
    
    if ($stmt->execute()) {
        echo "Password updated successfully!\n";
    } else {
        echo "Failed to update password.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test the login
$testPassword = 'admin123';
if (password_verify($testPassword, $hash)) {
    echo "Password verification successful!\n";
} else {
    echo "Password verification failed!\n";
}
?>
