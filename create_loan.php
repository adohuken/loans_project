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

        // Calculate Installment Amount — rounded to 2 decimals
        $installment_amount = round($total_amount / $duration, 2);

        // Calculate rounding difference to add to the last payment
        $sum_of_installments = $installment_amount * $duration;
        $rounding_diff = round($total_amount - $sum_of_installments, 2);

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

            // Last payment absorbs the rounding difference so SUM(amount_due) == total_amount exactly
            $cuota = ($i === $duration) ? round($installment_amount + $rounding_diff, 2) : $installment_amount;

            $stmt_payment = $pdo->prepare("INSERT INTO payments (loan_id, due_date, amount_due, status) VALUES (?, ?, ?, 'pending')");
            $stmt_payment->execute([$loan_id, $due_date, $cuota]);
        }

        $pdo->commit();
        header("Location: loan_details.php?id=$loan_id");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al crear el préstamo: " . $e->getMessage());
    }
}

// Include enhanced header
require 'components/enhanced_header.php';
?>

<style>
    body {
        background-color: var(--bg-secondary);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--text-primary);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Cards */
    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        padding: 2rem;
    }

    .card h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Forms */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-secondary);
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        transition: all 0.2s;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-help {
        display: block;
        margin-top: 0.5rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    /* Summary Card */
    .summary-card {
        background: var(--secondary-surface);
        padding: 2rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        position: sticky;
        top: 2rem;
    }

    .summary-title {
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        font-size: 1.25rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .summary-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .summary-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .summary-value {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-primary);
    }

    .total-value {
        color: var(--primary-color);
        font-size: 1.5rem;
    }

    .btn-submit {
        width: 100%;
        padding: 1rem;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        margin-top: 2rem;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
    }

    .btn-submit:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .btn-preview {
        width: 100%;
        padding: 0.75rem;
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        transition: all 0.2s;
    }

    .btn-preview:hover {
        background: #f1f5f9;
        color: var(--text-primary);
    }
</style>

<div class="container">
    <form method="POST">
        <div class="main-grid">

            <!-- Left Column: Form Inputs -->
            <div class="card">
                <h2><i class="fas fa-file-contract" style="color: var(--primary-color);"></i> Crear Nuevo Préstamo</h2>

                <div class="form-group">
                    <label>Cliente</label>
                    <select name="client_id" class="form-control" required>
                        <option value="">-- Seleccionar Cliente --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?>
                                (<?= htmlspecialchars($client['cedula'] ?? 'N/A') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-help">
                        <a href="clients.php"
                            style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                            <i class="fas fa-user-plus"></i> ¿Cliente nuevo? Regístralo aquí
                        </a>
                    </small>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Monto a Prestar</label>
                        <div style="position: relative;">
                            <span
                                style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-weight: 600;">$</span>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control"
                                style="padding-left: 2.5rem;" required oninput="calculateTotal()" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tasa de Interés (% Mensual)</label>
                        <div style="position: relative;">
                            <input type="number" step="0.01" name="interest_rate" id="interest_rate"
                                class="form-control" required oninput="calculateTotal()" placeholder="Ej: 15"
                                value="<?= $default_interest ?>">
                            <span
                                style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-weight: 600;">%</span>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Frecuencia de Pago</label>
                        <select name="payment_frequency" id="payment_frequency" class="form-control" required
                            onchange="calculateTotal()">
                            <option value="daily">Diario</option>
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quincenal</option>
                            <option value="monthly">Mensual</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Plazo (Meses)</label>
                        <input type="number" name="months" id="months" class="form-control" required min="1"
                            oninput="calculateTotal()" placeholder="Ej: 12">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha de Inicio</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Cartera (Opcional)</label>
                        <select name="portfolio_id" class="form-control">
                            <option value="">-- Sin Cartera --</option>
                            <?php foreach ($portfolios as $portfolio): ?>
                                <option value="<?= $portfolio['id'] ?>"><?= htmlspecialchars($portfolio['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Right Column: Summary Widget -->
            <div class="card summary-card">
                <h3 class="summary-title"><i class="fas fa-calculator" style="color: var(--primary-color);"></i> Resumen
                </h3>

                <div class="summary-row">
                    <span class="summary-label">Monto por Cuota</span>
                    <span class="summary-value" style="color: var(--primary-color);">$<span
                            id="display_installment">0.00</span></span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Nº de Cuotas</span>
                    <span class="summary-value"><span id="display_installments">0</span></span>
                </div>

                <div style="margin: 1.5rem 0; border-top: 2px dashed var(--border-color);"></div>

                <div style="text-align: center;">
                    <span class="summary-label" style="display: block; margin-bottom: 0.5rem;">Costo Total del
                        Crédito</span>
                    <span class="summary-value total-value">$<span id="display_total">0.00</span></span>
                </div>

                <button type="button" class="btn-preview" onclick="previewSchedule()">
                    <i class="fas fa-table"></i> Ver Tabla de Pagos
                </button>

                <div id="schedule_preview" style="margin-top: 1rem;"></div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-check-circle"></i> Aprobar Préstamo
                </button>
            </div>

        </div>
    </form>
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
        const installment = total / duration; // Division by zero protected by duration || 1 logic if months=0, but inputs are text

        document.getElementById('display_total').innerText = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('display_installment').innerText = installment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
            const previewContainer = document.getElementById('schedule_preview');
            previewContainer.innerHTML = `
                <div style="background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 8px; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                    <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
                    <span>Completa todos los campos para ver la tabla.</span>
                </div>
            `;
            // Auto hide after 3 seconds
            setTimeout(() => {
                if (previewContainer.innerHTML.includes('Completa todos')) {
                    previewContainer.innerHTML = '';
                }
            }, 3000);
            return;
        }

        const formData = new FormData();
        formData.append('amount', amount);
        formData.append('interest_rate', interest_rate);
        formData.append('months', months);
        formData.append('payment_frequency', payment_frequency);
        formData.append('start_date', start_date);

        document.getElementById('schedule_preview').innerHTML = '<p style="text-align:center; color: #64748b; font-size: 0.9rem; margin-top: 1rem;"><i class="fas fa-spinner fa-spin"></i> Calculando...</p>';

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
                document.getElementById('schedule_preview').innerHTML = '<p style="color: red; text-align: center; font-size: 0.9rem;">Error al cargar.</p>';
            });
    }
</script>
</body>

</html>