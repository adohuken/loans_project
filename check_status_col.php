<?php
require 'db.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM loans LIKE 'status'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($col);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>