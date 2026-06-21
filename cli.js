import { spawn, spawnSync } from 'child_process';
import prompts from 'prompts';
import chalk from 'chalk';
import net from 'net';
import os from 'os';
import fs from 'fs';

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
    
    console.log(chalk.green('Mengoptimalkan sistem untuk performa maksimal...'));
    spawnSync('php', ['artisan', 'optimize'], { stdio: 'ignore', shell: true });

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

    // Fungsi untuk mematikan server
    const killServers = () => {
        console.log(chalk.yellow('\nMenutup server Rynude AI... Sampai jumpa! 👋'));
        if (os.platform() === 'win32') {
            import('child_process').then(cp => {
                cp.exec(`taskkill /pid ${phpServer.pid} /T /F`, () => {
                    process.exit(0);
                });
            });
        } else {
            phpServer.kill('SIGINT');
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
