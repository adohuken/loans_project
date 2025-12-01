<?php
require 'db.php';

echo "<h2>Reparando Base de Datos...</h2>";

try {
    // 1. Check if payment_frequency column exists in loans table
    $stmt = $pdo->query("SHOW COLUMNS FROM loans LIKE 'payment_frequency'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Agregando columna 'payment_frequency' a la tabla loans...<br>";
        $pdo->exec("ALTER TABLE loans ADD COLUMN payment_frequency VARCHAR(20) DEFAULT 'mensual' AFTER interest_rate");
        echo "✅ Columna 'payment_frequency' agregada correctamente.<br>";
    } else {
        echo "ℹ️ La columna 'payment_frequency' ya existe.<br>";
    }

    // 2. Check if total_installments column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM loans LIKE 'total_installments'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Agregando columna 'total_installments' a la tabla loans...<br>";
        $pdo->exec("ALTER TABLE loans ADD COLUMN total_installments INT DEFAULT 0 AFTER payment_frequency");
        echo "✅ Columna 'total_installments' agregada correctamente.<br>";
    } else {
        echo "ℹ️ La columna 'total_installments' ya existe.<br>";
    }

    // 3. Check if installment_amount column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM loans LIKE 'installment_amount'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Agregando columna 'installment_amount' a la tabla loans...<br>";
        $pdo->exec("ALTER TABLE loans ADD COLUMN installment_amount DECIMAL(10,2) DEFAULT 0.00 AFTER total_installments");
        echo "✅ Columna 'installment_amount' agregada correctamente.<br>";
    } else {
        echo "ℹ️ La columna 'installment_amount' ya existe.<br>";
    }

    // 4. Check if portfolio_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM loans LIKE 'portfolio_id'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Agregando columna 'portfolio_id' a la tabla loans...<br>";
        $pdo->exec("ALTER TABLE loans ADD COLUMN portfolio_id INT DEFAULT NULL AFTER start_date");
        echo "✅ Columna 'portfolio_id' agregada correctamente.<br>";
    } else {
        echo "ℹ️ La columna 'portfolio_id' ya existe.<br>";
    }

    // 5. Check if created_at column exists in loans table
    $stmt = $pdo->query("SHOW COLUMNS FROM loans LIKE 'created_at'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Agregando columna 'created_at' a la tabla loans...<br>";
        $pdo->exec("ALTER TABLE loans ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Columna 'created_at' agregada correctamente a loans.<br>";
    } else {
        echo "ℹ️ La columna 'created_at' ya existe en loans.<br>";
    }

    // 6. Check if created_at column exists in payments table
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'created_at'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Agregando columna 'created_at' a la tabla payments...<br>";
        $pdo->exec("ALTER TABLE payments ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Columna 'created_at' agregada correctamente a payments.<br>";
    } else {
        echo "ℹ️ La columna 'created_at' ya existe en payments.<br>";
    }

    echo "<br><strong>¡Reparación completada! Ahora puedes intentar inicializar la cartera nuevamente.</strong>";
    echo "<br><br><a href='initialize_portfolio.php'>Volver a Inicializar Cartera</a>";

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "Error al reparar la base de datos: " . $e->getMessage();
    echo "</div>";
}
?>