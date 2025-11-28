# Script para actualizar create_loan.php con header moderno
$file = 'create_loan.php'
$content = Get-Content $file -Raw -Encoding UTF8

# 1. Agregar $company_name y $logo_path a las variables
$oldVars = '$default_interest = $settings[''interest_rate''] ?? 15;'
$newVars = @'
$default_interest = $settings['interest_rate'] ?? 15;
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
'@
$content = $content.Replace($oldVars, $newVars)

# 2. Quitar valores por defecto de interés y plazo
$content = $content.Replace('value="<?= $default_interest ?>"', '')
$content = $content.Replace('value="1"', '')

# 3. Reemplazar el header viejo con el moderno
$oldHeader = @'
        <header>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <h1><i class="fas fa-plus-circle"></i> Sistema de Préstamos</h1>
            </div>
            <nav>
'@

$newHeader = @'
        <header style="flex-direction: column; gap: 1.5rem;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                <?php if (!empty($logo_path)): ?>
                    <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo"
                        style="height: 60px; width: auto; object-fit: contain;">
                <?php endif; ?>
                <h1 style="margin: 0; font-size: 2rem;"><?= htmlspecialchars($company_name) ?></h1>
            </div>
            <nav style="justify-content: center;">
'@

$content = $content.Replace($oldHeader, $newHeader)

# 4. Agregar mobile.css
$content = $content.Replace(
    '<link rel="stylesheet" href="style.css?v=3.0">',
    "<link rel=`"stylesheet`" href=`"style.css?v=3.0`">`r`n    <link rel=`"stylesheet`" href=`"mobile.css?v=1.0`">"
)

# Guardar
$content | Out-File $file -Encoding UTF8 -NoNewline
Write-Host "create_loan.php actualizado con header moderno"
