<?php
require 'db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            loan_id INT NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            payment_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS transaction_details (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            payment_id INT NOT NULL,
            amount_applied DECIMAL(10, 2) NOT NULL,
            type ENUM('capital', 'late_fee') DEFAULT 'capital',
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
            FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
        );
    ");
    echo "Tablas 'transactions' y 'transaction_details' creadas exitosamente.";
} catch (PDOException $e) {
    die("Error al actualizar la base de datos: " . $e->getMessage());
}
?>