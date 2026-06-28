import fs from 'fs';
['chat-layout.blade.php', 'chat-interface.blade.php', 'app.blade.php', 'chat.blade.php'].forEach(file => {
    let html = '';
    try {
        html = fs.readFileSync('d:/claude-ui-clone/resources/views/' + (file === 'app.blade.php' ? 'layouts/' : 'livewire/') + file, 'utf8');
    } catch(e) {
        try {
            html = fs.readFileSync('d:/claude-ui-clone/resources/views/' + file, 'utf8');
        } catch(e) {}
    }
    const opens = (html.match(/<div/g) || []).length;
    const closes = (html.match(/<\/div>/g) || []).length;
    console.log(`${file}: <div count=${opens}, </div> count=${closes}. Difference = ${opens - closes}`);
});
