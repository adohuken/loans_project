<?php
require 'auth.php';
require 'db.php';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

// Fetch Clients
$clients = $pdo->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll();

// Fetch Settings for Default Interest
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$default_interest = $settings['interest_rate'] ?? 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $amount = $_POST['amount'];
    $interest_rate = $_POST['interest_rate'];
    $payment_frequency = $_POST['payment_frequency'];
    $months = $_POST['months']; // Plazo en meses
    $start_date = $_POST['start_date'];

    // Calculate number of installments based on frequency and months
    if ($payment_frequency == 'daily') {
        $duration = $months * 30; // Aproximadamente 30 días por mes
    } elseif ($payment_frequency == 'weekly') {
        $duration = $months * 4; // 4 semanas por mes
    } elseif ($payment_frequency == 'biweekly') {
        $duration = $months * 2; // 2 quincenas por mes
    } elseif ($payment_frequency == 'monthly') {
        $duration = $months; // 1 pago por mes
    }

    // Calculate Total Amount (Interest is MONTHLY)
    $interest_amount = $amount * ($interest_rate / 100) * $months; // Interés mensual × meses
    $total_amount = $amount + $interest_amount;

    // Calculate Installment Amount
    $installment_amount = $total_amount / $duration;

    // Insert Loan (usando nombres de columnas correctos según database.sql)
    $stmt = $pdo->prepare("INSERT INTO loans (client_id, amount, interest_rate, frequency, duration_months, start_date, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$client_id, $amount, $interest_rate, $payment_frequency, $months, $start_date, $total_amount]);
    $loan_id = $pdo->lastInsertId();

    // Generate Payment Schedule
    $current_date = new DateTime($start_date);

    for ($i = 1; $i <= $duration; $i++) {
        if ($payment_frequency == 'weekly') {
            $current_date->modify('+1 week');
        } elseif ($payment_frequency == 'biweekly') {
            $current_date->modify('+2 weeks');
        } elseif ($payment_frequency == 'monthly') {
            $current_date->modify('+1 month');
        } elseif ($payment_frequency == 'daily') {
            $current_date->modify('+1 day');
        }

        $due_date = $current_date->format('Y-m-d');

        $stmt_payment = $pdo->prepare("INSERT INTO payments (loan_id, due_date, amount_due, status) VALUES (?, ?, ?, 'pending')");
        $stmt_payment->execute([$loan_id, $due_date, $installment_amount]);
    }

    header("Location: active_loans.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Préstamo - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function calculateTotal() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const interest = parseFloat(document.getElementById('interest_rate').value) || 0;
            const months = parseInt(document.getElementById('months').value) || 1;
            const frequency = document.getElementById('payment_frequency').value;

            // Calculate number of installments based on frequency
            let duration = months;
            if (frequency === 'daily') {
                duration = months * 30;
            } else if (frequency === 'weekly') {
                duration = months * 4;
            } else if (frequency === 'biweekly') {
                duration = months * 2;
            } else if (frequency === 'monthly') {
                duration = months;
            }

            // Interest is MONTHLY: amount × (rate/100) × months
            const totalInterest = amount * (interest / 100) * months;
            const total = amount + totalInterest;
            const installment = total / duration;

            document.getElementById('display_total').innerText = total.toFixed(2);
            document.getElementById('display_installment').innerText = installment.toFixed(2);
            document.getElementById('display_installments').innerText = duration;
        }
    </script>
</head>

<body>
    <div class="container">
        <header>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <h1><i class="fas fa-plus-circle"></i> Sistema de Préstamos</h1>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clientes</a>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <a href="create_loan.php" class="active"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield"></i> Usuarios</a>
                <?php endif; ?>
                <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="backup.php"><i class="fas fa-database"></i> Backup</a>
                <?php endif; ?>
                <span
                    style="color: #1a202c; font-weight: 600; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </header>

        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <h2><i class="fas fa-file-contract"></i> Crear Nuevo Préstamo</h2>
            <form method="POST">
                <div class="grid">
                    <div class="form-group">
                        <label>Cliente</label>
                        <select name="client_id" required>
                            <option value="">-- Seleccionar Cliente --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?>
                                    (<?= htmlspecialchars($client['cedula'] ?? 'N/A') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small><a href="clients.php" style="color: var(--primary-solid); text-decoration: none;"><i
                                    class="fas fa-user-plus"></i> ¿Cliente nuevo? Regístralo aquí</a></small>
                    </div>

                    <div class="form-group">
                        <label>Monto a Prestar</label>
                        <input type="number" step="0.01" name="amount" id="amount" required oninput="calculateTotal()">
                    </div>

                    <div class="form-group">
                        <label>Tasa de Interés (% Mensual)</label>
                        <input type="number" step="0.01" name="interest_rate" id="interest_rate"
                            value="<?= $default_interest ?>" required oninput="calculateTotal()">
                        <small style="color: #64748b;">Interés mensual que se multiplica por el plazo.</small>
                    </div>

                    <div class="form-group">
                        <label>Frecuencia de Pago</label>
                        <select name="payment_frequency" id="payment_frequency" required onchange="calculateTotal()">
                            <option value="daily">Diario</option>
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quincenal</option>
                            <option value="monthly">Mensual</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Plazo (Meses)</label>
                        <input type="number" name="months" id="months" value="1" required min="1"
                            oninput="calculateTotal()">
                        <small style="color: #64748b;">Número de cuotas: <strong
                                id="display_installments">1</strong></small>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Inicio</label>
                        <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div
                    style="background: #f0f9ff; padding: 1.5rem; border-radius: 12px; margin: 1.5rem 0; border: 1px solid #bae6fd;">
                    <h3 style="color: #0369a1; margin-bottom: 1rem;"><i class="fas fa-calculator"></i> Resumen del
                        Préstamo</h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Total a Pagar:</span>
                        <strong style="font-size: 1.2rem; color: #0284c7;">$<span
                                id="display_total">0.00</span></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Monto por Cuota:</span>
                        <strong style="font-size: 1.2rem; color: #0284c7;">$<span
                                id="display_installment">0.00</span></strong>
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%;"><i class="fas fa-check-circle"></i> Crear
                    Préstamo</button>
            </form>
        </div>
    </div>
</body>

</html>