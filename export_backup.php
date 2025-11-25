<?php
require 'auth.php';
require 'db.php';

// Set headers for JSON download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.json"');

try {
    // Prepare backup data structure
    $backup = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['username'] ?? 'unknown'
        ],
        'data' => []
    ];

    // Export Settings
    $stmt = $pdo->query("SELECT * FROM settings");
    $backup['data']['settings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Export Users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id");
    $backup['data']['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Export Clients
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY id");
    $backup['data']['clients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Export Loans
    $stmt = $pdo->query("SELECT * FROM loans ORDER BY id");
    $backup['data']['loans'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Export Payments
    $stmt = $pdo->query("SELECT * FROM payments ORDER BY id");
    $backup['data']['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output JSON
    echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // In case of error, output error message
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error al crear el backup: ' . $e->getMessage()
    ]);
}
