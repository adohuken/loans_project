<?php
require 'db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY DEFAULT 1,
        company_name VARCHAR(255) DEFAULT 'Mi Empresa',
        currency_symbol VARCHAR(10) DEFAULT '$',
        logo_path VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    
    INSERT IGNORE INTO settings (id, company_name, currency_symbol) VALUES (1, 'Mi Empresa', '$');
    ";

    $pdo->exec($sql);
    echo "Tabla 'settings' creada/verificada correctamente. <a href='settings.php'>Ir a Configuración</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>