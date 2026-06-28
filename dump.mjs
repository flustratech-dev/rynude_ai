import puppeteer from 'puppeteer';
import fs from 'fs';

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    
    // Catch errors
    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
    
    await page.goto('http://localhost:8080/chat', { waitUntil: 'networkidle0' });
    
    // Dump outerHTML of the body to see what actually rendered
    const bodyHtml = await page.$eval('body', el => el.outerHTML);
    fs.writeFileSync('d:\\claude-ui-clone\\rendered_body.html', bodyHtml);
    
    // Check if the empty state h1 exists
    const h1Count = await page.$$eval('h1', els => els.length);
    console.log(`Found ${h1Count} <h1> elements.`);
    
    await browser.close();
})();
