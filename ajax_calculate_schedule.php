<?php
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $interest_rate = floatval($_POST['interest_rate']);
    $payment_frequency = $_POST['payment_frequency'];
    $months = intval($_POST['months']);
    $start_date = $_POST['start_date'];

    // Calculate number of installments
    $duration = 0;
    if ($payment_frequency == 'daily') {
        $duration = $months * 30;
    } elseif ($payment_frequency == 'weekly') {
        $duration = $months * 4;
    } elseif ($payment_frequency == 'biweekly') {
        $duration = $months * 2;
    } elseif ($payment_frequency == 'monthly') {
        $duration = $months;
    }

    if ($duration <= 0) {
        echo '<p style="color: red;">Error en el cálculo del plazo.</p>';
        exit;
    }

    // Calculate Total Amount
    $interest_amount = $amount * ($interest_rate / 100) * $months;
    $total_amount = $amount + $interest_amount;
    $installment_amount = $total_amount / $duration;

    // Generate Schedule
    $schedule = [];
    $current_date = new DateTime($start_date);

    for ($i = 1; $i <= $duration; $i++) {
        if ($payment_frequency == 'weekly') {
            $current_date->modify('+1 week');
        } elseif ($payment_frequency == 'biweekly') {
            // Quincenal: 15 y Fin de Mes
            $day = $current_date->format('j');
            $last_day = $current_date->format('t');

            if ($day < 15) {
                // Si es antes del 15, el siguiente es el 15 del mismo mes
                $current_date->setDate($current_date->format('Y'), $current_date->format('m'), 15);
            } elseif ($day < $last_day) {
                // Si es el 15 o después (pero no el último día), el siguiente es el fin de mes
                $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $last_day);
            } else {
                // Si es el último día, el siguiente es el 15 del próximo mes
                $current_date->modify('first day of next month');
                $current_date->setDate($current_date->format('Y'), $current_date->format('m'), 15);
            }
        } elseif ($payment_frequency == 'monthly') {
            $day = $current_date->format('j');
            $current_date->modify('first day of next month');
            $days_in_next_month = $current_date->format('t');
            $target_day = min($day, $days_in_next_month);
            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $target_day);
        } elseif ($payment_frequency == 'daily') {
            // Diario: Lunes a Viernes (saltar fines de semana)
            do {
                $current_date->modify('+1 day');
                $dow = $current_date->format('N'); // 1 (Lunes) - 7 (Domingo)
            } while ($dow >= 6); // Repetir si es Sábado (6) o Domingo (7)
        }

        $schedule[] = [
            'number' => $i,
            'date' => $current_date->format('Y-m-d'),
            'amount' => $installment_amount
        ];
    }

    // Return HTML Table
    echo '<div style="background: var(--bg-primary); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">';
    echo '<h4 style="margin-top: 0; color: var(--text-primary);">Tabla de Amortización Preliminar</h4>';
    echo '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<thead style="background: var(--bg-tertiary); position: sticky; top: 0;">';
    echo '<tr><th style="padding: 0.5rem; text-align: left;">#</th><th style="padding: 0.5rem; text-align: left;">Fecha</th><th style="padding: 0.5rem; text-align: left;">Monto</th></tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($schedule as $payment) {
        echo '<tr style="border-bottom: 1px solid var(--border-color); color: var(--text-primary);">';
        echo '<td style="padding: 0.5rem;">' . $payment['number'] . '</td>';
        echo '<td style="padding: 0.5rem;">' . date('d/m/Y', strtotime($payment['date'])) . '</td>';
        echo '<td style="padding: 0.5rem;">$' . number_format($payment['amount'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
}
?>