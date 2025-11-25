<?php
require 'db.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hash, $username]);
        echo "Contraseña actualizada para el usuario 'admin'.<br>";
    } else {
        // Create user
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hash]);
        echo "Usuario 'admin' creado.<br>";
    }

    echo "Nuevo Hash: " . $hash . "<br>";
    echo "Prueba iniciar sesión con: <b>admin</b> / <b>admin123</b><br>";
    echo "<a href='login.php'>Ir al Login</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>