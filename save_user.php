<?php
require 'auth.php';
require 'db.php';

// Only SuperAdmin can create users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    die("Acceso denegado. Solo el SuperAdmin puede crear usuarios.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'admin';
    $portfolio_id = !empty($_POST['portfolio_id']) ? $_POST['portfolio_id'] : null;

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, portfolio_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $role, $portfolio_id]);
        header("Location: users.php");
    } catch (PDOException $e) {
        die("Error al crear usuario: " . $e->getMessage());
    }
}
?>
