<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $amount = floatval($_POST['amount']);
    $interest_rate = floatval($_POST['interest_rate']);
    $duration_months = intval($_POST['duration_months']);
    $frequency = $_POST['frequency'];
    $start_date = $_POST['start_date'];

    // Calculation Logic
    // Total = Principal + (Principal * (Rate/100) * Months)
    $total_interest = $amount * ($interest_rate / 100) * $duration_months;
    $total_amount = $amount + $total_interest;

    // Insert Loan
    $stmt = $pdo->prepare("INSERT INTO loans (client_id, amount, interest_rate, frequency, duration_months, total_amount, start_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client_id, $amount, $interest_rate, $frequency, $duration_months, $total_amount, $start_date]);
    $loan_id = $pdo->lastInsertId();

    // Generate Schedule
    $num_payments = 0;
    $interval_days = 0;

    if ($frequency === 'weekly') {
        $num_payments = $duration_months * 4;
        $interval_days = 7;
    } elseif ($frequency === 'biweekly') {
        $num_payments = $duration_months * 2;
        $interval_days = 15;
    } else { // monthly
        $num_payments = $duration_months;
        $interval_days = 30;
    }

    $installment_amount = $total_amount / $num_payments;
    $current_date = new DateTime($start_date);

    $stmt_payment = $pdo->prepare("INSERT INTO payments (loan_id, amount_due, due_date) VALUES (?, ?, ?)");

    for ($i = 0; $i < $num_payments; $i++) {
        // Add interval
        if ($i > 0) { // First payment is one interval after start? Or start date? Usually after.
            // Let's assume first payment is 1 period after start date.
            // Actually, let's increment first.
        }
        $current_date->modify("+$interval_days days");

        $stmt_payment->execute([
            $loan_id,
            number_format($installment_amount, 2, '.', ''),
            $current_date->format('Y-m-d')
        ]);
    }

    header("Location: index.php");
    exit;
}
?>