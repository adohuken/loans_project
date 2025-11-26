<?php
require 'db.php';

try {
    // Check if columns exist in MySQL
    $stmt = $pdo->query("SHOW COLUMNS FROM settings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Add company_address if it doesn't exist
    if (!in_array('company_address', $columns)) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN company_address VARCHAR(255) DEFAULT ''");
        echo "✓ Columna 'company_address' agregada<br>";
    } else {
        echo "✓ Columna 'company_address' ya existe<br>";
    }

    // Add company_phone if it doesn't exist
    if (!in_array('company_phone', $columns)) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN company_phone VARCHAR(50) DEFAULT ''");
        echo "✓ Columna 'company_phone' agregada<br>";
    } else {
        echo "✓ Columna 'company_phone' ya existe<br>";
    }

    // Add receipt_footer if it doesn't exist
    if (!in_array('receipt_footer', $columns)) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN receipt_footer TEXT");
        echo "✓ Columna 'receipt_footer' agregada<br>";
    } else {
        echo "✓ Columna 'receipt_footer' ya existe<br>";
    }

    echo "<br><strong style='color: green;'>✅ Migración completada exitosamente!</strong><br>";
    echo "<a href='settings.php' style='display: inline-block; margin-top: 10px; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px;'>Ir a Configuración</a>";

} catch (PDOException $e) {
    echo "<strong style='color: red;'>❌ Error:</strong> " . $e->getMessage();
}
?>