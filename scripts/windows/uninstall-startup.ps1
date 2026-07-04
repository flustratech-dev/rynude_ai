# Removes the Rynude startup + Start Menu shortcuts.
# The running tray icon (if any) keeps running until you pick "Keluar" on it.
$targets = @(
    (Join-Path ([Environment]::GetFolderPath('Startup')) 'Rynude.lnk'),
    (Join-Path ([Environment]::GetFolderPath('StartMenu')) 'Programs\Rynude.lnk')
)
foreach ($lnkPath in $targets) {
    if (Test-Path $lnkPath) {
        Remove-Item $lnkPath
        Write-Host "Dihapus: $lnkPath"
    }
}
Write-Host 'Selesai. Tutup ikon tray lewat menu "Keluar" jika masih jalan.'
