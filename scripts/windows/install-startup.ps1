# Installs Rynude as a background app:
# - shortcut in the Startup folder (auto-start at login)
# - shortcut in the Start Menu (launch manually like a normal app)
# - starts the tray launcher right now
# Run:  powershell -ExecutionPolicy Bypass -File install-startup.ps1

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$appRoot   = (Resolve-Path (Join-Path $scriptDir '..\..')).Path
$vbs       = Join-Path $scriptDir 'Rynude.vbs'
$iconPath  = Join-Path $appRoot 'public\favicon.ico'

$ws = New-Object -ComObject WScript.Shell
$targets = @(
    (Join-Path ([Environment]::GetFolderPath('Startup')) 'Rynude.lnk'),
    (Join-Path ([Environment]::GetFolderPath('StartMenu')) 'Programs\Rynude.lnk')
)
foreach ($lnkPath in $targets) {
    $lnk = $ws.CreateShortcut($lnkPath)
    $lnk.TargetPath = 'wscript.exe'
    $lnk.Arguments = '"' + $vbs + '"'
    $lnk.WorkingDirectory = $appRoot
    $lnk.Description = 'Rynude background server'
    if (Test-Path $iconPath) { $lnk.IconLocation = $iconPath }
    $lnk.Save()
    Write-Host "Shortcut dibuat: $lnkPath"
}

Start-Process wscript.exe -ArgumentList ('"' + $vbs + '"')
Write-Host 'Rynude berjalan di background - cek ikon di system tray (^).'
