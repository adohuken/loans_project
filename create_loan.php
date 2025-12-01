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

// Fetch Portfolios
$portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC")->fetchAll();

// Fetch Settings for Default Interest
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$default_interest = $settings['interest_rate'] ?? 15;
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $amount = $_POST['amount'];
    $interest_rate = $_POST['interest_rate'];
    $payment_frequency = $_POST['payment_frequency'];
    $months = $_POST['months']; // Plazo en meses
    $start_date = $_POST['start_date'];
    $portfolio_id = !empty($_POST['portfolio_id']) ? $_POST['portfolio_id'] : null;

    try {
        $pdo->beginTransaction();

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

        // Insert Loan
        $stmt = $pdo->prepare("INSERT INTO loans (client_id, amount, interest_rate, frequency, duration_months, start_date, total_amount, portfolio_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$client_id, $amount, $interest_rate, $payment_frequency, $months, $start_date, $total_amount, $portfolio_id]);
        $loan_id = $pdo->lastInsertId();

        // Generate Payment Schedule
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
                // Handle end of month issues (e.g. Jan 31 + 1 month -> Feb 28/29)
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

            $due_date = $current_date->format('Y-m-d');

            $stmt_payment = $pdo->prepare("INSERT INTO payments (loan_id, due_date, amount_due, status) VALUES (?, ?, ?, 'pending')");
            $stmt_payment->execute([$loan_id, $due_date, $installment_amount]);
        }

        $pdo->commit();
        header("Location: active_loans.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al crear el préstamo: " . $e->getMessage());
    }
}

// Include enhanced header
require 'components/enhanced_header.php';
?>

<div class="container">
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
                    <label>Cartera</label>
                    <select name="portfolio_id">
                        <option value="">-- Sin Cartera --</option>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?= $portfolio['id'] ?>"><?= htmlspecialchars($portfolio['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Monto a Prestar</label>
                    <input type="number" step="0.01" name="amount" id="amount" required oninput="calculateTotal()">
                </div>

                <div class="form-group">
                    <label>Tasa de Interés (% Mensual)</label>
                    <input type="number" step="0.01" name="interest_rate" id="interest_rate" required
                        oninput="calculateTotal()">
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
                    <input type="number" name="months" id="months" required min="1" oninput="calculateTotal()">
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
                    <strong style="font-size: 1.2rem; color: #0284c7;">$<span id="display_total">0.00</span></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Monto por Cuota:</span>
                    <strong style="font-size: 1.2rem; color: #0284c7;">$<span
                            id="display_installment">0.00</span></strong>
                </div>

                <!-- Preview Button -->
                <button type="button" class="btn btn-sm btn-secondary" style="margin-top: 1rem; width: 100%;"
                    onclick="previewSchedule()">
                    <i class="fas fa-list-ol"></i> Ver Tabla de Amortización
                </button>

                <!-- Schedule Container -->
                <div id="schedule_preview" style="margin-top: 1rem;"></div>
            </div>

            <button type="submit" class="btn" style="width: 100%;"><i class="fas fa-check-circle"></i> Crear
                Préstamo</button>
        </form>
    </div>
</div>

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

        // Clear preview when parameters change
        document.getElementById('schedule_preview').innerHTML = '';
    }

    function previewSchedule() {
        const amount = document.getElementById('amount').value;
        const interest_rate = document.getElementById('interest_rate').value;
        const months = document.getElementById('months').value;
        const payment_frequency = document.getElementById('payment_frequency').value;
        const start_date = document.querySelector('input[name="start_date"]').value;

        if (!amount || !interest_rate || !months || !start_date) {
            alert("Por favor complete todos los campos para ver la tabla.");
            return;
        }

        const formData = new FormData();
        formData.append('amount', amount);
        formData.append('interest_rate', interest_rate);
        formData.append('months', months);
        formData.append('payment_frequency', payment_frequency);
        formData.append('start_date', start_date);

        document.getElementById('schedule_preview').innerHTML = '<p style="text-align:center; color: #64748b;">Calculando...</p>';

        fetch('ajax_calculate_schedule.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(html => {
                document.getElementById('schedule_preview').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('schedule_preview').innerHTML = '<p style="color: red;">Error al cargar la tabla.</p>';
            });
    }
</script>
</body>

</html>