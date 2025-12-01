<?php
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

// Get notifications for current user
$user_id = $_SESSION['user_id'] ?? 1;

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
    $days_until = (strtotime($payment['due_date']) - time()) / (60 * 60 * 24);
    $upcoming_payments[] = [
        'id' => 'payment_' . $payment['id'],
        'type' => 'warning',
        'title' => 'Pago Próximo',
        'message' => "{$payment['client_name']} - Pago de $" . number_format($payment['amount_due'], 2) . " vence en " . ceil($days_until) . " día(s)",
        'link' => "loan_details.php?id={$payment['loan_id']}",
        'date' => $payment['due_date'],
        'read' => false
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
    $overdue_payments[] = [
        'id' => 'overdue_' . $payment['id'],
        'type' => 'error',
        'title' => 'Pago Vencido',
        'message' => "{$payment['client_name']} - Pago de $" . number_format($payment['amount_due'], 2) . " vencido hace " . floor($days_overdue) . " día(s)",
        'link' => "loan_details.php?id={$payment['loan_id']}",
        'date' => $payment['due_date'],
        'read' => false
    ];
}

// Combine all notifications
$notifications = array_merge($overdue_payments, $upcoming_payments);

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => count($notifications)
]);
