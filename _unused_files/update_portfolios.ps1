$file = 'portfolios.php'
$content = Get-Content $file -Raw -Encoding UTF8

$oldButtons = @'
                                    <td>
                                        <a href="portfolios.php?edit=<?= $portfolio['id'] ?>" class="btn btn-sm"
                                            style="background-color: #f59e0b; margin-right: 0.5rem;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($portfolio['client_count'] == 0): ?>
                                            <a href="portfolios.php?delete=<?= $portfolio['id'] ?>"
                                                class="btn btn-sm btn-secondary"
                                                onclick="return confirm('¿Seguro que deseas eliminar esta cartera?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 0.85rem;"><i class="fas fa-lock"></i> En
                                                uso</span>
                                        <?php endif; ?>
                                    </td>
'@

$newButtons = @'
                                    <td>
                                        <a href="portfolios.php?edit=<?= $portfolio['id'] ?>" class="btn"
                                            style="background-color: #fff; color: #1e293b; border: 1px solid #cbd5e1; padding: 0.25rem 0.75rem; font-size: 0.85rem; margin-right: 0.5rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php if ($portfolio['client_count'] == 0): ?>
                                            <a href="portfolios.php?delete=<?= $portfolio['id'] ?>" class="btn"
                                                style="background-color: #fff; color: #dc2626; border: 1px solid #dc2626; padding: 0.25rem 0.75rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.25rem;"
                                                onclick="return confirm('¿Seguro que deseas eliminar esta cartera?')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 0.85rem;"><i class="fas fa-lock"></i> En
                                                uso</span>
                                        <?php endif; ?>
                                    </td>
'@

# Normalizar saltos de línea
$content = $content.Replace("`r`n", "`n")
$oldButtons = $oldButtons.Replace("`r`n", "`n")
$newButtons = $newButtons.Replace("`r`n", "`n")

if ($content.Contains($oldButtons)) {
    $content = $content.Replace($oldButtons, $newButtons)
    $content | Out-File $file -Encoding UTF8 -NoNewline
    Write-Host "Botones actualizados correctamente."
} else {
    Write-Host "No se encontró el bloque de botones para reemplazar."
}
