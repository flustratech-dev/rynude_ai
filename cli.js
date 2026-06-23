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

function protectDatabase() {
    if (!envUsesSqlite()) return; // hanya berlaku untuk sqlite

    ensureDir(RYNUDE_HOME);
    ensureDir(BACKUP_DIR);

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
    console.log(chalk.gray('Menyiapkan lingkungan Rynude AI...'));

    if (!fs.existsSync('./public/build')) {
        console.log(chalk.yellow('Melakukan build aset pertama kali (hanya sekali ini saja)...'));
        spawnSync('npm', ['run', 'build'], { stdio: 'inherit', shell: true });
    }
    
    if (fs.existsSync('./public/hot')) {
        fs.unlinkSync('./public/hot');
    }

    // Amankan database SEBELUM optimize agar config-cache menunjuk ke lokasi yang benar.
    console.log(chalk.green('Mengamankan database Anda...'));
    protectDatabase();

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

    // Menjalankan server di background
    const phpServer = spawn(`php artisan serve --port=${laravelPort}`, {
        stdio: 'ignore',
        shell: true,
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '10' }
    });

    // Menjalankan queue worker di background agar tugas asinkron
    // (mis. pembuatan judul chat otomatis) langsung diproses tanpa
    // perlu menjalankan `php artisan queue:work` secara manual.
    const queueWorker = spawn(`php artisan queue:work --tries=1 --timeout=0`, {
        stdio: 'ignore',
        shell: true,
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
            childProcesses.forEach((proc) => proc.kill('SIGINT'));
            process.exit(0);
        }
    };

    // Tangkap Ctrl+C
    process.on('SIGINT', killServers);

    async function showMenu() {
        const response = await prompts({
            type: 'select',
            name: 'action',
            message: 'Gunakan ⬆️/⬇️ lalu tekan ENTER:',
            choices: [
                { title: '🌐 Buka Web UI (Browser)', value: 'browser' },
                { title: '🛑 Tutup Server & Keluar', value: 'exit' }
            ]
        });

        if (response.action === 'browser') {
            openBrowser(`http://localhost:${laravelPort}`);
            console.log(chalk.cyan(`\nMembuka browser...`));
            setTimeout(showMenu, 1000);
        } else {
            killServers();
        }
    }

    // Tampilkan menu interaktif
    showMenu();
}

run().catch(e => {
    console.error(chalk.red('Gagal memulai server:'), e);
    process.exit(1);
});
