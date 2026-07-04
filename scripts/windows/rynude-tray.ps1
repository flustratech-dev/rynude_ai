# Rynude tray launcher.
# Starts the Laravel server hidden in the background and puts an icon in the
# system tray with Open / Restart / Exit actions. Launch via Rynude.vbs so no
# console window appears. All server output goes to storage/logs/rynude-launcher.log.

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

# Only one tray instance at a time.
$mutex = New-Object System.Threading.Mutex($false, 'Global\RynudeTrayMutex')
if (-not $mutex.WaitOne(0, $false)) { exit }

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$appRoot   = (Resolve-Path (Join-Path $scriptDir '..\..')).Path
$port      = 8080
$url       = "http://localhost:$port"
$logFile   = Join-Path $appRoot 'storage\logs\rynude-launcher.log'
$script:serverProc = $null

function Write-Log([string]$msg) {
    Add-Content -Path $logFile -Value ("[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $msg)
}

function Test-Port([int]$p) {
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $client.Connect('127.0.0.1', $p)
        $client.Close()
        return $true
    } catch { return $false }
}

function Test-ServerUp { Test-Port $port }

# The hidden PowerShell started at login may not have the PATH your terminal
# has (Laragon/XAMPP shells add PHP themselves), so probe common locations.
function Find-Php {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $laragon = Get-ChildItem 'C:\laragon\bin\php\*\php.exe' -ErrorAction SilentlyContinue |
        Sort-Object FullName -Descending | Select-Object -First 1
    if ($laragon) { return $laragon.FullName }
    foreach ($candidate in @('C:\xampp\php\php.exe', 'C:\php\php.exe', 'C:\tools\php\php.exe')) {
        if (Test-Path $candidate) { return $candidate }
    }
    return $null
}

function Invoke-Artisan([string]$php, [string]$artisanArgs) {
    # cmd /c so stdout+stderr reach the log even from a hidden window.
    Start-Process cmd -ArgumentList '/c', "`"$php`" artisan $artisanArgs >> `"$logFile`" 2>&1" `
        -WorkingDirectory $appRoot -WindowStyle Hidden -Wait
}

function Start-RynudeServer {
    if (Test-ServerUp) {
        Write-Log "Server sudah jalan di port $port, memakai yang ada."
        return $true
    }

    $php = Find-Php
    if (-not $php) {
        Write-Log 'GAGAL: php.exe tidak ditemukan di PATH maupun lokasi umum (Laragon/XAMPP/C:\php).'
        $trayIcon.ShowBalloonTip(8000, 'Rynude', 'php.exe tidak ditemukan. Tambahkan PHP ke PATH sistem.', [System.Windows.Forms.ToolTipIcon]::Error)
        return $false
    }
    Write-Log "Memakai PHP: $php"

    if (-not (Test-Port 3306)) {
        Write-Log 'PERINGATAN: MySQL (port 3306) belum jalan. Aplikasi akan error database sampai MySQL hidup.'
        $trayIcon.ShowBalloonTip(8000, 'Rynude', 'MySQL belum jalan (port 3306). Nyalakan MySQL/Laragon dulu.', [System.Windows.Forms.ToolTipIcon]::Warning)
    }

    # A stale hot file makes @vite point at a dev server that isn't running.
    Remove-Item (Join-Path $appRoot 'public\hot') -ErrorAction SilentlyContinue

    Invoke-Artisan $php 'migrate --force'
    Invoke-Artisan $php 'optimize'
    $script:serverProc = Start-Process cmd `
        -ArgumentList '/c', "`"$php`" artisan serve --host=127.0.0.1 --port=$port >> `"$logFile`" 2>&1" `
        -WorkingDirectory $appRoot -WindowStyle Hidden -PassThru

    for ($i = 0; $i -lt 30 -and -not (Test-ServerUp); $i++) { Start-Sleep -Milliseconds 500 }

    if (Test-ServerUp) {
        Write-Log "Server jalan di $url"
        return $true
    }
    Write-Log 'GAGAL: server tidak merespons setelah 15 detik. Lihat pesan error di atas.'
    $trayIcon.ShowBalloonTip(8000, 'Rynude', "Server gagal start. Cek log lewat menu tray.", [System.Windows.Forms.ToolTipIcon]::Error)
    return $false
}

function Stop-RynudeServer {
    if ($script:serverProc -and -not $script:serverProc.HasExited) {
        # artisan serve spawns child php processes; /T kills the whole tree.
        & taskkill /PID $script:serverProc.Id /T /F 2>$null | Out-Null
        Write-Log 'Server dihentikan.'
    }
    $script:serverProc = $null
}

$trayIcon = New-Object System.Windows.Forms.NotifyIcon
$iconPath = Join-Path $appRoot 'public\favicon.ico'
try {
    $trayIcon.Icon = New-Object System.Drawing.Icon($iconPath)
} catch {
    $trayIcon.Icon = [System.Drawing.SystemIcons]::Application
}
$trayIcon.Text = 'Rynude'

$menu = New-Object System.Windows.Forms.ContextMenuStrip

$openItem = $menu.Items.Add('Buka Rynude')
$openItem.add_Click({ Start-Process $url })

$restartItem = $menu.Items.Add('Restart server')
$restartItem.add_Click({
    Stop-RynudeServer
    if (Start-RynudeServer) {
        $trayIcon.ShowBalloonTip(2000, 'Rynude', 'Server di-restart.', [System.Windows.Forms.ToolTipIcon]::Info)
    }
})

$logItem = $menu.Items.Add('Lihat log')
$logItem.add_Click({ Start-Process notepad $logFile })

$menu.Items.Add('-') | Out-Null
$exitItem = $menu.Items.Add('Keluar')
$exitItem.add_Click({
    Stop-RynudeServer
    $trayIcon.Visible = $false
    $trayIcon.Dispose()
    [System.Windows.Forms.Application]::Exit()
})

$trayIcon.ContextMenuStrip = $menu
$trayIcon.add_DoubleClick({ Start-Process $url })
$trayIcon.Visible = $true

try {
    Write-Log '--- Tray launcher start ---'
    if (Start-RynudeServer) {
        $trayIcon.ShowBalloonTip(2000, 'Rynude', "Server jalan di $url", [System.Windows.Forms.ToolTipIcon]::Info)
    }
} catch {
    Write-Log "GAGAL: $($_.Exception.Message)"
    $trayIcon.ShowBalloonTip(8000, 'Rynude', "Gagal start server: $($_.Exception.Message)", [System.Windows.Forms.ToolTipIcon]::Error)
}

[System.Windows.Forms.Application]::Run()
$mutex.ReleaseMutex()
