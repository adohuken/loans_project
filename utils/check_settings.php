<?php
require 'db.php';

// Check if settings exist
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();

if (!$settings) {
    echo "<h2>No hay configuración inicial</h2>";
    echo "<p>Creando configuración por defecto...</p>";

    // Insert default settings
    $pdo->exec("
        INSERT INTO settings (id, company_name, currency_symbol, logo_path, company_address, company_phone, receipt_footer)
        VALUES (1, 'Mi Empresa', '$', '', '', '', 'Gracias por su pago')
    ");

    echo "<p style='color: green;'>✓ Configuración creada exitosamente</p>";
    echo "<p><a href='settings.php'>Ir a Configuración para personalizar</a></p>";
} else {
    echo "<h2>Configuración Actual:</h2>";
    echo "<ul>";
    echo "<li><strong>Nombre:</strong> " . htmlspecialchars($settings['company_name']) . "</li>";
    echo "<li><strong>Moneda:</strong> " . htmlspecialchars($settings['currency_symbol']) . "</li>";
    echo "<li><strong>Logo:</strong> " . ($settings['logo_path'] ? htmlspecialchars($settings['logo_path']) : 'No configurado') . "</li>";
    echo "<li><strong>Dirección:</strong> " . ($settings['company_address'] ? htmlspecialchars($settings['company_address']) : 'No configurado') . "</li>";
    echo "<li><strong>Teléfono:</strong> " . ($settings['company_phone'] ? htmlspecialchars($settings['company_phone']) : 'No configurado') . "</li>";
    echo "<li><strong>Pie de recibo:</strong> " . ($settings['receipt_footer'] ? htmlspecialchars($settings['receipt_footer']) : 'No configurado') . "</li>";
    echo "</ul>";
    echo "<p><a href='settings.php'>Ir a Configuración</a></p>";
}
?>