<?php
require 'auth.php';
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: active_loans.php");
    exit;
}

$loan_id = $_GET['id'];

// Fetch Loan Details
$stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die("Préstamo no encontrado.");
}

// Check if user is cobrador (cobradores shouldn't edit loans usually, but let's check role)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    die("Acceso denegado.");
}

// Check for existing payments
$stmt_payments = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE loan_id = ? AND status = 'paid'");
$stmt_payments->execute([$loan_id]);
$paid_payments_count = $stmt_payments->fetchColumn();

// Fetch Clients
$clients = $pdo->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll();

// Fetch Portfolios
$portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC")->fetchAll();

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

// Initial check for existing payments to decide default behavior
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE loan_id = ? AND status != 'pending'"); // Count paid or partial
$stmt_check->execute([$loan_id]);
$has_activity = $stmt_check->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $amount = $_POST['amount'];
    $interest_rate = $_POST['interest_rate'];
    $payment_frequency = $_POST['payment_frequency'];
    $months = $_POST['months'];
    $start_date = $_POST['start_date'];
    $portfolio_id = !empty($_POST['portfolio_id']) ? $_POST['portfolio_id'] : null;

    // Checkbox availability
    $regenerate_schedule = isset($_POST['regenerate_schedule']);

    try {
        $pdo->beginTransaction();

        // Calculate number of installments
        if ($payment_frequency == 'daily') {
            $duration = $months * 30;
        } elseif ($payment_frequency == 'weekly') {
            $duration = $months * 4;
        } elseif ($payment_frequency == 'biweekly') {
            $duration = $months * 2;
        } elseif ($payment_frequency == 'monthly') {
            $duration = $months;
        }

        // Calculate Total Amount
        $interest_amount = $amount * ($interest_rate / 100) * $months;
        $total_amount = $amount + $interest_amount;
        $installment_amount = $total_amount / $duration; // Used only if regenerating

        // Update Loan
        $stmt = $pdo->prepare("UPDATE loans SET client_id = ?, amount = ?, interest_rate = ?, frequency = ?, duration_months = ?, start_date = ?, total_amount = ?, portfolio_id = ? WHERE id = ?");
        $stmt->execute([$client_id, $amount, $interest_rate, $payment_frequency, $months, $start_date, $total_amount, $portfolio_id, $loan_id]);

        // Only regenerate if requested OR if there's no activity (safe to regenerate) and user didn't explicitly opt-out (though UI logic handles this)
        // Ideally trust the checkbox.

        if ($regenerate_schedule) {
            // Delete existing payments (Reset schedule)
            $pdo->prepare("DELETE FROM payments WHERE loan_id = ?")->execute([$loan_id]);

            // Regenerate Payment Schedule
            $current_date = new DateTime($start_date);

            for ($i = 1; $i <= $duration; $i++) {
                if ($payment_frequency == 'weekly') {
                    $current_date->modify('+1 week');
                } elseif ($payment_frequency == 'biweekly') {
                    $day = $current_date->format('j');
                    $last_day = $current_date->format('t');
                    if ($day < 15) {
                        $current_date->setDate($current_date->format('Y'), $current_date->format('m'), 15);
                    } elseif ($day < $last_day) {
                        $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $last_day);
                    } else {
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
                    do {
                        $current_date->modify('+1 day');
                        $dow = $current_date->format('N');
                    } while ($dow >= 6);
                }

                $due_date = $current_date->format('Y-m-d');
                $stmt_payment = $pdo->prepare("INSERT INTO payments (loan_id, due_date, amount_due, status) VALUES (?, ?, ?, 'pending')");
                $stmt_payment->execute([$loan_id, $due_date, $installment_amount]);
            }

            $msg = "Préstamo actualizado y calendario regenerado.";
        } else {
            $msg = "Préstamo actualizado. El calendario de pagos se mantuvo intacto.";
        }

        $pdo->commit();
        header("Location: loan_details.php?id=$loan_id&msg=" . urlencode($msg));
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al actualizar el préstamo: " . $e->getMessage());
    }
}

require 'components/enhanced_header.php';
?>

<div class="container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2><i class="fas fa-edit"></i> Editar Préstamo #<?= $loan_id ?></h2>
            <a href="loan_details.php?id=<?= $loan_id ?>" class="btn btn-secondary btn-sm"><i
                    class="fas fa-arrow-left"></i> Volver</a>
        </div>

        <?php if ($paid_payments_count > 0): ?>
            <div
                style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem;">
                    <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-top: 0.25rem;"></i>
                    <div>
                        <strong>Modo de Edición Segura</strong>
                        <p style="margin: 0.5rem 0 0; font-size: 0.95rem;">
                            Este préstamo tiene pagos registrados. Por defecto, solo se actualizarán los datos informativos.
                            El calendario de pagos NO se tocará a menos que marques la casilla de abajo.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirmEdit()">
            <div class="grid">
                <div class="form-group">
                    <label>Cliente</label>
                    <select name="client_id" required>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= $client['id'] == $loan['client_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?>
                                (<?= htmlspecialchars($client['cedula'] ?? 'N/A') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cartera</label>
                    <select name="portfolio_id">
                        <option value="">-- Sin Cartera --</option>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?= $portfolio['id'] ?>" <?= $portfolio['id'] == $loan['portfolio_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($portfolio['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Monto a Prestar</label>
                    <input type="number" step="0.01" name="amount" id="amount" value="<?= $loan['amount'] ?>" required
                        oninput="calculateTotal()">
                </div>

                <div class="form-group">
                    <label>Tasa de Interés (% Mensual)</label>
                    <input type="number" step="0.01" name="interest_rate" id="interest_rate"
                        value="<?= $loan['interest_rate'] ?>" required oninput="calculateTotal()">
                </div>

                <div class="form-group">
                    <label>Frecuencia de Pago</label>
                    <select name="payment_frequency" id="payment_frequency" required onchange="calculateTotal()">
                        <option value="daily" <?= $loan['frequency'] == 'daily' ? 'selected' : '' ?>>Diario</option>
                        <option value="weekly" <?= $loan['frequency'] == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                        <option value="biweekly" <?= $loan['frequency'] == 'biweekly' ? 'selected' : '' ?>>Quincenal
                        </option>
                        <option value="monthly" <?= $loan['frequency'] == 'monthly' ? 'selected' : '' ?>>Mensual</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Plazo (Meses)</label>
                    <input type="number" name="months" id="months" value="<?= $loan['duration_months'] ?>" required
                        min="1" oninput="calculateTotal()">
                    <small style="color: #64748b;">Número de cuotas: <strong
                            id="display_installments">1</strong></small>
                </div>

                <div class="form-group">
                    <label>Fecha de Inicio</label>
                    <input type="date" name="start_date" value="<?= $loan['start_date'] ?>" required>
                </div>
            </div>

            <div
                style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; border: 1px solid #e2e8f0;">
                <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-start; gap: 0.75rem;">
                    <div style="padding-top: 0.25rem;">
                        <input type="checkbox" name="regenerate_schedule" id="regenerate_schedule"
                            style="width: 20px; height: 20px;" <?= $paid_payments_count == 0 ? 'checked' : '' ?>>
                    </div>
                    <div>
                        <label for="regenerate_schedule" style="margin-bottom: 0.25rem; color: #1e293b;">Regenerar
                            Calendario de Pagos</label>
                        <p style="font-size: 0.85rem; color: #64748b; margin: 0;">
                            Si marcas esto, <strong>se borrarán todos los pagos actuales</strong> y se creará un nuevo
                            calendario basado en los nuevos parámetros.
                            <?php if ($paid_payments_count > 0): ?>
                                <br><span style="color: #ef4444;">¡Cuidado! Ya tienes pagos registrados. Solo marca esto si
                                    realmente quieres reiniciar el préstamo.</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div
                style="background: #f0f9ff; padding: 1.5rem; border-radius: 12px; margin: 1.5rem 0; border: 1px solid #bae6fd;">
                <h3 style="color: #0369a1; margin-bottom: 1rem;"><i class="fas fa-calculator"></i> Nuevo Resumen</h3>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Total a Pagar:</span>
                    <strong style="font-size: 1.2rem; color: #0284c7;">$<span id="display_total">0.00</span></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Monto por Cuota:</span>
                    <strong style="font-size: 1.2rem; color: #0284c7;">$<span
                            id="display_installment">0.00</span></strong>
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%;"><i class="fas fa-save"></i> Guardar Cambios</button>
        </form>
    </div>
</div>

<script>
    function calculateTotal() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const interest = parseFloat(document.getElementById('interest_rate').value) || 0;
        const months = parseInt(document.getElementById('months').value) || 1;
        const frequency = document.getElementById('payment_frequency').value;

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

        const totalInterest = amount * (interest / 100) * months;
        const total = amount + totalInterest;
        const installment = total / duration;

        document.getElementById('display_total').innerText = total.toFixed(2);
        document.getElementById('display_installment').innerText = installment.toFixed(2);
        document.getElementById('display_installments').innerText = duration;
    }

    function confirmEdit() {
        const checkbox = document.getElementById('regenerate_schedule');
        const hasPaidPayments = <?= $paid_payments_count > 0 ? 'true' : 'false' ?>;

        if (checkbox.checked && hasPaidPayments) {
            return confirm("¡ATENCIÓN!\n\nHas elegido REGENERAR el calendario en un préstamo con pagos existentes.\n\nEsto ELIMINARÁ TODOS LOS REGISTROS DE PAGO.\n\n¿Estás seguro de continuar?");
        }
        return true;
    }

    // Initial calculation
    calculateTotal();
</script>
</body>

</html>