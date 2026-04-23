<?php
require 'auth.php';
require 'db.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Get notifications for current user
$user_id = $_SESSION['user_id'] ?? 1;

// Get read notifications IDs
$read_ids = [];
$stmt_read = $pdo->prepare("SELECT CONCAT(entity_type, '_', entity_id) as full_id FROM user_notification_reads WHERE user_id = ?");
$stmt_read->execute([$user_id]);
$read_ids = $stmt_read->fetchAll(PDO::FETCH_COLUMN);

file_put_contents('debug_log.txt', "GET: User $user_id has " . count($read_ids) . " read items.\n", FILE_APPEND);

// Check for upcoming payments (next 3 days)
$upcoming_payments = [];
$stmt = $pdo->prepare("
    SELECT p.*, l.id as loan_id, c.name as client_name
    FROM payments p
    JOIN loans l ON p.loan_id = l.id
    JOIN clients c ON l.client_id = c.id
    WHERE p.status = 'pending' 
    AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY p.due_date ASC
    LIMIT 10
");
$stmt->execute();
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($upcoming as $payment) {
    if ($payment['status'] == 'paid')
        continue;

    $days_until = (strtotime($payment['due_date']) - time()) / (60 * 60 * 24);
    $notif_id = 'payment_' . $payment['id'];
    $is_read = in_array($notif_id, $read_ids);

    $upcoming_payments[] = [
        'id' => $notif_id,
        'type' => 'warning',
        'title' => 'Pago Próximo',
        'message' => "{$payment['client_name']} - Pago de $" . number_format($payment['amount_due'], 2) . " vence en " . ceil($days_until) . " día(s)",
        'link' => "loan_details.php?id={$payment['loan_id']}",
        'date' => $payment['due_date'],
        'read' => $is_read,
        'original_id' => $payment['id'],
        'entity_type' => 'payment'
    ];
}

// Check for overdue payments
$overdue_payments = [];
$stmt = $pdo->prepare("
    SELECT p.*, l.id as loan_id, c.name as client_name
    FROM payments p
    JOIN loans l ON p.loan_id = l.id
    JOIN clients c ON l.client_id = c.id
    WHERE p.status = 'pending' 
    AND p.due_date < CURDATE()
    ORDER BY p.due_date ASC
    LIMIT 10
");
$stmt->execute();
$overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($overdue as $payment) {
    $days_overdue = (time() - strtotime($payment['due_date'])) / (60 * 60 * 24);
    $notif_id = 'overdue_' . $payment['id'];
    $is_read = in_array($notif_id, $read_ids);

    $overdue_payments[] = [
        'id' => $notif_id,
        'type' => 'error',
        'title' => 'Pago Vencido',
        'message' => "{$payment['client_name']} - Pago de $" . number_format($payment['amount_due'], 2) . " vencido hace " . floor($days_overdue) . " día(s)",
        'link' => "loan_details.php?id={$payment['loan_id']}",
        'date' => $payment['due_date'],
        'read' => $is_read,
        'original_id' => $payment['id'],
        'entity_type' => 'overdue'
    ];
}

// Combine all notifications
$notifications = array_merge($overdue_payments, $upcoming_payments);

// Count unread
$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['read']) {
        $unread_count++;
    }
}

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
