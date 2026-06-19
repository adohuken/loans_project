<?php
require 'auth.php';
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: active_loans.php");
    exit;
}

$loan_id = $_GET['id'];
$user_role = $_SESSION['role'] ?? 'admin';
$user_portfolio_id = $_SESSION['portfolio_id'] ?? null;

// Fetch Loan Details
$stmt = $pdo->prepare("
    SELECT l.*, c.name, c.cedula, c.address, c.phone, p.name as portfolio_name, c.portfolio_id
    FROM loans l 
    JOIN clients c ON l.client_id = c.id 
    LEFT JOIN portfolios p ON c.portfolio_id = p.id
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die("Préstamo no encontrado.");
}

// Security Check for Cobrador
if ($user_role === 'cobrador') {
    if ($loan['portfolio_id'] != $user_portfolio_id) {
        die("Acceso denegado: Este préstamo no pertenece a tu cartera asignada.");
    }
}

// Fetch Payments
$stmt_payments = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY due_date ASC");
$stmt_payments->execute([$loan_id]);
$payments = $stmt_payments->fetchAll();

// Fetch Transactions (Receipts History)
$stmt_transactions = $pdo->prepare("SELECT * FROM transactions WHERE loan_id = ? ORDER BY payment_date DESC");
$stmt_transactions->execute([$loan_id]);
$transactions = $stmt_transactions->fetchAll();

// Identify the last entered payment (highest ID)
$last_entered_payment_id = 0;
foreach ($payments as $p) {
    if ($p['id'] > $last_entered_payment_id) {
        $last_entered_payment_id = $p['id'];
    }
}

// Calculate Progress & Detailed Metrics
$total_paid = 0;
$total_installments = count($payments);
$paid_installments = 0;
$pending_installments = 0;
$late_installments = 0;
$total_late_fees_loan = 0;
$total_paid_late_fees_loan = 0;

$next_payment = null;
$today_str = date('Y-m-d');

foreach ($payments as $index => $p) {
    $total_paid += $p['paid_amount'];
    
    // Late fees accumulated
    $total_late_fees_loan += $p['late_fee'];
    $total_paid_late_fees_loan += $p['paid_late_fee'];

    // Installment count states
    if ($p['status'] == 'paid') {
        $paid_installments++;
    } else {
        $pending_installments++;
        
        // Overdue status check
        if (strtotime($p['due_date']) < strtotime($today_str)) {
            $late_installments++;
        }
        
        // Find next payment (first unpaid or partially paid)
        if ($next_payment === null) {
            $next_payment = [
                'num' => $index + 1,
                'due_date' => $p['due_date'],
                'amount_due' => $p['amount_due'],
                'paid_amount' => $p['paid_amount'],
                'remaining' => max(0, $p['amount_due'] - $p['paid_amount'] + ($p['late_fee'] - $p['paid_late_fee'])),
                'is_late' => (strtotime($p['due_date']) < strtotime($today_str))
            ];
        }
    }
}
$progress = ($loan['total_amount'] > 0) ? ($total_paid / $loan['total_amount']) * 100 : 0;
$interest_total = max(0, $loan['total_amount'] - $loan['amount']);

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

// Include enhanced header
require 'components/enhanced_header.php';
?>

<style>
    /* Premium Design Overrides */
    .loan-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .client-profile {
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }

    .client-avatar {
        width: 80px;
        height: 80px;
        background: var(--accent-light);
        color: var(--accent-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        box-shadow: var(--shadow);
    }

    .client-info h1 {
        font-size: 1.8rem;
        margin: 0;
        color: var(--text-primary);
    }

    .client-info p {
        color: var(--text-secondary);
        margin: 0.25rem 0 0 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .loan-actions {
        display: flex;
        gap: 0.5rem;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .stat-item {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        transition: all 0.2s;
    }

    .stat-item:hover {
        transform: translateY(-2px);
        background: var(--bg-secondary);
        box-shadow: var(--shadow);
    }

    .stat-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .transaction-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .transaction-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        background: var(--bg-secondary);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        transition: all 0.2s;
    }

    .transaction-item:hover {
        border-color: var(--accent-primary);
        box-shadow: var(--shadow);
    }

    .t-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
        border: 1px solid #86efac;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-right: 1rem;
        box-shadow: 0 4px 6px -1px rgba(22, 101, 52, 0.1);
    }

    .t-info {
        flex: 1;
    }

    .t-date {
        font-weight: 600;
        color: #334155;
    }

    .t-meta {
        font-size: 0.85rem;
        color: #64748b;
    }

    .t-amount {
        font-weight: 700;
        color: #10b981;
        font-size: 1.1rem;
        text-align: right;
    }

    .progress-track {
        background: #e2e8f0;
        height: 12px;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 1rem;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        border-radius: 10px;
        transition: width 1s ease-in-out;
    }

    /* Override standard table for cleaner look */
    .clean-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .clean-table th {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        font-weight: 600;
        padding: 1rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border-color);
    }

    .clean-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
        color: var(--text-primary);
    }

    .clean-table tr:last-child td {
        border-bottom: none;
    }

    .clean-table tr:hover {
        background-color: #f8fafc;
    }

    .btn-secondary.btn-icon {
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .btn-secondary.btn-icon:hover {
        border-color: #cbd5e1;
        color: #334155;
        background: #f8fafc;
    }

    .btn-icon.danger {
        color: #ef4444;
        border-color: #fee2e2;
        background: #fef2f2;
    }

    .btn-icon.danger:hover {
        background: #fee2e2;
        border-color: #fca5a5;
    }

    /* Enriched Financial Summary Styles */
    .financial-divider {
        border: 0;
        height: 1px;
        background: var(--border-color);
        margin: 2rem 0;
    }

    .financial-details-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
        margin-top: 1.5rem;
    }

    @media (min-width: 768px) {
        .financial-details-grid {
            grid-template-columns: 1.2fr 0.8fr;
        }
    }

    .details-list {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .details-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.65rem;
        border-bottom: 1px dashed var(--border-color);
        font-size: 0.95rem;
    }

    .details-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .details-label {
        color: var(--text-secondary);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .details-value {
        font-weight: 700;
        color: var(--text-primary);
    }

    .next-payment-card {
        background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .next-payment-card.overdue {
        background: linear-gradient(135deg, #fffbef 0%, #fef3c7 100%);
        border-color: #f59e0b;
        box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1);
    }

    .next-payment-header {
        font-size: 0.8rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.05em;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-secondary);
    }

    .next-payment-card.overdue .next-payment-header {
        color: #b45309;
    }

    .next-payment-amount {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
        margin-bottom: 0.75rem;
    }

    .next-payment-card.overdue .next-payment-amount {
        color: #b45309;
    }

    .next-payment-meta {
        font-size: 0.85rem;
        color: var(--text-secondary);
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        border-top: 1px solid var(--border-color);
        padding-top: 0.75rem;
    }

    .next-payment-card.overdue .next-payment-meta {
        border-top-color: #fde68a;
        color: #78350f;
    }

    .badge-alert {
        background: #fee2e2;
        color: #ef4444;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        width: fit-content;
        margin-top: 0.5rem;
    }
</style>

<?php
// Verification of Total Balance vs Payments Sum
$calculated_total_due = 0;
foreach ($payments as $p) {
    $calculated_total_due += $p['amount_due'];
}

// Logic to detect mismatch (tolerance of 0.05 for decimals)
$has_mismatch = abs($loan['total_amount'] - $calculated_total_due) > 0.05;
?>

<div class="container">
    <?php if (isset($_GET['msg'])): ?>
        <div
            style="background-color: #dcfce7; border: 1px solid #bbf7d0; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
            <div>
                <strong>¡Operación Exitosa!</strong>
                <p style="margin: 0; font-size: 0.95rem;"><?= htmlspecialchars(urldecode($_GET['msg'])) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div
            style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-exclamation-circle" style="font-size: 1.25rem;"></i>
            <div>
                <strong>¡Ocurrió un Error!</strong>
                <p style="margin: 0; font-size: 0.95rem;"><?= htmlspecialchars(urldecode($_GET['error'])) ?></p>
            </div>
        </div>
    <?php endif; ?>
    <div class="loan-header">
        <div class="client-profile">
            <div class="client-avatar">
                <?= strtoupper(substr($loan['name'], 0, 1)) ?>
            </div>
            <div class="client-info">
                <h1><?= htmlspecialchars($loan['name']) ?></h1>
                <p>
                    <i class="fas fa-id-card"></i> <?= htmlspecialchars($loan['cedula'] ?? 'N/A') ?> &bull;
                    <i class="fas fa-folder"></i> <?= htmlspecialchars($loan['portfolio_name'] ?? 'General') ?>
                </p>
                <div style="margin-top: 0.5rem;">
                    <?php if ($loan['status'] == 'active'): ?>
                        <span class="badge" style="background:#dbeafe; color:#1e40af;">Activo</span>
                    <?php elseif ($loan['status'] == 'paid'): ?>
                        <span class="badge" style="background:#dcfce7; color:#166534;">Pagado</span>
                    <?php else: ?>
                        <span class="badge" style="background:#f3f4f6; color:#1f2937;">Cancelado</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="loan-actions">
            <a href="process_payment.php?loan_id=<?= $loan['id'] ?>" class="btn"
                style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);">
                <i class="fas fa-plus-circle"></i> Registrar Abono
            </a>
            <a href="edit_loan.php?id=<?= $loan['id'] ?>" class="btn-secondary btn-icon"
                title="Editar condiciones y datos del préstamo" style="width: auto; padding: 0.75rem;">
                <i class="fas fa-cog"></i>
            </a>
            <a href="print_payment_plan.php?loan_id=<?= $loan['id'] ?>" target="_blank" class="btn-secondary btn-icon"
                title="Imprimir tabla de amortización y plan de pagos" style="width: auto; padding: 0.75rem;">
                <i class="fas fa-print"></i>
            </a>
            <?php if ($user_role !== 'cobrador'): ?>
                <form action="cancel_loan.php" method="POST" onsubmit="return confirm('¿ELIMINAR PRÉSTAMO?');"
                    style="display:inline;">
                    <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                    <button type="submit" class="btn-icon danger"
                        title="Eliminar este préstamo y todo su historial permanentemente"
                        style="padding: 0.75rem; border: 1px solid #fee2e2; background: #fff; width: auto; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-2-3">
        <!-- Main Info Column -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">

            <!-- Financial Overview Card -->
            <div class="card">
                <h3
                    style="border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; margin-bottom: 1rem; color: #64748b; font-size: 0.9rem; text-transform: uppercase;">
                    Resumen Financiero</h3>

                <div class="stat-grid">
                    <div class="stat-item">
                        <div class="stat-label">Total Prestado</div>
                        <div class="stat-value"><?= $currency . number_format($loan['amount'], 2) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Total a Pagar</div>
                        <div class="stat-value" style="color: #6366f1;">
                            <?= $currency . number_format($loan['total_amount'], 2) ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Pagado</div>
                        <div class="stat-value" style="color: #10b981;"><?= $currency . number_format($total_paid, 2) ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Pendiente</div>
                        <div class="stat-value"
                            style="color: <?= ($loan['total_amount'] - $total_paid) > 0 ? '#f59e0b' : '#94a3b8' ?>;">
                            <?= $currency . number_format($loan['total_amount'] - $total_paid, 2) ?>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <div
                        style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem;">
                        <span>Progreso de Pago</span>
                        <span><?= number_format($progress, 1) ?>%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                    </div>
                </div>

                <hr class="financial-divider">

                <div class="financial-details-grid">
                    <!-- Column 1: Details List -->
                    <div class="details-list">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--text-primary);">Desglose del Crédito</h4>
                        
                        <div class="details-row">
                            <span class="details-label"><i class="fas fa-percentage" style="color: #3b82f6; width: 16px;"></i> Tasa de Interés</span>
                            <span class="details-value"><?= number_format($loan['interest_rate'], 2) ?>% Mensual</span>
                        </div>
                        <div class="details-row">
                            <span class="details-label"><i class="fas fa-coins" style="color: #f59e0b; width: 16px;"></i> Intereses Generados</span>
                            <span class="details-value"><?= $currency . number_format($interest_total, 2) ?></span>
                        </div>
                        <div class="details-row">
                            <span class="details-label"><i class="fas fa-list-ol" style="color: #8b5cf6; width: 16px;"></i> Cuotas Totales</span>
                            <span class="details-value"><?= $total_installments ?></span>
                        </div>
                        <div class="details-row">
                            <span class="details-label"><i class="fas fa-check-double" style="color: #10b981; width: 16px;"></i> Cuotas Pagadas</span>
                            <span class="details-value" style="color: #10b981;"><?= $paid_installments ?></span>
                        </div>
                        <div class="details-row">
                            <span class="details-label"><i class="fas fa-clock" style="color: #64748b; width: 16px;"></i> Cuotas Pendientes</span>
                            <span class="details-value"><?= $pending_installments ?></span>
                        </div>
                        <div class="details-row">
                            <span class="details-label"><i class="fas fa-exclamation-triangle" style="color: #ef4444; width: 16px;"></i> Cuotas Vencidas (Mora)</span>
                            <span class="details-value" style="<?= $late_installments > 0 ? 'color: #ef4444;' : '' ?>"><?= $late_installments ?></span>
                        </div>
                        <?php if ($total_late_fees_loan > 0): ?>
                            <div class="details-row">
                                <span class="details-label"><i class="fas fa-receipt" style="color: #dc2626; width: 16px;"></i> Moras Acumuladas</span>
                                <span class="details-value" style="color: #dc2626;"><?= $currency . number_format($total_late_fees_loan, 2) ?></span>
                            </div>
                            <div class="details-row">
                                <span class="details-label"><i class="fas fa-file-invoice-dollar" style="color: #166534; width: 16px;"></i> Moras Pagadas</span>
                                <span class="details-value" style="color: #10b981;"><?= $currency . number_format($total_paid_late_fees_loan, 2) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Column 2: Next Payment Info Card -->
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--text-primary);">Próximo Vencimiento</h4>
                        <?php if ($next_payment): ?>
                            <div class="next-payment-card <?= $next_payment['is_late'] ? 'overdue' : '' ?>">
                                <div>
                                    <div class="next-payment-header">
                                        <i class="fas <?= $next_payment['is_late'] ? 'fa-exclamation-triangle' : 'fa-calendar-alt' ?>"></i>
                                        <?= $next_payment['is_late'] ? 'CUOTA VENCIDA' : 'PRÓXIMO PAGO' ?>
                                    </div>
                                    <div class="next-payment-amount">
                                        <?= $currency . number_format($next_payment['remaining'], 2) ?>
                                    </div>
                                </div>
                                <div class="next-payment-meta">
                                    <div><strong>Cuota N°:</strong> <?= $next_payment['num'] ?> de <?= $total_installments ?></div>
                                    <div><strong>Vence el:</strong> <?= date('d/m/Y', strtotime($next_payment['due_date'])) ?></div>
                                    <?php if ($next_payment['paid_amount'] > 0): ?>
                                        <div style="font-size: 0.8rem; opacity: 0.9;">
                                            Abonado: <?= $currency . number_format($next_payment['paid_amount'], 2) ?> de <?= $currency . number_format($next_payment['amount_due'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($next_payment['is_late']): ?>
                                        <span class="badge-alert">
                                            <i class="fas fa-clock"></i> Pago atrasado
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 12px; padding: 2rem 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #10b981;"></i>
                                <strong style="font-size: 1rem;">Crédito Pagado</strong>
                                <span style="font-size: 0.85rem; opacity: 0.8;">Este préstamo ha sido saldado en su totalidad.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-history" style="color: #6366f1;"></i> Historial de Transacciones
                    </h3>
                </div>

                <?php if (count($transactions) > 0): ?>
                    <div class="transaction-list">
                        <?php foreach ($transactions as $t): ?>
                            <div class="transaction-item">
                                <div style="display: flex; align-items: center;">
                                    <div class="t-icon">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <div class="t-info">
                                        <div class="t-date"><?= date('d M, Y', strtotime($t['payment_date'])) ?></div>
                                        <div class="t-meta">Recibo #<?= str_pad($t['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="t-amount"><?= $currency . number_format($t['total_amount'], 2) ?></div>
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem;">
                                        <a href="receipt.php?transaction_id=<?= $t['id'] ?>" target="_blank" title="Ver Recibo"
                                            style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #eff6ff; color: #3b82f6; border-radius: 8px; transition: all 0.2s;">
                                            <i class="fas fa-file-invoice" style="font-size: 0.9rem;"></i>
                                        </a>
                                        <?php if ($user_role !== 'cobrador'): ?>
                                            <form action="void_transaction.php" method="POST" onsubmit="confirmVoid(event, this)"
                                                style="margin: 0;">
                                                <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                                                <button type="submit"
                                                    style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #fef2f2; color: #ef4444; border: none; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                    title="Anular Transacción">
                                                    <i class="fas fa-trash-alt" style="font-size: 0.9rem;"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div
                        style="text-align: center; padding: 2rem; color: #94a3b8; border: 2px dashed #e2e8f0; border-radius: 12px;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No hay transacciones registradas aún.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Schedule Column -->
        <div class="card">
            <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-calendar-check" style="color: #f59e0b;"></i> Calendario de Pagos
            </h3>

            <div class="table-responsive" style="border: none; box-shadow: none;">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vencimiento</th>
                            <th>Cuota</th>
                            <th>Abonado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $index => $payment):
                            $is_late = ($payment['status'] == 'pending' && strtotime($payment['due_date']) < strtotime(date('Y-m-d')));
                            $is_partial = $payment['paid_amount'] > 0 && $payment['paid_amount'] < $payment['amount_due'];
                            ?>
                            <tr>
                                <td style="color: #94a3b8; font-weight: 500;"><?= $index + 1 ?></td>
                                <td style="<?= $is_late ? 'color: #ef4444; font-weight: 600;' : '' ?>">
                                    <?= date('d/m/Y', strtotime($payment['due_date'])) ?>
                                    <?php if ($is_late): ?><i class="fas fa-exclamation-circle"
                                            style="font-size: 0.8rem;"></i><?php endif; ?>
                                </td>
                                <td style="font-weight: 600;"><?= $currency . number_format($payment['amount_due'], 2) ?>
                                </td>
                                <td>
                                    <?php if ($payment['paid_amount'] > 0): ?>
                                        <span
                                            style="color: #10b981; font-weight: 600;"><?= $currency . number_format($payment['paid_amount'], 2) ?></span>
                                        <?php if ($is_partial): ?>
                                            <div style="font-size: 0.75rem; color: #f59e0b;">Restan:
                                                <?= $currency . number_format($payment['amount_due'] - $payment['paid_amount'], 2) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($payment['status'] == 'paid'): ?>
                                        <span style="color: #10b981; font-size: 0.9rem;"><i
                                                class="fas fa-check-circle"></i></span>
                                    <?php else: ?>
                                        <span
                                            style="width: 8px; height: 8px; background: <?= $is_late ? '#ef4444' : ($is_partial ? '#f59e0b' : '#cbd5e1') ?>; border-radius: 50%; display: inline-block;"></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmVoid(event, form) {
        event.preventDefault();

        Swal.fire({
            title: '¿Anular Transacción?',
            text: "Esta acción revertirá los pagos asociados y no se puede deshacer de forma automática.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, Anular',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
</body>

</html>