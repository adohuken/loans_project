<?php
require 'db.php';

try {
    // Create portfolios table
    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabla 'portfolios' creada correctamente.<br>";

    // Check if portfolio_id column exists in clients table
    $stmt = $pdo->query("SHOW COLUMNS FROM clients LIKE 'portfolio_id'");
    $column = $stmt->fetch();

    if (!$column) {
        $pdo->exec("ALTER TABLE clients ADD COLUMN portfolio_id INT NULL");
        $pdo->exec("ALTER TABLE clients ADD CONSTRAINT fk_client_portfolio FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE SET NULL");
        echo "Columna 'portfolio_id' agregada a la tabla 'clients'.<br>";
    } else {
        echo "La columna 'portfolio_id' ya existe en 'clients'.<br>";
    }

} catch (PDOException $e) {
    die("Error al actualizar la base de datos: " . $e->getMessage());
}
?>
