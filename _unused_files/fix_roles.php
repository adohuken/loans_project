<?php
require 'db.php';

try {
    // 1. Cambiar el rol de "admin" a "cobrador" (o el rol que prefieras)
    $stmt = $pdo->prepare("UPDATE users SET role = 'cobrador' WHERE username = 'admin'");
    $stmt->execute();
    echo "✅ Usuario 'admin' cambiado a rol 'cobrador'<br>";

    // 2. Crear un nuevo usuario "superadmin" (el dueño del sistema)
    $superadmin_password = password_hash('superadmin123', PASSWORD_DEFAULT);

    // Verificar si ya existe un usuario superadmin
    $check = $pdo->query("SELECT id FROM users WHERE username = 'superadmin'")->fetch();

    if (!$check) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['superadmin', $superadmin_password, 'superadmin']);
        echo "✅ Usuario 'superadmin' creado con contraseña: <strong>superadmin123</strong><br>";
        echo "<p style='color: red;'>⚠️ IMPORTANTE: Cambia esta contraseña después de iniciar sesión</p>";
    } else {
        echo "ℹ️ El usuario 'superadmin' ya existe<br>";
    }

    echo "<hr>";
    echo "<h3>Usuarios actualizados:</h3>";
    $users = $pdo->query("SELECT username, role FROM users")->fetchAll();
    echo "<table border='1'>";
    echo "<tr><th>Username</th><th>Role</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>" . htmlspecialchars($u['username']) . "</td><td>" . htmlspecialchars($u['role']) . "</td></tr>";
    }
    echo "</table>";

    echo "<br><a href='login.php'>Ir a Login</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>