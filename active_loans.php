<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

// Check user role and filter loans accordingly
$user_role = $_SESSION['role'] ?? 'admin';
$user_portfolio_id = $_SESSION['portfolio_id'] ?? null;

if ($user_role === 'cobrador') {
    // Cobrador: Only see loans from their assigned portfolio
    if (!$user_portfolio_id) {
        die("Error: No tienes una cartera asignada. Contacta al administrador.");
    }

    $stmt = $pdo->prepare("
        SELECT l.*, c.name, c.cedula, p.name as portfolio_name,
        (SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE loan_id = l.id) as total_paid
        FROM loans l 
        JOIN clients c ON l.client_id = c.id 
        LEFT JOIN portfolios p ON c.portfolio_id = p.id
        WHERE l.status = 'active' AND c.portfolio_id = ?
        ORDER BY l.id DESC
    ");
    $stmt->execute([$user_portfolio_id]);
    $loans = $stmt->fetchAll();

    // Get portfolio name
    $stmt_portfolio = $pdo->prepare("SELECT name FROM portfolios WHERE id = ?");
    $stmt_portfolio->execute([$user_portfolio_id]);
    $portfolio_name = $stmt_portfolio->fetchColumn();
} else {
    // Admin/SuperAdmin: See all loans
    $stmt = $pdo->query("
        SELECT l.*, c.name, c.cedula, p.name as portfolio_name,
        (SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE loan_id = l.id) as total_paid
        FROM loans l 
        JOIN clients c ON l.client_id = c.id 
        LEFT JOIN portfolios p ON c.portfolio_id = p.id
        WHERE l.status = 'active'
        ORDER BY l.id DESC
    ");
    $loans = $stmt->fetchAll();
    $portfolio_name = null;
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
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* Cards */
    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--primary-surface);
        flex-wrap: wrap;
        gap: 1rem;
    }

    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Search Bar */
    .search-bar-container {
        background: var(--secondary-surface);
        padding: 1rem;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
        margin-bottom: 1.5rem;
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 2rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }

    /* Table */
    .table-container {
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        background: var(--secondary-surface);
        padding: 1rem;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .modern-table tr:hover td {
        background: var(--secondary-surface);
    }

    /* Elements */
    .badge-modern {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .badge-modern.primary {
        background: #e0e7ff;
        color: #4338ca;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        opacity: 0.9;
    }

    .btn-secondary {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        border-color: var(--border-color);
    }

    .btn-secondary:hover {
        background: var(--secondary-surface);
        color: var(--text-primary);
    }

    .client-avatar {
        width: 36px;
        height: 36px;
        background: #eff6ff;
        color: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-right: 0.75rem;
        font-size: 0.9rem;
    }
</style>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-hand-holding-usd" style="color: var(--success-color);"></i>
                Gestión de Créditos Activos (Abonar)
            </h3>
            <?php if ($portfolio_name): ?>
                <span class="badge-modern primary">
                    <i class="fas fa-briefcase"></i> <?= htmlspecialchars($portfolio_name) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="card-body">

            <div class="search-bar-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" onkeyup="filterTable()" class="search-input"
                    placeholder="Buscar por nombre del cliente, cédula o ID...">
            </div>

            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th>Cliente</th>
                            <?php if ($user_role !== 'cobrador'): ?>
                                <th>Cartera</th>
                            <?php endif; ?>
                            <th style="text-align: right;">Monto Total</th>
                            <th style="text-align: right;">Saldo Pendiente</th>
                            <th>Inicio</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td><span
                                        style="color: var(--text-secondary); font-family: monospace;">#<?= $loan['id'] ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="client-avatar">
                                            <?= strtoupper(substr($loan['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-primary);">
                                                <?= htmlspecialchars($loan['name']) ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                                <?= htmlspecialchars($loan['cedula'] ?? 'N/A') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <?php if ($user_role !== 'cobrador'): ?>
                                    <td>
                                        <?php if ($loan['portfolio_name']): ?>
                                            <span class="badge-modern primary">
                                                <?= htmlspecialchars($loan['portfolio_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 0.85rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td style="text-align: right; font-weight: 500;">
                                    $<?= number_format($loan['total_amount'], 2) ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--danger-color);">
                                    $<?= number_format($loan['total_amount'] - $loan['total_paid'], 2) ?>
                                </td>
                                <td>
                                    <span style="color: var(--text-secondary); font-size: 0.85rem;">
                                        <?= date('d/m/Y', strtotime($loan['start_date'])) ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="loan_details.php?id=<?= $loan['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-cash-register"></i> Abonar
                                    </a>
                                    <?php if (in_array($user_role, ['admin', 'superadmin'])): ?>
                                        <button type="button"
                                            onclick="confirmarCancelacion(<?= $loan['id'] ?>, '<?= htmlspecialchars(addslashes($loan['name']), ENT_QUOTES) ?>')"
                                            class="btn" style="background:#ef4444;color:white;margin-left:0.4rem;">
                                            <i class="fas fa-times-circle"></i> Cancelar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($loans)): ?>
                            <tr>
                                <td colspan="<?= $user_role !== 'cobrador' ? '7' : '6' ?>"
                                    style="text-align: center; padding: 4rem 2rem;">
                                    <div style="color: var(--text-secondary);">
                                        <i class="fas fa-inbox"
                                            style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                                        <p style="margin: 0; font-size: 1.1rem;">No hay créditos activos en este momento.
                                        </p>
                                        <?php if ($portfolio_name): ?>
                                            <p style="font-size: 0.9rem; opacity: 0.7;">Cartera:
                                                <?= htmlspecialchars($portfolio_name) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmarCancelacion(loanId, clientName) {
        Swal.fire({
            title: '¿Cancelar crédito?',
            html: `¿Estás seguro de que deseas cancelar el crédito de <strong>${clientName}</strong>?<br><br><span style="color:#ef4444;font-size:0.9rem;">⚠️ Esta acción eliminará el crédito permanentemente.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, regresar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cancel_loan.php';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'loan_id';
                input.value = loanId;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function filterTable() {
        // Simple client-side filter
        const input = document.getElementById("searchInput");
        const filter = input.value.toUpperCase();
        const table = document.querySelector("table");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            // Index 1 is the Client Name/Details column
            const td = tr[i].getElementsByTagName("td")[1];
            if (td) {
                const txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>
</body>

</html>