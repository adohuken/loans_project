<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

$user_role = $_SESSION['role'] ?? 'admin';
$user_portfolio_id = $_SESSION['portfolio_id'] ?? null;
$client_id = $_GET['id'];

// Fetch Client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client)
    die("Cliente no encontrado");

// Fetch Loans with payment details
$stmt_loans = $pdo->prepare("SELECT * FROM loans WHERE client_id = ? ORDER BY start_date DESC");
$stmt_loans->execute([$client_id]);
$loans = $stmt_loans->fetchAll();

// Advanced Stats
$total_loans = count($loans);
$active_loans = 0;
$paid_loans = 0;
$total_borrowed = 0;
$total_paid = 0;
$total_interest_earned = 0;
$on_time_payments = 0;
$late_payments = 0;
$total_late_fees = 0;

foreach ($loans as $l) {
    $total_borrowed += $l['amount'];
    $total_interest_earned += ($l['total_amount'] - $l['amount']);

    // Get payments for this loan
    $stmt_payments = $pdo->prepare("SELECT SUM(paid_amount) as total FROM payments WHERE loan_id = ?");
    $stmt_payments->execute([$l['id']]);
    $payment_sum = $stmt_payments->fetch();
    $total_paid += $payment_sum['total'] ?? 0;

    // Get payment details for score calculation
    $stmt_payment_details = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? AND status = 'paid'");
    $stmt_payment_details->execute([$l['id']]);
    $payments = $stmt_payment_details->fetchAll();

    foreach ($payments as $p) {
        if ($p['late_fee'] > 0) {
            $late_payments++;
            $total_late_fees += $p['late_fee'];
        } else {
            $on_time_payments++;
        }
    }

    if ($l['status'] == 'active')
        $active_loans++;
    else
        $paid_loans++;
}

// Calculate Credit Score (0-100)
$credit_score = 50; // Base score

// Positive factors
if ($paid_loans > 0) {
    $credit_score += min(20, $paid_loans * 5); // +5 per paid loan, max 20
}

if ($on_time_payments > 0) {
    $total_payments = $on_time_payments + $late_payments;
    $on_time_rate = ($on_time_payments / $total_payments) * 100;
    $credit_score += ($on_time_rate * 0.3); // Up to 30 points
}

// Negative factors
if ($late_payments > 0) {
    $credit_score -= min(30, $late_payments * 3); // -3 per late payment, max -30
}

if ($active_loans > 3) {
    $credit_score -= ($active_loans - 3) * 5; // -5 per extra active loan
}

$credit_score = max(0, min(100, round($credit_score)));

// Score classification
if ($credit_score >= 80) {
    $score_class = 'excellent';
    $score_label = 'Excelente';
    $score_color = '#10b981';
} elseif ($credit_score >= 60) {
    $score_class = 'good';
    $score_label = 'Bueno';
    $score_color = '#3b82f6';
} elseif ($credit_score >= 40) {
    $score_class = 'fair';
    $score_label = 'Regular';
    $score_color = '#f59e0b';
} else {
    $score_class = 'poor';
    $score_label = 'Bajo';
    $score_color = '#ef4444';
}

// Include enhanced header
require 'components/enhanced_header.php';
?>

<!-- External Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    /* Custom Styles for Client History */

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1.5rem;
        border-radius: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .action-btn.primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .action-btn.success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .action-btn.secondary {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 12px -2px rgba(0, 0, 0, 0.2);
    }

    .credit-score-card {
        background: linear-gradient(135deg, #ffffff, #f8fafc);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.1);
        border: 2px solid #e2e8f0;
        text-align: center;
    }

    .score-value {
        font-size: 3rem;
        font-weight: 900;
        color: var(--score-color);
        margin: 0.75rem 0;
    }

    .score-label {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--score-color);
        margin-bottom: 0.5rem;
    }

    .score-description {
        color: #64748b;
        font-size: 0.875rem;
        max-width: 500px;
        margin: 0 auto;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin: 2rem 0;
    }

    .stat-card {
        background: linear-gradient(135deg, var(--bg-start), var(--bg-end));
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.15);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 120px;
        height: 120px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        border-radius: 50%;
    }

    .stat-card:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 12px 20px -6px rgba(0, 0, 0, 0.2);
    }

    .stat-card.blue {
        --bg-start: #60a5fa;
        --bg-end: #3b82f6;
    }

    .stat-card.green {
        --bg-start: #34d399;
        --bg-end: #10b981;
    }

    .stat-card.amber {
        --bg-start: #fbbf24;
        --bg-end: #f59e0b;
    }

    .stat-card.purple {
        --bg-start: #a78bfa;
        --bg-end: #8b5cf6;
    }

    .stat-card.red {
        --bg-start: #f87171;
        --bg-end: #ef4444;
    }

    .stat-card.cyan {
        --bg-start: #22d3ee;
        --bg-end: #06b6d4;
    }

    .stat-icon {
        font-size: 2rem;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 0.75rem;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        position: relative;
        z-index: 1;
    }

    .stat-label {
        color: rgba(255, 255, 255, 0.98);
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }

    .stat-value {
        color: white;
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        position: relative;
        z-index: 1;
    }

    .client-header {
        background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 50%, #f3e8ff 100%);
        color: #1e293b;
        padding: 2.5rem;
        border-radius: 24px;
        margin-bottom: 2.5rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        border: 3px solid #bfdbfe;
        position: relative;
        overflow: hidden;
    }

    .client-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 70%);
        border-radius: 50%;
    }

    .client-name {
        font-size: 2.25rem;
        font-weight: 900;
        margin: 0 0 0.75rem 0;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        background: linear-gradient(135deg, #1e40af, #7c3aed);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        z-index: 1;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 1rem;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 1.25rem 2.5rem;
        border-radius: 16px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 8px 16px -4px rgba(99, 102, 241, 0.4);
        font-size: 1.05rem;
    }

    .back-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 24px -8px rgba(99, 102, 241, 0.5);
    }

    @media print {
        .action-buttons, nav, .back-button, .no-print {
            display: none !important;
        }
    }

    /* Table Styles - Compact & Responsive */
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05);
        overflow-x: auto;
        border: 1px solid #e2e8f0;
    }
    
    .history-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    .history-table th {
        background: #f8fafc;
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .history-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 0.85rem;
        vertical-align: middle;
    }

    .history-table tr:hover td {
        background-color: #f8fafc;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.6rem;
        border-radius: 100px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-badge.active {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fbbf24;
    }

    .status-badge.paid {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #34d399;
    }

    .details-row {
        background-color: #f8fafc;
        display: none;
    }

    .details-row.show {
        display: table-row;
    }

    .details-content {
        padding: 1rem !important;
        box-shadow: inset 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="container" id="printable-area">
    <!-- Client Header -->
    <div class="client-header">
        <h1 class="client-name">
            <i class="fas fa-user-circle" style="color: #4f46e5;"></i>
            <?= htmlspecialchars($client['name']) ?>
        </h1>
        <div style="display: flex; gap: 2rem; font-size: 1.1rem; color: #475569; flex-wrap: wrap;">
            <span><i class="fas fa-id-card" style="color: #6366f1;"></i> <?= htmlspecialchars($client['cedula'] ?? 'N/A') ?></span>
            <span><i class="fas fa-phone" style="color: #6366f1;"></i> <?= htmlspecialchars($client['phone'] ?? 'N/A') ?></span>
            <span><i class="fas fa-map-marker-alt" style="color: #6366f1;"></i> <?= htmlspecialchars($client['address'] ?? 'N/A') ?></span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button onclick="exportToPDF()" class="action-btn primary">
            <i class="fas fa-file-pdf"></i> Exportar a PDF
        </button>
        <button onclick="window.print()" class="action-btn success">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <button onclick="shareHistory()" class="action-btn secondary">
            <i class="fas fa-share-alt"></i> Compartir
        </button>
    </div>

    <!-- Credit Score Card -->
    <div class="credit-score-card" style="--score-color: <?= $score_color ?>;">
        <h3 style="font-size: 1.125rem; font-weight: 800; color: #1e293b; margin-bottom: 0.75rem;">
            <i class="fas fa-star"></i> Score Crediticio
        </h3>
        <div class="score-value"><?= $credit_score ?></div>
        <div class="score-label"><?= $score_label ?></div>
        <p class="score-description">
            <?php if ($credit_score >= 80): ?>
                Cliente excelente con historial impecable. Alta confiabilidad para nuevos préstamos.
            <?php elseif ($credit_score >= 60): ?>
                Cliente confiable con buen historial de pagos. Apto para préstamos.
            <?php elseif ($credit_score >= 40): ?>
                Cliente regular. Revisar historial antes de aprobar nuevos préstamos.
            <?php else: ?>
                Cliente de alto riesgo. Se recomienda precaución con nuevos préstamos.
            <?php endif; ?>
        </p>
    </div>

    <!-- Enhanced Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="stat-label">Total Préstamos</div>
            <div class="stat-value"><?= $total_loans ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Pagados</div>
            <div class="stat-value"><?= $paid_loans ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-label">Activos</div>
            <div class="stat-value"><?= $active_loans ?></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-label">Total Prestado</div>
            <div class="stat-value">$<?= number_format($total_borrowed, 0) ?></div>
        </div>
        <div class="stat-card cyan">
            <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="stat-label">Total Pagado</div>
            <div class="stat-value">$<?= number_format($total_paid, 0) ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Moras Totales</div>
            <div class="stat-value">$<?= number_format($total_late_fees, 0) ?></div>
        </div>
    </div>

    <!-- Loans List -->
    <h3 style="margin-top: 3rem; margin-bottom: 2rem; font-size: 2rem; color: #1e293b; font-weight: 800;">
        <i class="fas fa-list-alt" style="color: #3b82f6;"></i> Historial Detallado de Préstamos
    </h3>

    <?php if (count($loans) > 0): ?>
        <div class="table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Fecha</th>
                        <th style="text-align: right;">Monto</th>
                        <th style="text-align: right;">Interés</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Pagado</th>
                        <th style="text-align: right;">Pendiente</th>
                        <th style="text-align: center;">Estado</th>
                        <th style="width: 150px;">Progreso</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <?php
                        $stmt_payments = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? AND status = 'paid' ORDER BY paid_date DESC");
                        $stmt_payments->execute([$loan['id']]);
                        $payments = $stmt_payments->fetchAll();

                        $stmt_sum = $pdo->prepare("SELECT SUM(paid_amount) as total FROM payments WHERE loan_id = ?");
                        $stmt_sum->execute([$loan['id']]);
                        $paid_sum = $stmt_sum->fetch()['total'] ?? 0;

                        $remaining = $loan['total_amount'] - $paid_sum;
                        $progress = ($loan['total_amount'] > 0) ? ($paid_sum / $loan['total_amount']) * 100 : 0;
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: #64748b;"><?= $loan['id'] ?></td>
                            <td>
                                <div style="font-weight: 600;"><?= date('d/m/Y', strtotime($loan['start_date'])) ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">Inicio</div>
                            </td>
                            <td style="text-align: right; font-weight: 600;">$<?= number_format($loan['amount'], 2) ?></td>
                            <td style="text-align: right; color: #64748b;"><?= $loan['interest_rate'] ?>%</td>
                            <td style="text-align: right; font-weight: 700;">$<?= number_format($loan['total_amount'], 2) ?></td>
                            <td style="text-align: right; color: #10b981; font-weight: 600;">$<?= number_format($paid_sum, 2) ?></td>
                            <td style="text-align: right; color: #ef4444; font-weight: 600;">$<?= number_format($remaining, 2) ?></td>
                            <td style="text-align: center;">
                                <span class="status-badge <?= $loan['status'] ?>">
                                    <i class="fas fa-<?= $loan['status'] == 'active' ? 'clock' : 'check-circle' ?>"></i>
                                    <?= $loan['status'] == 'active' ? 'Activo' : 'Pagado' ?>
                                </span>
                            </td>
                            <td>
                                <div style="background: #e2e8f0; border-radius: 100px; height: 8px; overflow: hidden;">
                                    <div style="height: 100%; background: #10b981; width: <?= $progress ?>%;"></div>
                                </div>
                                <div style="text-align: center; font-size: 0.7rem; color: #64748b; margin-top: 0.25rem; font-weight: 600;">
                                    <?= number_format($progress, 0) ?>%
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <button onclick="toggleDetails(<?= $loan['id'] ?>)" class="action-btn secondary" style="padding: 0.5rem; width: 32px; height: 32px; justify-content: center; border-radius: 8px;">
                                    <i class="fas fa-chevron-down" id="icon-<?= $loan['id'] ?>"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="details-<?= $loan['id'] ?>" class="details-row">
                            <td colspan="10" class="details-content">
                                <div style="background: white; border-radius: 12px; padding: 1.5rem; border: 1px solid #e2e8f0;">
                                    <h5 style="margin: 0 0 1rem 0; font-size: 0.95rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-history" style="color: #3b82f6;"></i> Historial de Pagos
                                    </h5>
                                    <?php if (count($payments) > 0): ?>
                                        <table style="width: 100%; font-size: 0.9rem;">
                                            <thead>
                                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                                    <th style="text-align: left; padding: 0.75rem; color: #64748b;">Fecha</th>
                                                    <th style="text-align: right; padding: 0.75rem; color: #64748b;">Monto</th>
                                                    <th style="text-align: center; padding: 0.75rem; color: #64748b;">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr style="border-bottom: 1px dashed #e2e8f0;">
                                                        <td style="padding: 0.75rem;"><?= date('d/m/Y H:i', strtotime($payment['paid_date'])) ?></td>
                                                        <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #10b981;">$<?= number_format($payment['paid_amount'], 2) ?></td>
                                                        <td style="padding: 0.75rem; text-align: center;">
                                                            <?php if ($payment['late_fee'] > 0): ?>
                                                                <span style="color: #ef4444; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Mora: $<?= number_format($payment['late_fee'], 2) ?></span>
                                                            <?php else: ?>
                                                                <span style="color: #10b981; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-check"></i> A tiempo</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p style="text-align: center; color: #94a3b8; margin: 0; padding: 1rem;">No hay pagos registrados aún.</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 5rem 2rem; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 24px; color: #92400e; border: 3px solid #fbbf24; box-shadow: 0 10px 25px -5px rgba(251, 191, 36, 0.3);">
            <i class="fas fa-exclamation-triangle" style="font-size: 5rem; margin-bottom: 2rem; opacity: 0.6;"></i>
            <h3 style="font-size: 1.75rem; font-weight: 800;">No hay préstamos registrados</h3>
            <p style="margin: 0; font-size: 1.1rem;">Este cliente aún no tiene préstamos en el sistema.</p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 3rem; text-align: center;" class="no-print">
        <a href="clients.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver a Clientes
        </a>
    </div>
</div>

<script>
    // Export to PDF
    function exportToPDF() {
        const element = document.getElementById('printable-area');
        const opt = {
            margin: 10,
            filename: 'historial_<?= $client['name'] ?>_<?= date('Y-m-d') ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }

    // Share History
    function shareHistory() {
        if (navigator.share) {
            navigator.share({
                title: 'Historial Crediticio - <?= htmlspecialchars($client['name']) ?>',
                text: 'Historial crediticio del cliente',
                url: window.location.href
            }).catch(console.error);
        } else {
            alert('Función de compartir no disponible en este navegador');
        }
    }

    // Toggle Details
    function toggleDetails(id) {
        const row = document.getElementById('details-' + id);
        const icon = document.getElementById('icon-' + id);
        
        if (row.classList.contains('show')) {
            row.classList.remove('show');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        } else {
            row.classList.add('show');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    }
</script>
</body>
</html>