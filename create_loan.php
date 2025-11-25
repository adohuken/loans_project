<?php
require 'auth.php';
require 'db.php';
$clients = $pdo->query("SELECT * FROM clients")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Préstamo - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=2.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php" class="active">Nuevo Préstamo</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <h2>Asignar Nuevo Préstamo</h2>
            <form action="save_loan.php" method="POST" id="loanForm">
                <div class="grid">
                    <div class="form-group">
                        <label>Cliente</label>
                        <select name="client_id" required>
                            <option value="">Seleccione un cliente...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>">
                                    <?= htmlspecialchars($client['cedula'] . ' - ' . $client['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Monto del Préstamo ($)</label>
                        <input type="number" name="amount" id="amount" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Interés Mensual (%)</label>
                        <input type="number" name="interest_rate" id="interest" step="0.1" required
                            placeholder="Ej. 10">
                    </div>

                    <div class="form-group">
                        <label>Duración (Meses)</label>
                        <input type="number" name="duration_months" id="duration" required placeholder="Ej. 2">
                    </div>

                    <div class="form-group">
                        <label>Frecuencia de Pago</label>
                        <select name="frequency" id="frequency" required>
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quincenal</option>
                            <option value="monthly">Mensual</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Inicio</label>
                        <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="card" style="background: #f1f5f9; border: 1px solid #e2e8f0; margin-top: 1rem;">
                    <h3>Resumen del Cálculo</h3>
                    <p>Total a Pagar: <strong id="totalAmount">$0.00</strong></p>
                    <p>Pagos Estimados: <strong id="numPayments">0</strong></p>
                    <p>Monto por Cuota: <strong id="installmentAmount">$0.00</strong></p>
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Crear Préstamo</button>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('loanForm');
        const amountIn = document.getElementById('amount');
        const interestIn = document.getElementById('interest');
        const durationIn = document.getElementById('duration');
        const frequencyIn = document.getElementById('frequency');

        function calculate() {
            const amount = parseFloat(amountIn.value) || 0;
            const interestRate = parseFloat(interestIn.value) || 0;
            const months = parseFloat(durationIn.value) || 0;
            const frequency = frequencyIn.value;

            if (amount > 0 && months > 0) {
                // Logic: Percentage added to amount lent and divided by time
                // Total Interest = Amount * (Rate/100) * Months
                const totalInterest = amount * (interestRate / 100) * months;
                const total = amount + totalInterest;

                let numPayments = 0;
                if (frequency === 'weekly') numPayments = months * 4; // Approx
                if (frequency === 'biweekly') numPayments = months * 2;
                if (frequency === 'monthly') numPayments = months * 1;

                const installment = total / numPayments;

                document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
                document.getElementById('numPayments').textContent = numPayments;
                document.getElementById('installmentAmount').textContent = '$' + installment.toFixed(2);
            }
        }

        form.addEventListener('input', calculate);
    </script>
</body>

</html>