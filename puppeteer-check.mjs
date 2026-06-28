import puppeteer from 'puppeteer';

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    
    let errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push('CONSOLE ERROR: ' + msg.text());
        }
    });
    page.on('pageerror', error => {
        errors.push('PAGE ERROR: ' + error.message);
    });
    
    await page.goto('http://127.0.0.1:8080/chat', { waitUntil: 'networkidle0' });
    
    if (errors.length > 0) {
        console.log('--- ERRORS FOUND ---');
        errors.forEach(e => console.log(e));
    } else {
        console.log('No JS errors detected.');
    }
    
    const templates = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('template')).map(t => {
            return {
                tagName: t.tagName,
                hasContent: !!t.content,
                isTemplate: t instanceof HTMLTemplateElement
            };
        });
    });
    console.log("TEMPLATES:", templates);
    
    await browser.close();
})();
