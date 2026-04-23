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
$current_overdue_qty = 0;
$current_overdue_days = 0;

foreach ($loans as $l) {
    $total_borrowed += $l['amount'];
    $total_interest_earned += ($l['total_amount'] - $l['amount']);

    // Get ALL payments to analyze history and current status
    $stmt_all_payments = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ?");
    $stmt_all_payments->execute([$l['id']]);
    $all_payments_loan = $stmt_all_payments->fetchAll();

    foreach ($all_payments_loan as $p) {
        if ($p['status'] == 'paid') {
            $total_paid += $p['paid_amount'];
            if ($p['late_fee'] > 0) {
                $late_payments++;
                $total_late_fees += $p['late_fee'];
            } else {
                $on_time_payments++;
            }
        } elseif ($p['status'] == 'pending') {
            if (strtotime($p['due_date']) < time()) {
                // Currently overdue!
                $days_late = floor((time() - strtotime($p['due_date'])) / (60 * 60 * 24));
                $current_overdue_qty++;
                $current_overdue_days += $days_late;
            }
        }
    }

    if ($l['status'] == 'cancelled')
        continue;

    if ($l['status'] == 'active')
        $active_loans++;
    else
        $paid_loans++;
}

// Calculate STRICT Credit Score (0-100)
$credit_score = 60; // Start with a neutral/good-ish score

// ---------------- Bonuses ----------------
// 1. Paid Loans: Proof of completion (+10 per loan, max 40)
if ($paid_loans > 0) {
    $credit_score += min(40, $paid_loans * 10);
}

// 2. On-Time Payment Rate: Consistency (+20 max)
$total_historical_payments = $on_time_payments + $late_payments;
if ($total_historical_payments > 0) {
    $on_time_rate = ($on_time_payments / $total_historical_payments); // 0.0 to 1.0
    $credit_score += ($on_time_rate * 20);
}

// ---------------- Penalties ----------------

// 1. Historical Reliability (-5 per late payment, heavy historical penalty)
// If you paid late often, your score suffers permanently until you do better.
if ($late_payments > 0) {
    $credit_score -= ($late_payments * 5);
}

// 2. CURRENT BAD STATUS (The "Killer")
// If you owe money RIGHT NOW and it's late, your score tanks immediately.
if ($current_overdue_qty > 0) {
    // -15 points JUST for being in arrears
    $credit_score -= 15;

    // -5 points for EACH overdue installment
    $credit_score -= ($current_overdue_qty * 5);

    // -1 point for every 5 days overdue (accumulated) max -20 extra
    // This distinguishes "1 day late" vs "3 months late"
    $penalty_days = min(20, floor($current_overdue_days / 5));
    $credit_score -= $penalty_days;
}

// 3. Over-leverage (-10 if more than 2 active loans)
if ($active_loans > 2) {
    $credit_score -= ($active_loans - 2) * 10;
}

// Clamp Score
$credit_score = max(0, min(100, round($credit_score)));

// Score classification
if ($credit_score >= 85) {
    $score_class = 'excellent';
    $score_label = 'Excelente';
    $score_color = '#10b981';
    $score_icon = 'fa-trophy';
} elseif ($credit_score >= 70) {
    $score_class = 'good';
    $score_label = 'Bueno';
    $score_color = '#3b82f6';
    $score_icon = 'fa-thumbs-up';
} elseif ($credit_score >= 50) {
    $score_class = 'fair';
    $score_label = 'Regular';
    $score_color = '#f59e0b';
    $score_icon = 'fa-exclamation-circle';
} else {
    $score_class = 'poor';
    $score_label = 'Riesgoso';
    $score_color = '#ef4444';
    $score_icon = 'fa-times-circle';
}

// Include enhanced header
require 'components/enhanced_header.php';
?>

<!-- External Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    /* Modern Dashboard Styles */
    :root {
        --primary-surface: var(--bg-primary);
        --secondary-surface: var(--bg-secondary);
        --border-color: var(--border-color);
        --text-primary: var(--text-primary);
        --text-secondary: var(--text-secondary);
        --shadow-sm: var(--shadow);
        --shadow-md: var(--shadow);
        --radius-md: 12px;
        --radius-lg: 16px;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .action-btn.primary {
        background-color: #3b82f6;
        color: white;
        box-shadow: var(--shadow-sm);
    }

    .action-btn.primary:hover {
        background-color: #2563eb;
    }

    .action-btn.success {
        background-color: var(--bg-primary);
        color: #059669;
        border-color: #d1fae5;
    }

    .action-btn.success:hover {
        background-color: #ecfdf5;
    }

    .action-btn.secondary {
        background-color: var(--bg-primary);
        color: var(--text-secondary);
        border-color: var(--border-color);
    }

    .action-btn.secondary:hover {
        background-color: #f9fafb;
        border-color: #d1d5db;
    }

    /* Cards & Layout */
    .credit-score-card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        text-align: center;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .score-gauge-container {
        position: relative;
        width: 180px;
        height: 180px;
        margin: 1.5rem 0;
        border-radius: 50%;
        background: conic-gradient(var(--score-color) 0%,
                var(--score-color) var(--score-deg),
                var(--bg-secondary) var(--score-deg),
                var(--bg-secondary) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.05);
        transition: background 1s ease-out;
    }

    .score-gauge-container::before {
        content: '';
        position: absolute;
        width: 150px;
        height: 150px;
        background: var(--primary-surface);
        border-radius: 50%;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .score-content-wrapper {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .score-value {
        font-size: 3.5rem;
        font-weight: 800;
        color: var(--score-color);
        line-height: 1;
        letter-spacing: -2px;
    }

    .score-label {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-top: 0.5rem;
    }

    .score-feedback {
        margin-top: 1.5rem;
        color: var(--text-secondary);
        font-size: 0.95rem;
        max-width: 500px;
        line-height: 1.6;
        padding: 1rem;
        background: var(--secondary-surface);
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .stat-card {
        background: var(--primary-surface);
        border-radius: var(--radius-md);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        transition: transform 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        position: relative;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--stat-color, #cbd5e1);
        border-radius: 4px 4px 0 0;
    }

    /* Color definitions for stats */
    .stat-card.blue {
        --stat-color: #3b82f6;
    }

    .stat-card.green {
        --stat-color: #10b981;
    }

    .stat-card.amber {
        --stat-color: #f59e0b;
    }

    .stat-card.purple {
        --stat-color: #8b5cf6;
    }

    .stat-card.cyan {
        --stat-color: #06b6d4;
    }

    .stat-card.red {
        --stat-color: #ef4444;
    }

    .stat-icon {
        font-size: 1.5rem;
        color: var(--stat-color);
        margin-bottom: 1rem;
        background: color-mix(in srgb, var(--stat-color) 10%, transparent);
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    /* Client Header */
    .client-header {
        background: var(--primary-surface);
        padding: 2rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    @media (min-width: 768px) {
        .client-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .client-name {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 0;
    }

    .client-info-grid {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .client-info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-secondary);
        font-size: 0.95rem;
    }

    .client-info-item i {
        color: #64748b;
    }

    /* Tables */
    .table-container {
        background: var(--primary-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .history-table th {
        background: var(--secondary-surface);
        padding: 1rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 600;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .history-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .history-table tr:last-child td {
        border-bottom: none;
    }

    .history-table tr:hover td {
        background-color: #f8fafc;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        color: var(--text-secondary);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .back-button:hover {
        color: var(--text-primary);
    }

    .details-row {
        background-color: #f8fafc;
        display: none;
    }

    .details-row.show {
        display: table-row;
    }

    .details-content {
        padding: 1.5rem !important;
    }

    @media print {

        .no-print,
        .action-buttons,
        nav {
            display: none !important;
        }

        .container {
            max-width: 100%;
            padding: 0;
        }

        .card,
        .stat-card,
        .client-header {
            box-shadow: none;
            border: 1px solid #ddd;
        }
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
            <span><i class="fas fa-id-card" style="color: #6366f1;"></i>
                <?= htmlspecialchars($client['cedula'] ?? 'N/A') ?></span>
            <span><i class="fas fa-phone" style="color: #6366f1;"></i>
                <?= htmlspecialchars($client['phone'] ?? 'N/A') ?></span>
            <span><i class="fas fa-map-marker-alt" style="color: #6366f1;"></i>
                <?= htmlspecialchars($client['address'] ?? 'N/A') ?></span>
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
    <?php $score_deg = $credit_score * 3.6; ?>
    <div class="credit-score-card" style="--score-color: <?= $score_color ?>; --score-deg: <?= $score_deg ?>deg;">
        <h3
            style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas <?= $score_icon ?? 'fa-chart-line' ?>"></i> Análisis de Riesgo
        </h3>

        <div class="score-gauge-container">
            <div class="score-content-wrapper">
                <span class="score-value"><?= $credit_score ?></span>
                <span class="score-label" style="color: <?= $score_color ?>"><?= $score_label ?></span>
            </div>
        </div>

        <p class="score-feedback">
            <?php if ($credit_score >= 85): ?>
                <i class="fas fa-check-circle" style="color: #10b981;"></i> <strong>Excelente:</strong> Cliente modelo.
                Aprobación inmediata recomendada.
            <?php elseif ($credit_score >= 70): ?>
                <i class="fas fa-thumbs-up" style="color: #3b82f6;"></i> <strong>Bueno:</strong> Cliente confiable con
                historial positivo. Bajo riesgo.
            <?php elseif ($credit_score >= 50): ?>
                <i class="fas fa-exclamation-circle" style="color: #f59e0b;"></i> <strong>Regular:</strong> Historial mixto.
                Se sugiere revisión manual.
            <?php else: ?>
                <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> <strong>Riesgoso:</strong> Historial de
                moras graves o deudas activas vencidas.
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
                            <td style="text-align: right; font-weight: 700;">$<?= number_format($loan['total_amount'], 2) ?>
                            </td>
                            <td style="text-align: right; color: #10b981; font-weight: 600;">$<?= number_format($paid_sum, 2) ?>
                            </td>
                            <td style="text-align: right; color: #ef4444; font-weight: 600;">
                                $<?= number_format($remaining, 2) ?></td>
                            <td style="text-align: center;">
                                <span class="badge" style="<?php
                                if ($loan['status'] == 'active')
                                    echo 'background-color: #fef3c7; color: #92400e; border: 1px solid #fbbf24;';
                                elseif ($loan['status'] == 'paid')
                                    echo 'background-color: #d1fae5; color: #065f46; border: 1px solid #34d399;';
                                elseif ($loan['status'] == 'cancelled')
                                    echo 'background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; text-decoration: line-through;';
                                ?>">
                                    <i
                                        class="fas fa-<?= $loan['status'] == 'active' ? 'clock' : ($loan['status'] == 'paid' ? 'check-circle' : 'ban') ?>"></i>
                                    <?= $loan['status'] == 'active' ? 'Activo' : ($loan['status'] == 'paid' ? 'Pagado' : 'Cancelado') ?>
                                </span>
                            </td>
                            <td>
                                <div style="background: #e2e8f0; border-radius: 100px; height: 8px; overflow: hidden;">
                                    <div style="height: 100%; background: #10b981; width: <?= $progress ?>%;"></div>
                                </div>
                                <div
                                    style="text-align: center; font-size: 0.7rem; color: #64748b; margin-top: 0.25rem; font-weight: 600;">
                                    <?= number_format($progress, 0) ?>%
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <button onclick="toggleDetails(<?= $loan['id'] ?>)" class="action-btn secondary"
                                    style="padding: 0.5rem; width: 32px; height: 32px; justify-content: center; border-radius: 8px;">
                                    <i class="fas fa-chevron-down" id="icon-<?= $loan['id'] ?>"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="details-<?= $loan['id'] ?>" class="details-row">
                            <td colspan="10" class="details-content">
                                <div
                                    style="background: white; border-radius: 12px; padding: 1.5rem; border: 1px solid #e2e8f0;">
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <h5
                                            style="margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-history" style="color: #3b82f6;"></i> Historial de Pagos
                                        </h5>
                                        <a href="print_payment_plan.php?loan_id=<?= $loan['id'] ?>" target="_blank"
                                            class="action-btn secondary"
                                            style="padding: 0.25rem 0.75rem; font-size: 0.8rem; text-decoration: none;">
                                            <i class="fas fa-print"></i> Imprimir Plan
                                        </a>
                                    </div>
                                    <?php if (count($payments) > 0): ?>
                                        <table style="width: 100%; font-size: 0.9rem;">
                                            <thead>
                                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                                    <th style="text-align: left; padding: 0.75rem; color: #64748b;">Fecha</th>
                                                    <th style="text-align: right; padding: 0.75rem; color: #64748b;">Monto</th>
                                                    <th style="text-align: center; padding: 0.75rem; color: #64748b;">Estado</th>
                                                    <th style="text-align: center; padding: 0.75rem; color: #64748b;">Recibo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr style="border-bottom: 1px dashed #e2e8f0;">
                                                        <td style="padding: 0.75rem;">
                                                            <?= date('d/m/Y H:i', strtotime($payment['paid_date'])) ?>
                                                        </td>
                                                        <td
                                                            style="padding: 0.75rem; text-align: right; font-weight: 600; color: #10b981;">
                                                            $<?= number_format($payment['paid_amount'], 2) ?></td>
                                                        <td style="padding: 0.75rem; text-align: center;">
                                                            <?php if ($payment['late_fee'] > 0): ?>
                                                                <span style="color: #ef4444; font-size: 0.8rem; font-weight: 600;"><i
                                                                        class="fas fa-exclamation-circle"></i> Mora:
                                                                    $<?= number_format($payment['late_fee'], 2) ?></span>
                                                            <?php else: ?>
                                                                <span style="color: #10b981; font-size: 0.8rem; font-weight: 600;"><i
                                                                        class="fas fa-check"></i> A tiempo</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="padding: 0.75rem; text-align: center;">
                                                            <a href="receipt.php?payment_id=<?= $payment['id'] ?>" target="_blank"
                                                                class="action-btn secondary"
                                                                style="padding: 0.25rem 0.75rem; font-size: 0.75rem; text-decoration: none;">
                                                                <i class="fas fa-print"></i> Ver
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p style="text-align: center; color: #94a3b8; margin: 0; padding: 1rem;">No hay pagos
                                            registrados aún.</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div
            style="text-align: center; padding: 5rem 2rem; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 24px; color: #92400e; border: 3px solid #fbbf24; box-shadow: 0 10px 25px -5px rgba(251, 191, 36, 0.3);">
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

    <!-- Global Payment History -->
    <h3 style="margin-top: 4rem; margin-bottom: 2rem; font-size: 2rem; color: #1e293b; font-weight: 800;">
        <i class="fas fa-money-check-alt" style="color: #10b981;"></i> Historial Global de Pagos
    </h3>

    <?php
    // Fetch all payments for this client across all loans
    $stmt_global = $pdo->prepare("
        SELECT p.*, l.id as loan_id, l.status as loan_status 
        FROM payments p 
        JOIN loans l ON p.loan_id = l.id 
        WHERE l.client_id = ? 
        AND p.paid_amount > 0 
        ORDER BY p.paid_date DESC
    ");
    $stmt_global->execute([$client_id]);
    $global_payments = $stmt_global->fetchAll();
    ?>

    <?php if (count($global_payments) > 0): ?>
        <div class="table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Fecha de Pago</th>
                        <th>Préstamo ID</th>
                        <th style="text-align: right;">Monto Pagado</th>
                        <th style="text-align: right;">Mora Pagada</th>
                        <th style="text-align: center;">Estado Cuota</th>
                        <th style="text-align: center;">Recibo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($global_payments as $gp): ?>
                        <tr>
                            <td>
                                <strong><?= date('d/m/Y', strtotime($gp['paid_date'])) ?></strong><br>
                                <small style="color: #64748b;"><?= date('h:i A', strtotime($gp['paid_date'])) ?></small>
                            </td>
                            <td>
                                <a href="loan_details.php?id=<?= $gp['loan_id'] ?>"
                                    style="text-decoration: none; font-weight: bold; color: #3b82f6;">
                                    #<?= $gp['loan_id'] ?>
                                </a>
                                <span style="font-size: 0.7rem; color: #64748b; margin-left: 5px;">
                                    (<?= $gp['loan_status'] == 'active' ? 'Activo' : ($gp['loan_status'] == 'paid' ? 'Pagado' : 'Cancelado') ?>)
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: bold; color: #10b981;">
                                $<?= number_format($gp['paid_amount'], 2) ?>
                            </td>
                            <td style="text-align: right; color: #ef4444;">
                                <?= $gp['paid_late_fee'] > 0 ? '$' . number_format($gp['paid_late_fee'], 2) : '-' ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($gp['status'] == 'paid'): ?>
                                    <span style="color: #10b981; font-weight: 700; font-size: 0.8rem;"><i
                                            class="fas fa-check-circle"></i> Completo</span>
                                <?php else: ?>
                                    <span style="color: #f59e0b; font-weight: 700; font-size: 0.8rem;"><i class="fas fa-adjust"></i>
                                        Parcial</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="receipt.php?payment_id=<?= $gp['id'] ?>" target="_blank" class="action-btn secondary"
                                    style="padding: 0.4rem 1rem; font-size: 0.8rem; text-decoration: none;">
                                    <i class="fas fa-print"></i> Ver Recibo
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p
            style="text-align: center; color: #64748b; padding: 2rem; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
            No hay pagos registrados para este cliente.
        </p>
    <?php endif; ?>

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