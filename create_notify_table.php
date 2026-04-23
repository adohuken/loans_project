<?php
require 'db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notification_reads (
        user_id INT, 
        entity_type VARCHAR(50), 
        entity_id INT, 
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        PRIMARY KEY (user_id, entity_type, entity_id)
    )");
    echo "Table created successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
