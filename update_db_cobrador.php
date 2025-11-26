<?php
require 'db.php';

try {
    // Update users table to add portfolio_id column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'portfolio_id'");
    $column = $stmt->fetch();

    if (!$column) {
        $pdo->exec("ALTER TABLE users ADD COLUMN portfolio_id INT NULL");
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_user_portfolio FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE SET NULL");
        echo "Columna 'portfolio_id' agregada a la tabla 'users'.<br>";
    } else {
        echo "La columna 'portfolio_id' ya existe en 'users'.<br>";
    }

    // Update role enum to include 'cobrador'
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'superadmin', 'cobrador') DEFAULT 'admin'");
    echo "Rol 'cobrador' agregado a la tabla 'users'.<br>";

    echo "<br>✅ Base de datos actualizada correctamente para el rol de cobrador.";

} catch (PDOException $e) {
    die("Error al actualizar la base de datos: " . $e->getMessage());
}
?>
