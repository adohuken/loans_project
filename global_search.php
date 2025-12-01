<?php
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['clients' => [], 'loans' => []]);
    exit;
}

$searchTerm = "%{$query}%";

// Search clients
$stmt_clients = $pdo->prepare("
    SELECT id, name, cedula, phone 
    FROM clients 
    WHERE name LIKE ? OR cedula LIKE ? OR phone LIKE ?
    LIMIT 10
");
$stmt_clients->execute([$searchTerm, $searchTerm, $searchTerm]);
$clients = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);

// Search loans
$stmt_loans = $pdo->prepare("
    SELECT l.id, l.amount, l.status, c.name as client_name
    FROM loans l
    JOIN clients c ON l.client_id = c.id
    WHERE l.id LIKE ? OR c.name LIKE ?
    LIMIT 10
");
$stmt_loans->execute([$searchTerm, $searchTerm]);
$loans = $stmt_loans->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'clients' => $clients,
    'loans' => $loans
]);
