const fs = require('node:fs');
const assert = require('node:assert/strict');
const {chromium} = require('playwright');
const root = '/workspace/site/var/tests/docs-browser';
const origin = 'http://127.0.0.1:8001';
const names = ['home', 'section', 'article', 'search', 'admin-pages', 'admin-form', 'admin-preview', 'admin-images', 'picker'];
function fixture(url) {
    if (url.pathname.startsWith('/__docs_fixture/')) return url.pathname.split('/').pop();
    if (/^\/admin\/docs\/pages\/\d+\/images$/.test(url.pathname)) return url.searchParams.get('embed') === '1' ? 'admin-image-embed' : 'admin-images';
    if (url.hostname !== 'docs.vashfindir.ru') return null;
    if (url.pathname === '/') return 'home';
    if (url.pathname.startsWith('/sections/')) return 'section';
    if (url.pathname.endsWith('/browser-next')) return 'next';
    if (url.pathname.startsWith('/articles/')) return 'article';
    if (url.pathname === '/search') return 'search';
    return null;
}
async function prepare(context) {
    let temporaryUploadFailure = true;
    await context.route('**/*', async route => {
        const request = route.request(), url = new URL(request.url());
        if (request.method() === 'POST' && url.origin === origin && /^\/admin\/docs\/pages\/\d+\/images$/.test(url.pathname)) {
            if (temporaryUploadFailure && request.postDataBuffer()?.includes('retry.webp')) {
                temporaryUploadFailure = false;
                return route.fulfill({status: 422, json: {message: 'Временная ошибка загрузки.'}});
            }
            return route.fulfill({json: {}});
        }
        assert.equal(request.method(), 'GET', 'Browser fixtures must not change server data');
        if (/^\/(media\/documentation|admin\/docs\/images)\/[a-f0-9-]+$/.test(url.pathname)) {
            return route.fulfill({contentType: 'image/webp', body: fs.readFileSync(`${root}/image.webp`)});
        }
        const name = fixture(url);
        if (name) return route.fulfill({contentType: 'text/html', body: fs.readFileSync(`${root}/${name}.html`, 'utf8')});
        if (url.origin === origin) return route.continue();
        // Analytics/chat/CDNs never leave the local test environment.
        return route.fulfill({status: 200, body: '', contentType: 'application/javascript'});
    });
}
async function load(page, name) {
    await page.goto(`${origin}/__docs_fixture/${name}`, {waitUntil: 'networkidle'});
    await page.evaluate(() => document.fonts.ready);
}
async function geometry(page, name) {
    const state = await page.evaluate(() => ({width: innerWidth, scroll: document.documentElement.scrollWidth, css: [...document.querySelectorAll('link[rel="stylesheet"]')].map(x => x.getAttribute('href')), h1: document.querySelectorAll('h1').length}));
    assert.ok(state.scroll <= state.width + 1, `${name}: overflow ${JSON.stringify(state)}`);
    assert.equal(state.h1, 1, `${name}: one H1`);
    const admin = name.startsWith('admin-') || name === 'picker';
    assert.ok(state.css.length === 1 && state.css[0].startsWith(admin ? '/assets/admin/' : '/assets/website/'), `${name}: isolated bundle`);
}
(async () => {
    const browser = await chromium.launch({headless: true, args: ['--no-sandbox']});
    try {
        const context = await browser.newContext(); await prepare(context);
        const page = await context.newPage(); const errors = []; page.on('pageerror', e => errors.push(e.message));
        for (const width of [320, 375, 768, 1024, 1440]) {
            await page.setViewportSize({width, height: 900});
            for (const name of names) { await load(page, name); await geometry(page, name); }
        }
        await page.setViewportSize({width: 320, height: 900});
        for (const name of ['article','admin-form','picker']) {
            await load(page, name); await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; }); await geometry(page, name);
        }
        await load(page, 'article'); await page.keyboard.press('Tab');
        assert.equal(await page.evaluate(() => document.activeElement.tagName), 'A', 'Keyboard focus reaches skip link');
        await page.locator('nav[aria-label="Оглавление"] a').first().click();
        assert.ok(await page.evaluate(() => !!location.hash && document.getElementById(decodeURIComponent(location.hash.slice(1))) !== null), 'TOC target exists');
        await page.locator('a[rel="next"]').click(); assert.ok(page.url().endsWith('/browser-next'), 'Next instruction navigation');
        await load(page, 'article'); await page.locator('section[aria-label="Читайте также"] a').click(); assert.ok(page.url().endsWith('/browser-next'), 'Related instruction navigation');
        await load(page, 'home'); await page.locator('#docs-query').fill('операции'); await page.locator('form[role="search"] button').click(); assert.ok(page.url().includes('/search?q='), 'Search uses GET');
        await load(page, 'admin-form');
        const body = page.locator('[data-doc-body]');
        await body.fill('Несохранённый текст');
        const image = fs.readFileSync(`${root}/image.webp`);
        await page.locator('input[type="file"]').setInputFiles([
            {name: 'first.webp', mimeType: 'image/webp', buffer: image},
            {name: 'retry.webp', mimeType: 'image/webp', buffer: image},
        ]);
        await page.getByRole('button', {name: 'Загрузить снимки'}).click();
        await page.getByText('first.webp — загружен').waitFor();
        await page.getByRole('button', {name: 'Повторить'}).click();
        await page.getByText('retry.webp — загружен').waitFor();
        assert.equal(await body.inputValue(), 'Несохранённый текст', 'Upload preserves unsaved edits');
        await page.getByRole('button', {name: 'Вставить в текст'}).click();
        await page.getByLabel('Альтернативный текст для снимка').fill('Поля регистрации');
        await page.getByRole('button', {name: 'Вставить снимок'}).click();
        assert.match(await body.inputValue(), /Несохранённый текст[\s\S]*!\[Поля регистрации\]\(doc-image:[a-f0-9-]+\)/);
        await load(page, 'article');
        const screenshot = page.getByAltText('Снимок настроек');
        await screenshot.scrollIntoViewIfNeeded();
        await page.waitForFunction(() => document.querySelector('img[alt="Снимок настроек"]')?.naturalWidth === 1280);
        const screenshotBox = await screenshot.boundingBox();
        assert.ok(screenshotBox && screenshotBox.width <= 320, 'Screenshot fits the mobile article');
        await load(page, 'article'); await page.screenshot({path: '/artifacts/docs-mobile.png', fullPage: true});
        assert.deepEqual(errors, [], 'No browser JS errors'); await context.close();
        const nojs = await browser.newContext({javaScriptEnabled: false, viewport: {width: 320, height: 900}}); await prepare(nojs);
        const plain = await nojs.newPage();
        for (const name of ['article','admin-form','picker']) { await load(plain, name); await geometry(plain, name); }
        assert.ok(await plain.locator('textarea[readonly]').count(), 'Copyable links without JS');
        await load(plain, 'admin-images');
        assert.match(await plain.locator('input[readonly]').inputValue(), /doc-image:/, 'Markdown is copyable without JS');
        await nojs.close();
        console.log('OK: 9 screens × 5 widths; browser navigation, screenshot upload/retry/insert, mobile rendering and no-JS fallback');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exit(1);});
