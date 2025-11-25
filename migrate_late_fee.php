<?php
require 'db.php';

try {
    // Check if late_fee column exists
    $columns = $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('late_fee', $columns)) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN late_fee DECIMAL(10,2) DEFAULT 0.00 AFTER paid_amount");
        echo "✓ Columna 'late_fee' (mora) agregada a la tabla payments<br>";
    } else {
        echo "✓ Columna 'late_fee' ya existe<br>";
    }

    echo "<br><strong style='color: green;'>✅ Migración completada!</strong><br>";
    echo "<a href='loan_details.php' style='display: inline-block; margin-top: 10px; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px;'>Volver</a>";

} catch (PDOException $e) {
    echo "<strong style='color: red;'>❌ Error:</strong> " . $e->getMessage();
}
?>