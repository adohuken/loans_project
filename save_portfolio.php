<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];

    try {
        $stmt = $pdo->prepare("INSERT INTO portfolios (name) VALUES (?)");
        $stmt->execute([$name]);
        header("Location: portfolios.php");
    } catch (PDOException $e) {
        die("Error al crear cartera: " . $e->getMessage());
    }
}
?>
