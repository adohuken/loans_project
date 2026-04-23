<?php
require 'auth.php';
require 'db.php';

$user_id = $_SESSION['user_id'] ?? 1;
echo "User ID: $user_id\n";
$rows = $pdo->query("SELECT * FROM user_notification_reads WHERE user_id = $user_id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
?>