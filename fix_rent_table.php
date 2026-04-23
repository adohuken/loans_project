<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS rent_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_name VARCHAR(255) NOT NULL,
    tenant_id_number VARCHAR(50),
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    concept TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

try {
    $pdo->exec($sql);
    echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
    echo "<h1 style='color: #10b981;'>✅ Tabla 'rent_receipts' creada con éxito</h1>";
    echo "<p>Ya puedes empezar a generar tus recibos de renta.</p>";
    echo "<a href='rent_receipts.php' style='display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Ir al Historial de Renta</a>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
    echo "<h1 style='color: #ef4444;'>❌ Error al crear la tabla</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
