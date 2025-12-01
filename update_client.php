<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $cedula = $_POST['cedula'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $portfolio_id = !empty($_POST['portfolio_id']) ? $_POST['portfolio_id'] : null;

    try {
        $stmt = $pdo->prepare("UPDATE clients SET cedula = ?, name = ?, phone = ?, address = ?, portfolio_id = ? WHERE id = ?");
        $stmt->execute([$cedula, $name, $phone, $address, $portfolio_id, $id]);

        // Redirect back to clients list with success message (optional, but good practice)
        header("Location: clients.php");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Error: La cédula ya existe.";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
    exit;
} else {
    // If accessed directly without POST, redirect to clients
    header("Location: clients.php");
    exit;
}
?>