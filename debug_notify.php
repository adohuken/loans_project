<?php
require 'auth.php';
require 'db.php';

$user_id = $_SESSION['user_id'] ?? 1;
echo "User ID: $user_id\n";

// 1. Check current unread count (simulate get_notifications)
echo "--- Initial State ---\n";
// (Simplified check)
$count_read = $pdo->query("SELECT COUNT(*) FROM user_notification_reads WHERE user_id = $user_id")->fetchColumn();
echo "Rows in user_notification_reads: $count_read\n";

// 2. Simulate Mark All
echo "--- Executing Mark All ---\n";
$stmt = $pdo->prepare("SELECT p.id FROM payments p WHERE p.status = 'pending' AND p.due_date < CURDATE()");
$stmt->execute();
$overdue = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($overdue) . " overdue payments.\n";

$stmt = $pdo->prepare("INSERT IGNORE INTO user_notification_reads (user_id, entity_type, entity_id) VALUES (?, ?, ?)");
foreach ($overdue as $id) {
    $res = $stmt->execute([$user_id, 'overdue', $id]);
    if (!$res)
        echo "Failed to insert overdue $id\n";
}

// 3. Check DB again
$count_read_after = $pdo->query("SELECT COUNT(*) FROM user_notification_reads WHERE user_id = $user_id")->fetchColumn();
echo "Rows in user_notification_reads after Mark All: $count_read_after\n";

// 4. Verify specific entry
if (count($overdue) > 0) {
    $test_id = $overdue[0];
    $check = $pdo->query("SELECT * FROM user_notification_reads WHERE user_id = $user_id AND entity_type = 'overdue' AND entity_id = $test_id")->fetch();
    echo "Check for overdue ID $test_id: " . ($check ? "FOUND" : "NOT FOUND") . "\n";
}
?>