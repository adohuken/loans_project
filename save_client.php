<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $_POST['cedula'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $portfolio_id = !empty($_POST['portfolio_id']) ? $_POST['portfolio_id'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO clients (cedula, name, phone, address, portfolio_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$cedula, $name, $phone, $address, $portfolio_id]);
        header("Location: clients.php");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Error: La cédula ya existe.";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
    exit;
}
?>
