<?php
require 'db.php';

// Check admin user role
$stmt = $pdo->query("SELECT username, role FROM users WHERE username = 'admin'");
$user = $stmt->fetch();

echo "<h2>Usuario Admin:</h2>";
echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
echo "<p>Role: " . htmlspecialchars($user['role']) . "</p>";

echo "<hr>";
echo "<h2>Todos los usuarios:</h2>";
$stmt = $pdo->query("SELECT id, username, role FROM users");
$users = $stmt->fetchAll();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
foreach ($users as $u) {
    echo "<tr>";
    echo "<td>" . $u['id'] . "</td>";
    echo "<td>" . htmlspecialchars($u['username']) . "</td>";
    echo "<td>" . htmlspecialchars($u['role']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>