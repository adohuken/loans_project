<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: backup.php');
    exit;
}

if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Error al subir el archivo. Por favor intente nuevamente.';
    header('Location: backup.php');
    exit;
}

try {
    // Read and decode JSON file
    $json_content = file_get_contents($_FILES['backup_file']['tmp_name']);
    $backup = json_decode($json_content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('El archivo no es un JSON válido.');
    }

    // Validate backup structure
    if (!isset($backup['metadata']) || !isset($backup['data'])) {
        throw new Exception('El archivo de backup no tiene la estructura correcta.');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Clear existing data (in reverse order to respect foreign keys)
    $pdo->exec("DELETE FROM payments");
    $pdo->exec("DELETE FROM loans");
    $pdo->exec("DELETE FROM clients");
    $pdo->exec("DELETE FROM users");
    $pdo->exec("DELETE FROM settings");

    // Import Settings
    if (!empty($backup['data']['settings'])) {
        $stmt = $pdo->prepare("INSERT INTO settings (id, company_name, currency_symbol, logo_path, late_fee_percentage, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($backup['data']['settings'] as $row) {
            $stmt->execute([
                $row['id'] ?? 1,
                $row['company_name'] ?? 'Mi Empresa',
                $row['currency_symbol'] ?? '$',
                $row['logo_path'] ?? '',
                $row['late_fee_percentage'] ?? 0,
                $row['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
    }

    // Import Users
    if (!empty($backup['data']['users'])) {
        $stmt = $pdo->prepare("INSERT INTO users (id, username, password, created_at) VALUES (?, ?, ?, ?)");
        foreach ($backup['data']['users'] as $row) {
            $stmt->execute([
                $row['id'],
                $row['username'],
                $row['password'],
                $row['created_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
    }

    // Import Clients
    if (!empty($backup['data']['clients'])) {
        $stmt = $pdo->prepare("INSERT INTO clients (id, cedula, name, phone, address, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($backup['data']['clients'] as $row) {
            $stmt->execute([
                $row['id'],
                $row['cedula'] ?? null,
                $row['name'],
                $row['phone'] ?? null,
                $row['address'] ?? null,
                $row['created_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
    }

    // Import Loans
    if (!empty($backup['data']['loans'])) {
        $stmt = $pdo->prepare("INSERT INTO loans (id, client_id, amount, interest_rate, frequency, duration_months, total_amount, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($backup['data']['loans'] as $row) {
            $stmt->execute([
                $row['id'],
                $row['client_id'],
                $row['amount'],
                $row['interest_rate'],
                $row['frequency'],
                $row['duration_months'],
                $row['total_amount'],
                $row['start_date'],
                $row['status'] ?? 'active'
            ]);
        }
    }

    // Import Payments
    if (!empty($backup['data']['payments'])) {
        $stmt = $pdo->prepare("INSERT INTO payments (id, loan_id, amount_due, due_date, paid_amount, paid_date, status, late_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($backup['data']['payments'] as $row) {
            $stmt->execute([
                $row['id'],
                $row['loan_id'],
                $row['amount_due'],
                $row['due_date'],
                $row['paid_amount'] ?? 0,
                $row['paid_date'] ?? null,
                $row['status'] ?? 'pending',
                $row['late_fee'] ?? 0
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = 'Backup importado exitosamente. Todos los datos han sido restaurados.';
    header('Location: backup.php');
    exit;

} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'Error al importar el backup: ' . $e->getMessage();
    header('Location: backup.php');
    exit;
}
