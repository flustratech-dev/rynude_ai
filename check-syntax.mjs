import fs from 'fs';

const html = fs.readFileSync('d:/claude-ui-clone/chat_output.html', 'utf8');
const scripts = html.match(/<script>[^]*?<\/script>/g) || [];

scripts.forEach((s, i) => {
    const code = s.replace(/<\/?script>/g, '');
    try {
        new Function(code);
    } catch(e) {
        console.log('--- Syntax Error in Script ' + i + ' ---');
        console.log(e.message);
        console.log('Code snippet:');
        console.log(code.substring(0, 200));
        console.log('...');
    }
});
