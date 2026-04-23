<?php
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$portfolio_id = $_GET['portfolio_id'] ?? '';

if (strlen($query) < 2 && empty($portfolio_id)) {
    echo json_encode(['clients' => [], 'loans' => []]);
    exit;
}

$params = [];
$sql = "SELECT id, name, cedula, phone FROM clients WHERE 1=1";

if (!empty($query)) {
    $sql .= " AND (name LIKE ? OR cedula LIKE ? OR phone LIKE ?)";
    $searchTerm = "%{$query}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($portfolio_id)) {
    $sql .= " AND portfolio_id = ?";
    $params[] = $portfolio_id;
}

$sql .= " ORDER BY name ASC LIMIT 20";

// Search clients
$stmt_clients = $pdo->prepare($sql);
$stmt_clients->execute($params);
$clients = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);

// Search loans
$loans = [];
if (!empty($query)) {
    $searchTerm = "%{$query}%";
    $stmt_loans = $pdo->prepare("
        SELECT l.id, l.amount, l.status, c.name as client_name
        FROM loans l
        JOIN clients c ON l.client_id = c.id
        WHERE l.id LIKE ? OR c.name LIKE ?
        LIMIT 10
    ");
    $stmt_loans->execute([$searchTerm, $searchTerm]);
    $loans = $stmt_loans->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'clients' => $clients,
    'loans' => $loans
]);
