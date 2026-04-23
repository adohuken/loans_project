<?php
require 'db.php';

try {
    $pdo->exec("ALTER TABLE loans MODIFY COLUMN status ENUM('active', 'paid', 'cancelled') DEFAULT 'active'");
    echo "Database schema updated successfully.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>