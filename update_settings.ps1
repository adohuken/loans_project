$file = 'settings.php'
$content = Get-Content $file -Raw -Encoding UTF8

# 1. Update PHP Logic
$oldPhp = @'
    $company_name = $_POST['company_name'];
    $currency_symbol = $_POST['currency_symbol'];

    // Handle Logo Upload
    $logo_path = $_POST['current_logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["logo"]["name"]);
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_path = $target_file;
        }
    }

    $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, currency_symbol = ?, logo_path = ? WHERE id = 1");
    $stmt->execute([$company_name, $currency_symbol, $logo_path]);
'@

$newPhp = @'
    $company_name = $_POST['company_name'];
    $currency_symbol = $_POST['currency_symbol'];
    $company_address = $_POST['company_address'];
    $company_phone = $_POST['company_phone'];
    $receipt_footer = $_POST['receipt_footer'];

    // Handle Logo Upload
    $logo_path = $_POST['current_logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["logo"]["name"]);
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_path = $target_file;
        }
    }

    $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, currency_symbol = ?, logo_path = ?, company_address = ?, company_phone = ?, receipt_footer = ? WHERE id = 1");
    $stmt->execute([$company_name, $currency_symbol, $logo_path, $company_address, $company_phone, $receipt_footer]);
'@

# Normalize line endings for replacement
$content = $content.Replace("`r`n", "`n")
$oldPhp = $oldPhp.Replace("`r`n", "`n")
$newPhp = $newPhp.Replace("`r`n", "`n")

if ($content.Contains($oldPhp)) {
    $content = $content.Replace($oldPhp, $newPhp)
    Write-Host "PHP logic updated."
} else {
    Write-Host "PHP logic block not found."
}

# 2. Update HTML Form
$oldHtml = @'
                <div class="form-group">
                    <label>Símbolo de Moneda</label>
                    <input type="text" name="currency_symbol"
                        value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required>
                </div>
'@

$newHtml = @'
                <div class="form-group">
                    <label>Símbolo de Moneda</label>
                    <input type="text" name="currency_symbol"
                        value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Dirección de la Empresa</label>
                    <input type="text" name="company_address"
                        value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="company_phone"
                        value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Pie de Recibo (Mensaje)</label>
                    <textarea name="receipt_footer" rows="2" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                </div>
'@

$oldHtml = $oldHtml.Replace("`r`n", "`n")
$newHtml = $newHtml.Replace("`r`n", "`n")

if ($content.Contains($oldHtml)) {
    $content = $content.Replace($oldHtml, $newHtml)
    Write-Host "HTML form updated."
} else {
    Write-Host "HTML form block not found."
}

$content | Out-File $file -Encoding UTF8 -NoNewline
