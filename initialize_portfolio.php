<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

$user_role = $_SESSION['role'] ?? 'admin';

// Only admin and superadmin can access
if ($user_role === 'cobrador') {
    header('Location: active_loans.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $client_id = $_POST['client_id'];
        $amount = floatval($_POST['amount']);
        $interest_rate = floatval($_POST['interest_rate']);
        $payment_frequency = $_POST['payment_frequency'];
        $term_months = intval($_POST['term_months']);
        $start_date = $_POST['start_date'];
        $portfolio_id = $_POST['portfolio_id'] ?? null;

        // Datos de inicialización - SOLO monto total pagado
        $total_paid = floatval($_POST['total_paid'] ?? 0);

        // Calculate total amount using term-based interest
        // Interest = (Amount × Rate × Term in months) / 100
        $interest_amount = ($amount * $interest_rate * $term_months) / 100;
        $total_amount = $amount + $interest_amount;

        // Calculate total installments based on frequency and term
        switch ($payment_frequency) {
            case 'diaria':
                $total_installments = $term_months * 30; // 30 days per month
                break;
            case 'semanal':
                $total_installments = $term_months * 4; // 4 weeks per month
                break;
            case 'quincenal':
                $total_installments = $term_months * 2; // 2 fortnights per month
                break;
            case 'mensual':
                $total_installments = $term_months; // 1 payment per month
                break;
            default:
                $total_installments = $term_months;
        }

        $installment_amount = $total_amount / $total_installments;

        // Calculate how many installments were paid based on total paid
        $paid_installments = ($installment_amount > 0) ? floor($total_paid / $installment_amount) : 0;

        // Calculate remaining
        $remaining_amount = $total_amount - $total_paid;
        $remaining_installments = $total_installments - $paid_installments;

        // Determine status
        $status = ($remaining_amount <= 0) ? 'paid' : 'active';

        // Insert loan
        $stmt = $pdo->prepare("INSERT INTO loans (client_id, amount, interest_rate, total_amount, payment_frequency, 
                              total_installments, installment_amount, start_date, portfolio_id, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $client_id,
            $amount,
            $interest_rate,
            $total_amount,
            $payment_frequency,
            $total_installments,
            $installment_amount,
            $start_date,
            $portfolio_id,
            $status
        ]);

        $loan_id = $pdo->lastInsertId();

        // Generate payment schedule
        $next_payment_date = new DateTime($start_date);

        for ($i = 1; $i <= $total_installments; $i++) {
            // Calculate next payment date based on frequency
            if ($i > 1) {
                switch ($payment_frequency) {
                    case 'diaria':
                        $next_payment_date->modify('+1 day');
                        break;
                    case 'semanal':
                        $next_payment_date->modify('+1 week');
                        break;
                    case 'quincenal':
                        $next_payment_date->modify('+2 weeks');
                        break;
                    case 'mensual':
                        $next_payment_date->modify('+1 month');
                        break;
                }
            }

            $due_date = $next_payment_date->format('Y-m-d');

            // Determine if this installment was already paid
            if ($i <= $paid_installments) {
                // Mark as paid (use estimated dates)
                $paid_date = $due_date;

                $stmt_payment = $pdo->prepare("INSERT INTO payments (loan_id, due_date, amount_due, paid_amount, 
                                               paid_date, status, late_fee, created_at) 
                                               VALUES (?, ?, ?, ?, ?, 'paid', 0, NOW())");

                $stmt_payment->execute([
                    $loan_id,
                    $due_date,
                    $installment_amount,
                    $installment_amount,
                    $paid_date
                ]);
            } else {
                // Mark as pending
                $stmt_payment = $pdo->prepare("INSERT INTO payments (loan_id, due_date, amount_due, status, created_at) 
                                               VALUES (?, ?, ?, 'pending', NOW())");

                $stmt_payment->execute([
                    $loan_id,
                    $due_date,
                    $installment_amount
                ]);
            }
        }

        $success = "Préstamo inicializado exitosamente. Se registraron $paid_installments cuotas como pagadas y $remaining_installments como pendientes.";

    } catch (Exception $e) {
        $error = "Error al inicializar préstamo: " . $e->getMessage();
    }
}

// Fetch clients
$stmt_clients = $pdo->query("SELECT id, name FROM clients ORDER BY name");
$clients = $stmt_clients->fetchAll();

// Fetch portfolios
$stmt_portfolios = $pdo->query("SELECT id, name FROM portfolios ORDER BY name");
$portfolios = $stmt_portfolios->fetchAll();

require 'components/enhanced_header.php';
?>

<style>
    .init-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 2rem;
    }

    .init-card {
        background: var(--bg-primary);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 10px 30px -5px var(--shadow);
        border: 2px solid var(--border-color);
    }

    .init-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--border-color);
    }

    .init-header h2 {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0 0 0.5rem 0;
    }

    .init-header p {
        color: var(--text-secondary);
        font-size: 1rem;
        margin: 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        font-weight: 700;
        color: var(--text-primary);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group input,
    .form-group select {
        padding: 0.875rem;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        font-size: 1rem;
        background: var(--bg-secondary);
        color: var(--text-primary);
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px var(--accent-lighter);
    }

    .info-box {
        background: linear-gradient(135deg, var(--accent-lighter), var(--bg-secondary));
        border-left: 4px solid var(--accent-primary);
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
    }

    .info-box h3 {
        margin: 0 0 0.75rem 0;
        color: var(--accent-primary);
        font-size: 1.1rem;
        font-weight: 800;
    }

    .info-box ul {
        margin: 0;
        padding-left: 1.5rem;
        color: var(--text-secondary);
    }

    .info-box li {
        margin-bottom: 0.5rem;
    }

    .calculated-info {
        background: var(--bg-secondary);
        border-radius: 12px;
        padding: 1.5rem;
        margin: 1.5rem 0;
        border: 2px solid var(--border-color);
    }

    .calculated-info h3 {
        margin: 0 0 1rem 0;
        color: var(--text-primary);
        font-size: 1.1rem;
        font-weight: 800;
    }

    .calc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .calc-item {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .calc-item label {
        display: block;
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    .calc-item .value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--accent-primary);
    }

    .btn-submit {
        width: 100%;
        padding: 1.25rem;
        background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px -2px var(--accent-primary);
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px var(--accent-primary);
    }

    .alert {
        padding: 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
        border: 2px solid #34d399;
    }

    .alert-error {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #991b1b;
        border: 2px solid #f87171;
    }
</style>

<div class="init-container">
    <div class="init-card">
        <div class="init-header">
            <h2><i class="fas fa-file-import"></i> Inicializar Cartera Existente</h2>
            <p>Registra préstamos que ya estaban activos antes de usar el sistema</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> ¿Cómo funciona?</h3>
            <ul>
                <li>Ingresa los datos del préstamo original (monto, interés, plazo en meses, etc.)</li>
                <li>El interés se calcula como: <strong>(Monto × Tasa × Plazo en meses) / 100</strong></li>
                <li>Indica el monto total que ya se ha pagado</li>
                <li>El sistema calculará automáticamente cuántas cuotas están pagadas</li>
                <li>Las cuotas restantes quedarán pendientes para continuar el seguimiento</li>
            </ul>
        </div>

        <form method="POST" id="initForm">
            <h3 style="margin: 0 0 1.5rem 0; color: var(--text-primary); font-weight: 800;">
                <i class="fas fa-user"></i> Datos del Cliente
            </h3>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-user"></i> Cliente
                    </label>
                    <select name="client_id" required>
                        <option value="">Seleccionar cliente...</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-briefcase"></i> Cartera
                    </label>
                    <select name="portfolio_id">
                        <option value="">Sin cartera</option>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?= $portfolio['id'] ?>"><?= htmlspecialchars($portfolio['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3 style="margin: 2rem 0 1.5rem 0; color: var(--text-primary); font-weight: 800;">
                <i class="fas fa-file-invoice-dollar"></i> Datos del Préstamo Original
            </h3>

            <div class="form-grid">
                <div class="form-group">
                    <label>
                        <i class="fas fa-dollar-sign"></i> Monto Prestado
                    </label>
                    <input type="number" name="amount" step="0.01" required id="amount">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-percentage"></i> Tasa de Interés (%)
                    </label>
                    <input type="number" name="interest_rate" step="0.01" required id="interest_rate">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-alt"></i> Plazo (Meses)
                    </label>
                    <input type="number" name="term_months" min="1" required id="term_months">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-check"></i> Fecha de Inicio
                    </label>
                    <input type="date" name="start_date" required>
                </div>

                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-clock"></i> Frecuencia de Pago
                    </label>
                    <select name="payment_frequency" required id="payment_frequency">
                        <option value="diaria">Diaria</option>
                        <option value="semanal">Semanal</option>
                        <option value="quincenal">Quincenal</option>
                        <option value="mensual">Mensual</option>
                    </select>
                </div>
            </div>

            <h3 style="margin: 2rem 0 1.5rem 0; color: var(--text-primary); font-weight: 800;">
                <i class="fas fa-history"></i> Estado Actual del Préstamo
            </h3>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-money-bill-wave"></i> Monto Total Pagado
                    </label>
                    <input type="number" name="total_paid" step="0.01" value="0" required id="total_paid">
                </div>
            </div>

            <div class="calculated-info" id="calculatedInfo" style="display: none;">
                <h3><i class="fas fa-calculator"></i> Información Calculada</h3>
                <div class="calc-grid">
                    <div class="calc-item">
                        <label>Interés Total</label>
                        <div class="value" id="interestAmount">$0.00</div>
                    </div>
                    <div class="calc-item">
                        <label>Total a Pagar</label>
                        <div class="value" id="totalAmount">$0.00</div>
                    </div>
                    <div class="calc-item">
                        <label>Total de Cuotas</label>
                        <div class="value" id="totalInstallments">0</div>
                    </div>
                    <div class="calc-item">
                        <label>Monto por Cuota</label>
                        <div class="value" id="installmentAmount">$0.00</div>
                    </div>
                    <div class="calc-item">
                        <label>Cuotas Pagadas</label>
                        <div class="value" id="paidInstallments">0</div>
                    </div>
                    <div class="calc-item">
                        <label>Saldo Pendiente</label>
                        <div class="value" id="remainingAmount">$0.00</div>
                    </div>
                    <div class="calc-item">
                        <label>Cuotas Restantes</label>
                        <div class="value" id="remainingInstallments">0</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Inicializar Préstamo
            </button>
        </form>
    </div>
</div>

<script>
    // Calculate and display loan information
    function calculateLoanInfo() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const interestRate = parseFloat(document.getElementById('interest_rate').value) || 0;
        const termMonths = parseInt(document.getElementById('term_months').value) || 0;
        const paymentFrequency = document.getElementById('payment_frequency').value;
        const totalPaid = parseFloat(document.getElementById('total_paid').value) || 0;

        if (amount > 0 && interestRate >= 0 && termMonths > 0) {
            // Calculate interest: (Amount × Rate × Term in months) / 100
            const interestAmount = (amount * interestRate * termMonths) / 100;
            const totalAmount = amount + interestAmount;

            // Calculate total installments based on frequency
            let totalInstallments;
            switch (paymentFrequency) {
                case 'diaria':
                    totalInstallments = termMonths * 30;
                    break;
                case 'semanal':
                    totalInstallments = termMonths * 4;
                    break;
                case 'quincenal':
                    totalInstallments = termMonths * 2;
                    break;
                case 'mensual':
                    totalInstallments = termMonths;
                    break;
                default:
                    totalInstallments = termMonths;
            }

            const installmentAmount = totalAmount / totalInstallments;
            const paidInstallments = installmentAmount > 0 ? Math.floor(totalPaid / installmentAmount) : 0;
            const remainingAmount = totalAmount - totalPaid;
            const remainingInstallments = totalInstallments - paidInstallments;

            document.getElementById('interestAmount').textContent = '$' + interestAmount.toFixed(2);
            document.getElementById('totalAmount').textContent = '$' + totalAmount.toFixed(2);
            document.getElementById('totalInstallments').textContent = totalInstallments;
            document.getElementById('installmentAmount').textContent = '$' + installmentAmount.toFixed(2);
            document.getElementById('paidInstallments').textContent = paidInstallments;
            document.getElementById('remainingAmount').textContent = '$' + remainingAmount.toFixed(2);
            document.getElementById('remainingInstallments').textContent = remainingInstallments;
            document.getElementById('calculatedInfo').style.display = 'block';
        } else {
            document.getElementById('calculatedInfo').style.display = 'none';
        }
    }

    // Add event listeners
    document.getElementById('amount').addEventListener('input', calculateLoanInfo);
    document.getElementById('interest_rate').addEventListener('input', calculateLoanInfo);
    document.getElementById('term_months').addEventListener('input', calculateLoanInfo);
    document.getElementById('payment_frequency').addEventListener('change', calculateLoanInfo);
    document.getElementById('total_paid').addEventListener('input', calculateLoanInfo);
</script>

</body>

</html>