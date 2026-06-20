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

async function checkRequirements() {
    const reqs = ['git', 'php', 'composer', 'npm'];
    for (const req of reqs) {
        try {
            execSync(`${req} --version`, { stdio: 'ignore' });
        } catch (e) {
            console.error(chalk.red(`\n❌ Error: Program '${req}' tidak ditemukan di sistem Anda.`));
            console.error(chalk.yellow(`Silakan install ${req} terlebih dahulu sebelum menginstal Rynude AI.\n`));
            process.exit(1);
        }
    }
}

async function run() {
    console.log(chalk.cyan.bold('\n🚀 Selamat datang di Rynude AI Auto-Installer\n'));
    
    await checkRequirements();

    if (fs.existsSync(INSTALL_DIR)) {
        const response = await prompts({
            type: 'confirm',
            name: 'overwrite',
            message: `Folder instalasi ${chalk.yellow(INSTALL_DIR)} sudah ada. Apakah Anda ingin menginstal ulang (menghapus instalasi yang lama)?`,
            initial: false
        });

        if (!response.overwrite) {
            console.log(chalk.yellow('Instalasi dibatalkan.'));
            process.exit(0);
        }
        
        const spinner = ora('Menghapus instalasi lama...').start();
        fs.removeSync(INSTALL_DIR);
        spinner.succeed('Instalasi lama berhasil dihapus.');
    }

    console.log(chalk.gray(`\nMenginstal Rynude AI ke folder tersembunyi: ${INSTALL_DIR}`));

    // 1. Clone
    const cloneSpinner = ora('Mengunduh Rynude AI (Clone)...').start();
    try {
        execSync(`git clone --depth=1 ${REPO_URL} "${INSTALL_DIR}"`, { stdio: 'ignore' });
        cloneSpinner.succeed('Berhasil mengunduh source code.');
    } catch (e) {
        cloneSpinner.fail('Gagal mengunduh source code.');
        console.error(chalk.red(e.message));
        process.exit(1);
    }

    // 2. Composer
    const composerSpinner = ora('Menginstal dependensi backend (Composer)...').start();
    try {
        execSync('composer install --no-interaction --optimize-autoloader', { cwd: INSTALL_DIR, stdio: 'ignore' });
        composerSpinner.succeed('Dependensi backend terinstal.');
    } catch (e) {
        composerSpinner.fail('Gagal menginstal dependensi backend.');
        process.exit(1);
    }

    // 3. NPM
    const npmSpinner = ora('Menginstal dependensi frontend (NPM)...').start();
    try {
        execSync('npm install', { cwd: INSTALL_DIR, stdio: 'ignore' });
        npmSpinner.succeed('Dependensi frontend terinstal.');
    } catch (e) {
        npmSpinner.fail('Gagal menginstal dependensi frontend.');
        process.exit(1);
    }

    // 4. ENV Setup
    const envSpinner = ora('Menyiapkan database bawaan (SQLite)...').start();
    try {
        fs.copySync(path.join(INSTALL_DIR, '.env.example'), path.join(INSTALL_DIR, '.env'));
        
        // Ubah koneksi ke sqlite agar zero-config
        let envContent = fs.readFileSync(path.join(INSTALL_DIR, '.env'), 'utf-8');
        envContent = envContent.replace(/DB_CONNECTION=.*/, 'DB_CONNECTION=sqlite');
        fs.writeFileSync(path.join(INSTALL_DIR, '.env'), envContent);
        
        // Buat file database
        fs.ensureFileSync(path.join(INSTALL_DIR, 'database', 'database.sqlite'));
        
        execSync('php artisan key:generate', { cwd: INSTALL_DIR, stdio: 'ignore' });
        execSync('php artisan migrate:fresh --seed', { cwd: INSTALL_DIR, stdio: 'ignore' });
        envSpinner.succeed('Konfigurasi dan database siap.');
    } catch (e) {
        envSpinner.fail('Gagal menyiapkan database.');
        console.error(chalk.red(e.message));
        process.exit(1);
    }

    // 5. Global Command Setup
    const globalSpinner = ora('Mengatur global command (rynude)...').start();
    try {
        const isWindows = os.platform() === 'win32';
        if (isWindows) {
            execSync('cmd.exe /c setup-global.bat', { cwd: INSTALL_DIR, stdio: 'ignore' });
        } else {
            execSync('bash setup-global.sh', { cwd: INSTALL_DIR, stdio: 'ignore' });
            
            // Auto add to PATH for Mac/Linux
            const bashrcPath = path.join(os.homedir(), '.bashrc');
            const zshrcPath = path.join(os.homedir(), '.zshrc');
            const pathExport = `\nexport PATH="$HOME/.local/bin:$PATH"\n`;

            if (fs.existsSync(bashrcPath)) {
                const content = fs.readFileSync(bashrcPath, 'utf8');
                if (!content.includes('.local/bin')) fs.appendFileSync(bashrcPath, pathExport);
            }
            if (fs.existsSync(zshrcPath)) {
                const content = fs.readFileSync(zshrcPath, 'utf8');
                if (!content.includes('.local/bin')) fs.appendFileSync(zshrcPath, pathExport);
            }
        }
        globalSpinner.succeed('Global command berhasil diatur.');
    } catch (e) {
        globalSpinner.fail('Gagal mengatur global command.');
        console.error(chalk.yellow(`Silakan jalankan setup-global secara manual di folder: ${INSTALL_DIR}`));
    }

    console.log(chalk.green.bold('\n🎉 Instalasi Rynude AI Berhasil Selesai!\n'));
    
    if (os.platform() !== 'win32') {
        console.log(chalk.bgYellow.black.bold(' ⚠️ PENTING UNTUK MAC/LINUX '));
        console.log(chalk.yellow(`Harap TUTUP terminal ini sepenuhnya (Quit) lalu buka terminal yang baru, agar perintah dikenali.\n`));
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
