<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS rent_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_name VARCHAR(255) NOT NULL,
    tenant_id_number VARCHAR(50),
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    concept TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

try {
    $pdo->exec($sql);
    echo "Table 'rent_receipts' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
unlink(__FILE__); // Delete itself
