import { spawn, spawnSync } from 'child_process';
import prompts from 'prompts';
import chalk from 'chalk';
import net from 'net';
import os from 'os';
import fs from 'fs';
import path from 'path';

// ─────────────────────────────────────────────────────────────────────────────
// Perlindungan Database
//
// Saat update (npx install-rynude@latest) menimpa folder project, file
// database/database.sqlite ikut ter-reset sehingga data hilang. Untuk mencegah
// itu, kita simpan database asli di folder HOME pengguna (~/.rynude) yang TIDAK
// pernah disentuh oleh proses update, lalu mengarahkan .env ke sana. Kita juga
// membuat backup ber-timestamp setiap kali aplikasi dijalankan.
// ─────────────────────────────────────────────────────────────────────────────
const RYNUDE_HOME = path.join(os.homedir(), '.rynude');
const PERSIST_DB = path.join(RYNUDE_HOME, 'database.sqlite');
const BACKUP_DIR = path.join(RYNUDE_HOME, 'backups');
const PROJECT_DB = path.resolve('database', 'database.sqlite');
const APP_KEY_FILE = path.join(RYNUDE_HOME, 'app_key.txt');

function sizeOf(p) {
    try { return fs.statSync(p).size; } catch { return -1; }
}

function ensureDir(p) {
    try { fs.mkdirSync(p, { recursive: true }); } catch {}
}

function pruneBackups(keep = 15) {
    try {
        const files = fs.readdirSync(BACKUP_DIR)
            .filter(f => f.endsWith('.sqlite'))
            .map(f => ({ f, t: fs.statSync(path.join(BACKUP_DIR, f)).mtimeMs }))
            .sort((a, b) => b.t - a.t);
        files.slice(keep).forEach(x => {
            try { fs.unlinkSync(path.join(BACKUP_DIR, x.f)); } catch {}
        });
    } catch {}
}

function envUsesSqlite() {
    try {
        if (!fs.existsSync('.env')) return true; // default Rynude adalah sqlite
        const env = fs.readFileSync('.env', 'utf8');
        const m = env.match(/^DB_CONNECTION\s*=\s*(.+)$/m);
        return !m || m[1].trim().replace(/["']/g, '') === 'sqlite';
    } catch { return true; }
}

function setEnvDbPath(absPath) {
    try {
        if (!fs.existsSync('.env')) return;
        let env = fs.readFileSync('.env', 'utf8');
        const normalized = absPath.replace(/\\/g, '/'); // PHP menerima forward slash di Windows
        const line = `DB_DATABASE=${normalized}`;
        if (/^DB_DATABASE\s*=.*$/m.test(env)) {
            const current = env.match(/^DB_DATABASE\s*=(.*)$/m)[1].trim().replace(/["']/g, '');
            if (current === normalized) return; // sudah benar
            env = env.replace(/^DB_DATABASE\s*=.*$/m, line);
        } else if (/^DB_CONNECTION\s*=.*$/m.test(env)) {
            env = env.replace(/^(DB_CONNECTION\s*=.*)$/m, `$1\n${line}`);
        } else {
            env += `\n${line}\n`;
        }
        fs.writeFileSync('.env', env);
    } catch {}
}

function protectAppKey() {
    try {
        if (!fs.existsSync('.env')) return;
        const env = fs.readFileSync('.env', 'utf8');
        const match = env.match(/^APP_KEY=(.*)$/m);
        if (match && match[1]) {
            const currentKey = match[1].trim();
            if (fs.existsSync(APP_KEY_FILE)) {
                // Pulihkan key lama jika berubah
                const savedKey = fs.readFileSync(APP_KEY_FILE, 'utf8').trim();
                if (savedKey && savedKey !== currentKey) {
                    const newEnv = env.replace(/^APP_KEY=.*$/m, `APP_KEY=${savedKey}`);
                    fs.writeFileSync('.env', newEnv);
                    console.log(chalk.green('Kunci enkripsi (APP_KEY) lama berhasil dipulihkan untuk mengamankan data.'));
                }
            } else {
                // Simpan key untuk pertama kalinya
                fs.writeFileSync(APP_KEY_FILE, currentKey);
            }
        }
    } catch {}
}

function protectDatabase() {
    if (!envUsesSqlite()) return; // hanya berlaku untuk sqlite

    ensureDir(RYNUDE_HOME);
    ensureDir(BACKUP_DIR);

    protectAppKey();

    const persistSize = sizeOf(PERSIST_DB);
    const projectSize = sizeOf(PROJECT_DB);

    // Pindahkan/pulihkan data ke lokasi persisten di folder home.
    if (projectSize > 0 && persistSize <= 0) {
        // Pertama kali, atau DB persisten kosong: bawa data project ke home.
        try { fs.copyFileSync(PROJECT_DB, PERSIST_DB); } catch {}
        console.log(chalk.green('Database dipindahkan ke folder aman: ') + chalk.gray(PERSIST_DB));
    } else if (persistSize < 0) {
        // Belum ada DB sama sekali: siapkan file kosong (akan dimigrasi artisan).
        try { fs.writeFileSync(PERSIST_DB, ''); } catch {}
    }

    // Arahkan aplikasi ke DB persisten (juga memperbaiki pointer bila .env
    // ter-reset oleh update). Folder home tidak pernah ditimpa update.
    setEnvDbPath(PERSIST_DB);

    // Backup ber-timestamp dari DB persisten bila sudah berisi data.
    if (sizeOf(PERSIST_DB) > 0) {
        const stamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
        try {
            fs.copyFileSync(PERSIST_DB, path.join(BACKUP_DIR, `database-${stamp}.sqlite`));
            console.log(chalk.gray(`Backup database dibuat (menyimpan 15 terbaru di ${BACKUP_DIR}).`));
        } catch {}
        pruneBackups(15);
    }
}

// Fungsi untuk mencari port yang kosong (agar tidak bentrok)
async function getFreePort(startPort) {
    return new Promise((resolve, reject) => {
        const server = net.createServer();
        server.listen(startPort, () => {
            const port = server.address().port;
            server.close(() => resolve(port));
        });
        server.on('error', (err) => {
            if (err.code === 'EADDRINUSE') {
                resolve(getFreePort(startPort + 1));
            } else {
                reject(err);
            }
        });
    });
}

// Fungsi untuk membuka browser
function openBrowser(url) {
    const start = (process.platform === 'darwin' ? 'open' : process.platform === 'win32' ? 'start' : 'xdg-open');
    import('child_process').then(cp => cp.exec(`${start} ${url}`));
}

async function run() {
    console.clear();

    // Instalasi rusak (mis. update yang terputus di tengah jalan) membuat npm/
    // artisan gagal dengan error ENOENT yang membingungkan. Deteksi lebih awal
    // dan arahkan user ke installer.
    if (!fs.existsSync(path.resolve('package.json')) || !fs.existsSync(path.resolve('artisan'))) {
        console.error(chalk.red.bold('Instalasi Rynude AI tidak lengkap (kemungkinan update yang terputus).'));
        console.error(chalk.yellow('Perbaiki dengan menjalankan:'));
        console.error(chalk.white.bold('  npx install-rynude@latest'));
        console.error(chalk.green('Tenang: data obrolan Anda aman di folder ~/.rynude dan dipulihkan otomatis.\n'));
        process.exit(1);
    }

    console.log(chalk.gray('Menyiapkan lingkungan Rynude AI...'));

    // CRITICAL: Always run npm install to ensure all dependencies (including mermaid) are installed
    console.log(chalk.green('Memastikan semua dependencies terinstall...'));
    spawnSync('npm', ['install'], { stdio: 'ignore', shell: true });

    // CRITICAL: Always rebuild assets to ensure latest code is in public/build/
    console.log(chalk.green('Membangun ulang aset frontend...'));
    spawnSync('npm', ['run', 'build'], { stdio: 'inherit', shell: true });

    // REMOVED: Don't delete public/hot - it's needed for Vite HMR
    // if (fs.existsSync('./public/hot')) {
    //     fs.unlinkSync('./public/hot');
    // }

    // Amankan database SEBELUM optimize agar config-cache menunjuk ke lokasi yang benar.
    console.log(chalk.green('Mengamankan database Anda...'));
    protectDatabase();

    // CRITICAL: Clear ALL caches before optimize to ensure fresh view compilations
    console.log(chalk.green('Membersihkan semua cache...'));
    spawnSync('php', ['artisan', 'view:clear'], { stdio: 'ignore', shell: true });
    spawnSync('php', ['artisan', 'config:clear'], { stdio: 'ignore', shell: true });
    spawnSync('php', ['artisan', 'cache:clear'], { stdio: 'ignore', shell: true });
    spawnSync('php', ['artisan', 'optimize:clear'], { stdio: 'ignore', shell: true });

    console.log(chalk.green('Mengoptimalkan sistem untuk performa maksimal...'));
    spawnSync('php', ['artisan', 'optimize'], { stdio: 'ignore', shell: true });

    // Pastikan skema database terbaru (additif — tidak menghapus data lama).
    // Berguna setelah update menambah tabel/kolom baru.
    spawnSync('php', ['artisan', 'migrate', '--force'], { stdio: 'ignore', shell: true });

    console.log(chalk.gray('Mencari port yang tersedia...'));
    const laravelPort = await getFreePort(8080);

    console.clear();
    console.log(chalk.magenta('==================================================================='));
    console.log(chalk.white.bold('   🚀 Rynude AI - Interactive Dashboard'));
    console.log(chalk.gray('   Local Web UI  : ') + chalk.green(`http://localhost:${laravelPort}`));
    console.log(chalk.magenta('===================================================================\n'));

    // Tulis port ke file sementara agar bisa dibaca oleh script background
    try {
        fs.writeFileSync(path.join(process.cwd(), '.rynude-port'), laravelPort.toString());
    } catch (err) {}

    // Di Mac/Linux proses dijalankan sebagai process group tersendiri agar
    // shell + php di dalamnya bisa dimatikan sekaligus (setara taskkill /T).
    const isUnix = os.platform() !== 'win32';

    // Menjalankan server di background
    const phpServer = spawn(`php artisan serve --port=${laravelPort}`, {
        stdio: 'ignore',
        shell: true,
        detached: isUnix,
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '10' }
    });

    // Menjalankan queue worker di background agar tugas asinkron
    // (mis. pembuatan judul chat otomatis) langsung diproses tanpa
    // perlu menjalankan `php artisan queue:work` secara manual.
    // Koneksi `database` HARUS eksplisit: QUEUE_CONNECTION default `sync`,
    // sehingga worker tanpa argumen tidak memproses apa pun dan job judul
    // chat malah berjalan inline di dalam request streaming (chat terasa
    // macet menunggu panggilan AI kedua selesai).
    const queueWorker = spawn(`php artisan queue:work database --tries=1 --timeout=0`, {
        stdio: 'ignore',
        shell: true,
        detached: isUnix,
        env: { ...process.env }
    });

    // Daftar semua proses anak agar bisa dimatikan sekaligus
    const childProcesses = [phpServer, queueWorker];

    // Fungsi untuk mematikan server
    const killServers = () => {
        console.log(chalk.yellow('\nMenutup server Rynude AI... Sampai jumpa! 👋'));
        if (os.platform() === 'win32') {
            import('child_process').then(cp => {
                let remaining = childProcesses.length;
                if (remaining === 0) process.exit(0);
                childProcesses.forEach((proc) => {
                    cp.exec(`taskkill /pid ${proc.pid} /T /F`, () => {
                        remaining -= 1;
                        if (remaining === 0) process.exit(0);
                    });
                });
            });
        } else {
            childProcesses.forEach((proc) => {
                // PID negatif = bunuh seluruh process group (shell beserta php
                // artisan serve/queue:work di dalamnya), bukan hanya shell-nya.
                try {
                    process.kill(-proc.pid, 'SIGTERM');
                } catch (e) {
                    try { proc.kill('SIGTERM'); } catch (e2) {}
                }
            });
            process.exit(0);
        }
    };

    // Tangkap Ctrl+C
    process.on('SIGINT', killServers);

    async function showMenu() {
        const workspace = process.env.RYNUDE_WORKSPACE || process.cwd();

        const response = await prompts({
            type: 'select',
            name: 'action',
            message: 'Gunakan ⬆️/⬇️ lalu tekan ENTER:',
            choices: [
                { title: '💻 Buka Terminal UI (RynudeCode CLI)', value: 'cli' },
                { title: '🌐 Buka Web UI (Browser)', value: 'browser' },
                { title: '🛑 Tutup Server & Keluar', value: 'exit' }
            ]
        });

        if (response.action === 'cli') {
            console.clear();
            console.log(chalk.cyan(`Menjalankan RynudeCode CLI di workspace: ${workspace}`));
            
            // Execute the Laravel command directly attached to stdio
            const cliProcess = spawn('php', ['artisan', 'rynude:chat', `--workspace=${workspace}`], {
                stdio: 'inherit',
                shell: true
            });
            
            cliProcess.on('close', () => {
                killServers();
            });
        } else if (response.action === 'browser') {
            openBrowser(`http://localhost:${laravelPort}`);
            console.log(chalk.cyan(`\nMembuka browser...`));
            setTimeout(showMenu, 1000);
        } else {
            killServers();
        }
    }

    // Cek apakah dijalankan dalam mode silent atau mode CLI langsung
    const isSilent = process.argv.includes('--silent');
    const isCliMode = process.argv.includes('--cli');

    if (isCliMode) {
        const workspace = process.env.RYNUDE_WORKSPACE || process.cwd();
        console.clear();
        console.log(chalk.cyan(`Menjalankan RynudeCode CLI di workspace: ${workspace}`));
        const cliProcess = spawn('php', ['artisan', 'rynude:chat', `--workspace=${workspace}`], {
            stdio: 'inherit',
            shell: true
        });
        cliProcess.on('close', () => {
            killServers();
        });
    } else if (isSilent) {
        console.log(chalk.gray('Berjalan di mode silent (Background). Gunakan icon Taskbar untuk menutup.'));
        
        // Buka browser otomatis setelah beberapa saat menunggu server PHP siap
        setTimeout(() => {
            console.log(chalk.blue('Membuka browser otomatis...'));
            spawn('cmd', ['/c', 'start', `http://localhost:${laravelPort}`], { stdio: 'ignore' });
        }, 2000);
        
        // JANGAN panggil showMenu() agar proses tidak tertahan menunggu input pengguna.
    } else {
        // Tampilkan menu interaktif
        showMenu();
    }
}

run().catch(e => {
    console.error(chalk.red('Gagal memulai Rynude AI:'), e);
    process.exit(1);
});
