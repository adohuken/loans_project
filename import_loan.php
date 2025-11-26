<?php
require 'auth.php';
require 'db.php';

$clients = $pdo->query("SELECT * FROM clients ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Préstamo Existente - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=3.0">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="reports.php">Reportes</a>
                <a href="portfolios.php">Carteras</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php">Usuarios</a>
                <?php endif; ?>
                <a href="settings.php">Configuración</a>
                <a href="backup.php">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <h2>📥 Importar Préstamo Existente</h2>
            <p style="color: #64748b; margin-bottom: 1.5rem;">
                Usa este formulario para registrar préstamos que ya están activos y darles seguimiento en el sistema.
            </p>

            <form action="save_imported_loan.php" method="POST" id="importForm">
                <div class="grid">
                    <!-- Cliente -->
                    <div class="form-group">
                        <label>Cliente *</label>
                        <select name="client_id" required>
                            <option value="">Seleccione un cliente...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>">
                                    <?= htmlspecialchars($client['cedula'] . ' - ' . $client['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fecha de Inicio Original -->
                    <div class="form-group">
                        <label>Fecha de Inicio Original *</label>
                        <input type="date" name="start_date" required>
                    </div>

                    <!-- Monto Original Prestado -->
                    <div class="form-group">
                        <label>Monto Original Prestado ($) *</label>
                        <input type="number" name="amount" step="0.01" required placeholder="Ej. 10000">
                    </div>

                    <!-- Interés Mensual -->
                    <div class="form-group">
                        <label>Interés Mensual (%) *</label>
                        <input type="number" name="interest_rate" step="0.1" required placeholder="Ej. 10">
                    </div>

                    <!-- Duración en Meses -->
                    <div class="form-group">
                        <label>Duración Total (Meses) *</label>
                        <input type="number" name="duration_months" required placeholder="Ej. 12">
                    </div>

                    <!-- Frecuencia de Pago -->
                    <div class="form-group">
                        <label>Frecuencia de Pago *</label>
                        <select name="frequency" required>
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quincenal</option>
                            <option value="monthly">Mensual</option>
                        </select>
                    </div>
                </div>

                <div
                    style="background: #fef9c3; border: 1px solid #fde68a; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                    <h3 style="color: #854d0e; margin: 0 0 0.5rem 0;">💡 Estado Actual del Préstamo</h3>
                    <p style="margin: 0; color: #854d0e; font-size: 0.9rem;">
                        Indica cuánto ha pagado el cliente hasta ahora y cuántas cuotas ya ha completado.
                    </p>
                </div>

                <div class="grid">
                    <!-- Cuotas Ya Pagadas -->
                    <div class="form-group">
                        <label>Número de Cuotas Ya Pagadas *</label>
                        <input type="number" name="payments_made" min="0" required placeholder="Ej. 5">
                        <small style="color: #64748b;">¿Cuántas cuotas ya pagó el cliente?</small>
                    </div>

                    <!-- Monto Total Ya Pagado -->
                    <div class="form-group">
                        <label>Monto Total Ya Pagado ($) *</label>
                        <input type="number" name="total_paid" step="0.01" min="0" required placeholder="Ej. 5000">
                        <small style="color: #64748b;">¿Cuánto dinero ha pagado en total?</small>
                    </div>
                </div>

                <div
                    style="background: #e0f2fe; border: 1px solid #bae6fd; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                    <h3 style="color: #075985; margin: 0 0 0.5rem 0;">ℹ️ ¿Cómo funciona?</h3>
                    <ul style="margin: 0; padding-left: 1.5rem; color: #075985; font-size: 0.9rem;">
                        <li>El sistema calculará el total a pagar según el monto, interés y plazo</li>
                        <li>Creará el calendario completo de pagos</li>
                        <li>Marcará como "pagadas" las cuotas que ya completó el cliente</li>
                        <li>Las cuotas restantes quedarán pendientes para seguimiento</li>
                    </ul>
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">
                    📥 Importar Préstamo al Sistema
                </button>

                <a href="index.php" class="btn btn-secondary"
                    style="width: 100%; margin-top: 0.5rem; text-align: center;">
                    Cancelar
                </a>
            </form>
        </div>
    </div>
</body>

</html>
