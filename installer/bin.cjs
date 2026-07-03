#!/usr/bin/env node
// Launcher CJS: berjalan di Node versi berapa pun, memeriksa versi minimal
// SEBELUM index.js (ESM + chalk 5/ora 8) dimuat — Node lama akan crash dengan
// ERR_REQUIRE_ESM/SyntaxError tanpa penjelasan jika langsung memuat index.js.
// Jangan pakai sintaks modern (?., ??, import) di file ini.
var major = parseInt(process.versions.node.split('.')[0], 10);

if (major < 18) {
    console.error('');
    console.error('❌ Installer Rynude AI membutuhkan Node.js versi 18 atau lebih baru.');
    console.error('   Versi Node.js Anda saat ini: ' + process.version);
    console.error('');
    console.error('   Cara update:');
    console.error('   - macOS   : brew install node   (atau: nvm install --lts)');
    console.error('   - Windows : download dari https://nodejs.org/');
    console.error('   - Linux   : nvm install --lts   (atau paket distro Anda)');
    console.error('');
    console.error('   Setelah update, buka terminal baru lalu jalankan ulang:');
    console.error('   npx install-rynude@latest');
    console.error('');
    process.exit(1);
}

// Dynamic import lewat Function agar file ini tetap bisa DI-PARSE oleh Node
// sangat tua (yang belum mengenal sintaks import()) — cek versi di atas tetap
// sempat berjalan dan menampilkan pesan yang jelas. Path dibuat absolut karena
// import() di dalam new Function tidak resolve relatif terhadap file ini.
var pathToFileURL = require('url').pathToFileURL;
var indexPath = require('path').join(__dirname, 'index.js');
var dynamicImport = new Function('p', 'return import(p);');
dynamicImport(pathToFileURL(indexPath).href).catch(function (e) {
    console.error('Gagal memulai installer:', e);
    process.exit(1);
});
