const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    
    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
    
    await page.goto('http://localhost:8080/chat', { waitUntil: 'networkidle0' });
    
    // Check if empty state exists
    const emptyState = await page.$eval('h1', el => el.innerText).catch(() => 'No h1 found');
    console.log('H1 Text:', emptyState);
    
    // Check sidebar position
    const sidebar = await page.$('.w-\\[290px\\]');
    if (sidebar) {
        const box = await sidebar.boundingBox();
        console.log('Sidebar bounding box:', box);
    } else {
        console.log('Sidebar not found');
    }
    
    await browser.close();
})();
