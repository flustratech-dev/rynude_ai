import fs from 'fs';

const html = fs.readFileSync('d:/claude-ui-clone/chat_output.html', 'utf8');
const scripts = html.match(/<script>[^]*?<\/script>/g) || [];

scripts.forEach((s, i) => {
    const code = s.replace(/<\/?script>/g, '');
    if (code.includes('artifactPanelState')) {
        fs.writeFileSync('d:/claude-ui-clone/temp_script.js', code);
    }
});
