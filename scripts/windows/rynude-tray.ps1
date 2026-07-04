# Rynude tray launcher.
# Starts the Laravel server hidden in the background and puts an icon in the
# system tray with Open / Restart / Exit actions. Launch via Rynude.vbs so no
# console window appears.

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
$script:serverProc = $null

function Test-ServerUp {
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $client.Connect('127.0.0.1', $port)
        $client.Close()
        return $true
    } catch { return $false }
}

function Start-RynudeServer {
    if (Test-ServerUp) { return }

    # A stale hot file makes @vite point at a dev server that isn't running.
    Remove-Item (Join-Path $appRoot 'public\hot') -ErrorAction SilentlyContinue

    Start-Process php -ArgumentList 'artisan','migrate','--force' `
        -WorkingDirectory $appRoot -WindowStyle Hidden -Wait
    Start-Process php -ArgumentList 'artisan','optimize' `
        -WorkingDirectory $appRoot -WindowStyle Hidden -Wait
    $script:serverProc = Start-Process php `
        -ArgumentList 'artisan','serve','--host=127.0.0.1',"--port=$port" `
        -WorkingDirectory $appRoot -WindowStyle Hidden -PassThru
}

function Stop-RynudeServer {
    if ($script:serverProc -and -not $script:serverProc.HasExited) {
        # artisan serve spawns a child php -S process; /T kills the whole tree.
        & taskkill /PID $script:serverProc.Id /T /F 2>$null | Out-Null
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
    Start-RynudeServer
    $trayIcon.ShowBalloonTip(2000, 'Rynude', 'Server di-restart.', [System.Windows.Forms.ToolTipIcon]::Info)
})

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
    Start-RynudeServer
    $trayIcon.ShowBalloonTip(2000, 'Rynude', "Server jalan di $url", [System.Windows.Forms.ToolTipIcon]::Info)
} catch {
    $trayIcon.ShowBalloonTip(5000, 'Rynude', "Gagal start server: $($_.Exception.Message)", [System.Windows.Forms.ToolTipIcon]::Error)
}

[System.Windows.Forms.Application]::Run()
$mutex.ReleaseMutex()
