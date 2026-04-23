<?php
require 'auth.php';
require 'db.php';

$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

echo "Debug Range: $start_date to $end_date\n\n";

// 1. List all portfolios
echo "--- Portfolios ---\n";
$portfolios = $pdo->query("SELECT * FROM portfolios")->fetchAll();
foreach ($portfolios as $p) {
    echo "ID: " . $p['id'] . " | Name: " . $p['name'] . "\n";
}

echo "\n--- Payments by Portfolio (This Month) ---\n";
foreach ($portfolios as $p) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM payments p
        JOIN loans l ON p.loan_id = l.id
        JOIN clients c ON l.client_id = c.id
        WHERE p.paid_date BETWEEN ? AND ? 
        AND p.status = 'paid'
        AND c.portfolio_id = ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59', $p['id']]);
    $count = $stmt->fetchColumn();
    echo "Portfolio: " . $p['name'] . " (" . $p['id'] . ") -> Count: " . $count . "\n";
}
?>