# rynude-tray.ps1
# Script untuk menjalankan Rynude di background dan menampilkan icon di Taskbar (System Tray)

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

# Folder project = folder tempat script ini berada (di mesin user: ~\.rynude_ai)
$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$portFile = Join-Path $projectDir ".rynude-port"

# Pastikan kita berada di direktori project
Set-Location -Path $projectDir

# Jika ada port file lama, hapus
if (Test-Path $portFile) {
    Remove-Item $portFile
}

# Mulai proses node cli.js --silent di background
$nodeProcess = Start-Process -FilePath "node" -ArgumentList "cli.js", "--silent" -WindowStyle Hidden -PassThru -WorkingDirectory $projectDir

# Buat System Tray Icon
$notifyIcon = New-Object System.Windows.Forms.NotifyIcon

# Menggunakan Icon Rynude asli dari folder public
$iconPath = Join-Path $projectDir "public\favicon.ico"
if (Test-Path $iconPath) {
    $notifyIcon.Icon = New-Object System.Drawing.Icon($iconPath)
} else {
    $notifyIcon.Icon = [System.Drawing.Icon]::ExtractAssociatedIcon("C:\Windows\System32\cmd.exe")
}
$notifyIcon.Text = "Rynude AI Server"
$notifyIcon.Visible = $true

# Buat Context Menu (Klik Kanan)
$contextMenu = New-Object System.Windows.Forms.ContextMenu

# Menu: Buka Rynude (Browser)
$openMenuItem = New-Object System.Windows.Forms.MenuItem
$openMenuItem.Text = "Buka Rynude (Browser)"
$openMenuItem.add_Click({
    if (Test-Path $portFile) {
        $port = Get-Content $portFile
        Start-Process "http://localhost:$port"
    } else {
        [System.Windows.Forms.MessageBox]::Show("Server masih dalam proses startup. Silakan coba beberapa detik lagi.", "Harap Tunggu", [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Information)
    }
})
$contextMenu.MenuItems.Add($openMenuItem)

# Fungsi Double Click pada Icon
$notifyIcon.add_DoubleClick({
    if (Test-Path $portFile) {
        $port = Get-Content $portFile
        Start-Process "http://localhost:$port"
    } else {
        [System.Windows.Forms.MessageBox]::Show("Server masih dalam proses startup. Silakan coba beberapa detik lagi.", "Harap Tunggu", [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Information)
    }
})

# Menu Pembatas
$separatorItem = New-Object System.Windows.Forms.MenuItem
$separatorItem.Text = "-"
$contextMenu.MenuItems.Add($separatorItem)

# Menu: Tutup Server
$exitMenuItem = New-Object System.Windows.Forms.MenuItem
$exitMenuItem.Text = "Tutup Server"
$exitMenuItem.add_Click({
    # Sembunyikan icon
    $notifyIcon.Visible = $false
    
    # Hentikan proses Node.js
    if ($nodeProcess -and !$nodeProcess.HasExited) {
        Stop-Process -Id $nodeProcess.Id -Force
    }
    
    # Hentikan proses PHP yang ditinggalkan
    # (Karena kita Force kill node, proses anak php artisan mungkin tertinggal)
    Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -match "artisan" } | Stop-Process -Force -ErrorAction SilentlyContinue
    
    # Hapus file port
    if (Test-Path $portFile) {
        Remove-Item $portFile
    }

    # Keluar dari aplikasi System Tray
    [System.Windows.Forms.Application]::Exit()
})
$contextMenu.MenuItems.Add($exitMenuItem)

# Pasang context menu ke icon
$notifyIcon.ContextMenu = $contextMenu

# Tampilkan notifikasi saat pertama kali jalan
$notifyIcon.BalloonTipTitle = "Rynude AI Aktif"
$notifyIcon.BalloonTipText = "Server berjalan di background. Klik kanan icon ini untuk membuka browser atau menutup server."
$notifyIcon.ShowBalloonTip(3000)

# Jalankan Application Loop agar tray icon tetap hidup
[System.Windows.Forms.Application]::Run()

# Pastikan resources dibersihkan saat script ditutup paksa
$notifyIcon.Dispose()
