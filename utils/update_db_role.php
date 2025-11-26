<?php
require 'db.php';

try {
    // 1. Add 'role' column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'superadmin') DEFAULT 'admin' AFTER password");
        echo "Column 'role' added successfully.\n";
    } else {
        echo "Column 'role' already exists.\n";
    }

    // 2. Create SuperAdmin user
    $username = 'SuperAdmin';
    $password = 'SuperAdmin123'; // Default password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'superadmin';

    // Check if SuperAdmin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, $role]);
        echo "User 'SuperAdmin' created successfully.\n";
    } else {
        // Update role just in case
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ?");
        $stmt->execute([$role, $username]);
        echo "User 'SuperAdmin' already exists. Role updated.\n";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>