<?php
require 'db.php';

try {
    $pdo->exec("ALTER TABLE payments ADD COLUMN paid_late_fee DECIMAL(10, 2) DEFAULT 0.00 AFTER paid_amount");
    echo "Columna paid_late_fee agregada exitosamente.";
} catch (PDOException $e) {
    echo "Error (puede que la columna ya exista): " . $e->getMessage();
}
?>