import fs from 'fs';
import path from 'path';

const dir = 'd:/claude-ui-clone/resources/views/livewire/';
const files = fs.readdirSync(dir).filter(f => f.endsWith('.blade.php'));

for (const file of files) {
    const html = fs.readFileSync(path.join(dir, file), 'utf8');
    const opens = (html.match(/<svg(?![a-zA-Z])/g) || []).length;
    const closes = (html.match(/<\/svg>/g) || []).length;
    if (opens !== closes) {
        console.log(`${file}: open ${opens}, close ${closes}`);
    }
}
