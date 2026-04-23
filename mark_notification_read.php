<?php
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['id'] ?? null;
$mark_all = $data['all'] ?? false;
$user_id = $_SESSION['user_id'] ?? 1;

try {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Called with: " . print_r($data, true) . "\n", FILE_APPEND);

    if ($mark_all) {
        $stmt = $pdo->prepare("SELECT p.id FROM payments p WHERE p.status = 'pending' AND p.due_date < CURDATE()");
        $stmt->execute();
        $overdue = $stmt->fetchAll(PDO::FETCH_COLUMN);

        file_put_contents('debug_log.txt', "Found overdue: " . count($overdue) . "\n", FILE_APPEND);

        $stmt = $pdo->prepare("SELECT p.id FROM payments p WHERE p.status = 'pending' AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
        $stmt->execute();
        $upcoming = $stmt->fetchAll(PDO::FETCH_COLUMN);

        file_put_contents('debug_log.txt', "Found upcoming: " . count($upcoming) . "\n", FILE_APPEND);

        $stmt_insert = $pdo->prepare("INSERT IGNORE INTO user_notification_reads (user_id, entity_type, entity_id) VALUES (?, ?, ?)");

        foreach ($overdue as $id) {
            $stmt_insert->execute([$user_id, 'overdue', $id]);
        }
        foreach ($upcoming as $id) {
            $stmt_insert->execute([$user_id, 'payment', $id]);
        }

        echo json_encode(['success' => true, 'message' => 'All marked as read']);

    } elseif ($notification_id) {
        // ... (existing single mark logic)
        $parts = explode('_', $notification_id);
        if (count($parts) == 2) {
            $type = $parts[0];
            $id = $parts[1];

            $stmt = $pdo->prepare("INSERT IGNORE INTO user_notification_reads (user_id, entity_type, entity_id) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $type, $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID format']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    }
} catch (Exception $e) {
    file_put_contents('debug_log.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
