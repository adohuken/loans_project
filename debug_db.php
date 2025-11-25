<?php
require 'db.php';

echo "<h1>Database Debug</h1>";

// Check Clients Schema
echo "<h2>Clients Table Schema</h2>";
try {
    $stmt = $pdo->query("PRAGMA table_info(clients)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error checking schema: " . $e->getMessage();
}

// Test Insertion
echo "<h2>Test Insertion</h2>";
try {
    $test_cedula = "TEST-" . time();
    $stmt = $pdo->prepare("INSERT INTO clients (cedula, name, phone, address) VALUES (?, 'Test User', '123', 'Test Address')");
    $stmt->execute([$test_cedula]);
    echo "Insertion successful! Created client with cedula: $test_cedula<br>";

    $id = $pdo->lastInsertId();
    echo "New Client ID: $id<br>";

    // Verify data
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $client = $stmt->fetch();
    echo "<pre>";
    print_r($client);
    echo "</pre>";

    // Cleanup
    $pdo->exec("DELETE FROM clients WHERE id = $id");
    echo "Test client deleted.";

} catch (Exception $e) {
    echo "Insertion Failed: " . $e->getMessage();
}
?>