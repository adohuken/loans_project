<?php
require 'db.php';

try {
    $pdo->beginTransaction();
    
    // Clear late fees and paid late fees for all payments of loan 1 (or all loans if desired)
    $stmt = $pdo->prepare("UPDATE payments SET late_fee = 0, paid_late_fee = 0");
    $stmt->execute();
    
    // Also clear any late fee details from transactions to maintain consistency
    $pdo->exec("DELETE FROM transaction_details WHERE type = 'late_fee'");
    
    $pdo->commit();
    echo "<h1 style='color: green;'>✅ Moras limpiadas con éxito en la base de datos!</h1>";
    echo "<p>Todas las moras acumuladas y pagadas se han establecido en $0.00.</p>";
    echo "<a href='loan_details.php?id=1'>Volver al préstamo</a>";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h1 style='color: red;'>❌ Error al limpiar moras: " . $e->getMessage() . "</h1>";
}
?>
