<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $currency_symbol = $_POST['currency_symbol'];
    $company_address = $_POST['company_address'];
    $company_phone = $_POST['company_phone'];
    $receipt_footer = $_POST['receipt_footer'];

    // Handle File Upload
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logo_path = $targetPath;
        }
    }

    try {
        if ($logo_path) {
            $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, currency_symbol = ?, logo_path = ?, company_address = ?, company_phone = ?, receipt_footer = ? WHERE id = 1");
            $stmt->execute([$company_name, $currency_symbol, $logo_path, $company_address, $company_phone, $receipt_footer]);
        } else {
            $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, currency_symbol = ?, company_address = ?, company_phone = ?, receipt_footer = ? WHERE id = 1");
            $stmt->execute([$company_name, $currency_symbol, $company_address, $company_phone, $receipt_footer]);
        }

        header("Location: settings.php");
    } catch (PDOException $e) {
        die("Error al guardar configuración: " . $e->getMessage());
    }
}
?>