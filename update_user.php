<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            // Update with password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $hash, $id]);
        } else {
            // Update only username
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$username, $id]);
        }

        header("Location: users.php");
    } catch (PDOException $e) {
        die("Error al actualizar usuario: " . $e->getMessage());
    }
}
?>