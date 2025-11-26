<?php
require 'auth.php';
require 'db.php';

// Only SuperAdmin can update users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    die("Acceso denegado. Solo el SuperAdmin puede actualizar usuarios.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'admin';
    $portfolio_id = !empty($_POST['portfolio_id']) ? $_POST['portfolio_id'] : null;

    try {
        if (!empty($password)) {
            // Update with password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ?, portfolio_id = ? WHERE id = ?");
            $stmt->execute([$username, $hash, $role, $portfolio_id, $id]);
        } else {
            // Update without password
            $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, portfolio_id = ? WHERE id = ?");
            $stmt->execute([$username, $role, $portfolio_id, $id]);
        }

        header("Location: users.php");
    } catch (PDOException $e) {
        die("Error al actualizar usuario: " . $e->getMessage());
    }
}
?>
