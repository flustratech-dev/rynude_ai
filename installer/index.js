#!/usr/bin/env node
import fs from 'fs-extra';
import path from 'path';
import os from 'os';
import { execSync } from 'child_process';
import prompts from 'prompts';
import ora from 'ora';
import chalk from 'chalk';

const REPO_URL = 'https://github.com/flustratech-dev/rynude_ai.git';
const INSTALL_DIR = path.join(os.homedir(), '.rynude_ai');
const IS_WINDOWS = os.platform() === 'win32';
const IS_MAC = os.platform() === 'darwin';

// stdin sengaja 'ignore' agar perintah anak yang menunggu input (mis. "pause"
// di .bat, atau prompt composer) langsung menerima EOF dan tidak menggantung.
function sh(cmd, opts = {}) {
    return execSync(cmd, { stdio: ['ignore', 'pipe', 'pipe'], ...opts });
}

// Ambil pesan error asli dari proses yang gagal supaya bisa ditampilkan ke user.
function execDetail(e) {
    const out = [e.stderr, e.stdout]
        .map(b => (b ? b.toString().trim() : ''))
        .filter(Boolean)
        .join('\n');
    return out || e.message;
}

async function checkRequirements() {
    const reqs = [
        {
            cmd: 'php', name: 'PHP (>= 8.2)',
            win: 'Download: https://windows.php.net/download/',
            mac: 'Jalankan: brew install php',
            linux: 'Jalankan: sudo apt install php php-sqlite3 php-xml php-curl php-mbstring'
        },
        {
            cmd: 'composer', name: 'Composer',
            win: 'Download: https://getcomposer.org/Composer-Setup.exe',
            mac: 'Jalankan: brew install composer',
            linux: 'Lihat: https://getcomposer.org/download/'
        },
        {
            cmd: 'git', name: 'Git',
            win: 'Download: https://git-scm.com/downloads',
            mac: 'Jalankan: xcode-select --install (atau brew install git)',
            linux: 'Jalankan: sudo apt install git'
        },
        {
            cmd: 'npm', name: 'Node.js/NPM',
            win: 'Download: https://nodejs.org/',
            mac: 'Jalankan: brew install node',
            linux: 'Download: https://nodejs.org/'
        }
    ];

    let hasMissing = false;
    let statusMessage = '\n';

    for (const req of reqs) {
        try {
            execSync(`${req.cmd} --version`, { stdio: 'ignore' });
            statusMessage += chalk.green(`[✓] ${req.name} (Ditemukan)\n`);
        } catch (e) {
            hasMissing = true;
            const hint = IS_WINDOWS ? req.win : (IS_MAC ? req.mac : req.linux);
            statusMessage += chalk.red(`[x] ${req.name} (Tidak Ditemukan) -> ${hint}\n`);
        }
    }

    if (hasMissing) {
        console.error(chalk.red.bold('\n❌ Instalasi Dihentikan: Komponen Hilang!'));
        console.error(chalk.white('Rynude AI membutuhkan beberapa alat tambahan di komputer Anda:'));
        console.error(statusMessage);
        if (IS_MAC) {
            try {
                execSync('brew --version', { stdio: 'ignore' });
            } catch (e) {
                console.error(chalk.yellow('Homebrew belum terpasang di Mac Anda. Install dulu dengan:'));
                console.error(chalk.white('  /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"\n'));
            }
        }
        console.error(chalk.yellow.bold('Silakan install komponen yang disilang merah, lalu BUKA TERMINAL BARU dan ketik ulang "npx install-rynude@latest".\n'));
        process.exit(1);
    }
}

async function run() {
    console.log(chalk.cyan.bold('\n🚀 Selamat datang di Rynude AI Auto-Installer\n'));

    await checkRequirements();

    let hasBackup = false;
    const backupDir = path.join(os.homedir(), '.rynude_backup_tmp');
    const dbPath = path.join(INSTALL_DIR, 'database', 'database.sqlite');
    const envPath = path.join(INSTALL_DIR, '.env');

    if (fs.existsSync(INSTALL_DIR)) {
        const response = await prompts({
            type: 'confirm',
            name: 'overwrite',
            message: `Aplikasi Rynude AI sudah terinstal. Apakah Anda ingin melakukan UPDATE ke versi terbaru? (Data obrolan & API Key Anda akan aman)`,
            initial: true
        });

        if (!response.overwrite) {
            console.log(chalk.yellow('Update dibatalkan.'));
            process.exit(0);
        }

        const spinner = ora('Mem-backup data Anda (Database & Konfigurasi)...').start();
        fs.ensureDirSync(backupDir);
        if (fs.existsSync(dbPath)) fs.copySync(dbPath, path.join(backupDir, 'database.sqlite'));
        if (fs.existsSync(envPath)) fs.copySync(envPath, path.join(backupDir, '.env'));
        hasBackup = true;

        fs.removeSync(INSTALL_DIR);
        spinner.succeed('Data lama dibackup dan siap diupdate.');
    } else if (fs.existsSync(path.join(backupDir, '.env'))) {
        // Jika instalasi sebelumnya gagal di tengah jalan (folder utama sudah terhapus
        // tapi backup masih ada di folder tmp), gunakan backup tersebut agar APP_KEY tidak hilang.
        hasBackup = true;
        console.log(chalk.green('Backup dari instalasi yang terputus ditemukan, akan dilanjutkan.'));
    }

    console.log(chalk.gray(`\nMenginstal Rynude AI ke folder tersembunyi: ${INSTALL_DIR}`));

    // 1. Clone
    const cloneSpinner = ora('Mengunduh Rynude AI (Clone)...').start();
    try {
        sh(`git clone --depth=1 ${REPO_URL} "${INSTALL_DIR}"`);
        cloneSpinner.succeed('Berhasil mengunduh source code.');
    } catch (e) {
        cloneSpinner.fail('Gagal mengunduh source code.');
        console.error(chalk.red(execDetail(e)));
        process.exit(1);
    }

    // 2. Composer
    const composerSpinner = ora('Menginstal dependensi backend (Composer)...').start();
    try {
        sh('composer install --no-interaction --optimize-autoloader', { cwd: INSTALL_DIR });
        composerSpinner.succeed('Dependensi backend terinstal.');
    } catch (e) {
        composerSpinner.fail('Gagal menginstal dependensi backend.');
        console.error(chalk.red(execDetail(e)));
        if (IS_MAC) {
            console.error(chalk.yellow('Tips Mac: pastikan PHP dari Homebrew terbaru — jalankan "brew upgrade php composer" lalu coba lagi.'));
        }
        process.exit(1);
    }

    // 3. NPM
    const npmSpinner = ora('Menginstal dependensi frontend (NPM)...').start();
    try {
        sh('npm install', { cwd: INSTALL_DIR });
        npmSpinner.succeed('Dependensi frontend terinstal.');
    } catch (e) {
        npmSpinner.fail('Gagal menginstal dependensi frontend.');
        console.error(chalk.red(execDetail(e)));
        process.exit(1);
    }

    // 4. ENV Setup
    const envSpinner = ora('Menyiapkan konfigurasi & database...').start();
    try {
        if (hasBackup && fs.existsSync(path.join(backupDir, '.env')) && fs.existsSync(path.join(backupDir, 'database.sqlite'))) {
            // Restore from backup
            fs.copySync(path.join(backupDir, '.env'), path.join(INSTALL_DIR, '.env'));
            fs.ensureDirSync(path.join(INSTALL_DIR, 'database'));
            fs.copySync(path.join(backupDir, 'database.sqlite'), path.join(INSTALL_DIR, 'database', 'database.sqlite'));
            fs.removeSync(backupDir); // clean up tmp

            sh('php artisan migrate --force', { cwd: INSTALL_DIR });
            sh('php artisan db:seed --class=AiModelSeeder --force', { cwd: INSTALL_DIR });
            sh('php artisan optimize:clear', { cwd: INSTALL_DIR });
            envSpinner.succeed('Konfigurasi dan database lama Anda berhasil dipulihkan (Aman!).');
        } else {
            // Fresh Install
            fs.copySync(path.join(INSTALL_DIR, '.env.example'), path.join(INSTALL_DIR, '.env'));
            let envContent = fs.readFileSync(path.join(INSTALL_DIR, '.env'), 'utf-8');
            envContent = envContent.replace(/DB_CONNECTION=.*/, 'DB_CONNECTION=sqlite');
            fs.writeFileSync(path.join(INSTALL_DIR, '.env'), envContent);

            fs.ensureFileSync(path.join(INSTALL_DIR, 'database', 'database.sqlite'));
            sh('php artisan key:generate', { cwd: INSTALL_DIR });
            try { sh('php artisan storage:link', { cwd: INSTALL_DIR }); } catch (e) { /* symlink sudah ada — bukan masalah */ }
            sh('php artisan migrate:fresh --seed', { cwd: INSTALL_DIR });
            sh('php artisan optimize:clear', { cwd: INSTALL_DIR });
            envSpinner.succeed('Konfigurasi dan database siap.');
        }
    } catch (e) {
        envSpinner.fail('Gagal menyiapkan database.');
        console.error(chalk.red(execDetail(e)));
        process.exit(1);
    }

    // 5. Global Command Setup
    const globalSpinner = ora('Mengatur global command (rynude)...').start();
    try {
        if (IS_WINDOWS) {
            sh('cmd.exe /c setup-global.bat', { cwd: INSTALL_DIR });
        } else {
            // setup-global.sh memasang wrapper di ~/.local/bin, membuat file rc
            // bila belum ada (macOS baru tidak punya ~/.zshrc), dan mengatur PATH.
            sh('bash setup-global.sh', { cwd: INSTALL_DIR });
        }
        globalSpinner.succeed('Global command berhasil diatur.');
    } catch (e) {
        globalSpinner.fail('Gagal mengatur global command.');
        console.error(chalk.gray(execDetail(e)));
        console.error(chalk.yellow(`Silakan jalankan setup-global secara manual di folder: ${INSTALL_DIR}`));
    }

    console.log(chalk.green.bold('\n🎉 Instalasi Rynude AI Berhasil Selesai!\n'));

    if (!IS_WINDOWS) {
        console.log(chalk.bgYellow.black.bold(' ⚠️ PENTING UNTUK MAC/LINUX '));
        console.log(chalk.yellow('Harap TUTUP terminal ini sepenuhnya (Quit) lalu buka terminal baru agar perintah dikenali,'));
        console.log(chalk.yellow('atau jalankan:  source ~/.zshrc\n'));
    } else {
        console.log(`Sekarang Anda tidak perlu lagi masuk ke folder project untuk menjalankan aplikasi.`);
    }

    console.log(`Buka terminal baru dari folder mana saja (misal di Desktop), lalu ketik:`);
    console.log(chalk.magenta.bold('  rynude\n'));
}

run().catch(e => {
    console.error(chalk.red('\nTerjadi kesalahan fatal:'), e);
    process.exit(1);
});
