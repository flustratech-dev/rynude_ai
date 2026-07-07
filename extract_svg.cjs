const fs = require('fs');
['anthropic', 'googlegemini', 'huggingface', 'ollama'].forEach(f => {
  try {
    const data = fs.readFileSync(f + '.svg', 'utf8');
    const match = data.match(/<path[^>]*d="([^"]+)"/);
    if(match) console.log(f + ':\n' + match[1]);
  } catch(e){}
});
