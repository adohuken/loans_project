<?php
require 'db.php';

echo "<h2>Diagnóstico de Moras</h2>";

// 1. Check pending late fees (late_fee column)
$stmt = $pdo->query("SELECT SUM(late_fee) as pending_late_fees FROM payments");
$pending = $stmt->fetchColumn();
echo "<p>Total Mora Pendiente (columna 'late_fee'): <strong>$" . number_format($pending, 2) . "</strong></p>";

// 2. Check collected late fees (paid_late_fee column)
$stmt = $pdo->query("SELECT SUM(paid_late_fee) as collected_late_fees FROM payments");
$collected = $stmt->fetchColumn();
echo "<p>Total Mora Cobrada (columna 'paid_late_fee'): <strong>$" . number_format($collected, 2) . "</strong></p>";

// 3. List payments with late fees
echo "<h3>Detalle de Pagos con Mora (Pendiente > 0 o Cobrada > 0)</h3>";
$stmt = $pdo->query("
    SELECT p.id, p.loan_id, p.amount_due, p.late_fee, p.paid_late_fee, p.status 
    FROM payments p 
    WHERE p.late_fee > 0 OR p.paid_late_fee > 0
    LIMIT 20
");
$payments = $stmt->fetchAll();

if (count($payments) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID Pago</th><th>ID Préstamo</th><th>Monto Cuota</th><th>Mora Pendiente</th><th>Mora Cobrada</th><th>Estado</th></tr>";
    foreach ($payments as $p) {
        echo "<tr>";
        echo "<td>" . $p['id'] . "</td>";
        echo "<td>" . $p['loan_id'] . "</td>";
        echo "<td>" . $p['amount_due'] . "</td>";
        echo "<td>" . $p['late_fee'] . "</td>";
        echo "<td>" . $p['paid_late_fee'] . "</td>";
        echo "<td>" . $p['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron pagos con mora registrada.</p>";
}
?>