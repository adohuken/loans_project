<?php
// db.php

// Configuración de la base de datos MySQL
$host = 'localhost';
$dbname = 'loans_db';
$username = 'root'; // Usuario por defecto de XAMPP
$password = '';     // Contraseña por defecto de XAMPP (vacía)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // La creación de tablas se maneja externamente con el script SQL proporcionado.

} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>