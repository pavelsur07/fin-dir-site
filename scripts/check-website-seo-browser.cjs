// Только dev: POST /lead и внешняя Метрика перехватываются, заявки не сохраняются.
const assert = require('node:assert/strict');
const puppeteer = require('/usr/src/app/node_modules/puppeteer');

(async () => {
    const browser = await puppeteer.launch({headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage']});
    try {
        const page = await browser.newPage();
        let leadStatus = 422;
        await page.setRequestInterception(true);
        page.on('request', request => {
            const url = new URL(request.url());
            if (url.hostname !== 'localhost') {
                if (url.href === 'https://mc.yandex.ru/metrika/tag.js') {
                    return request.respond({status: 200, contentType: 'application/javascript', body: ''});
                }
                return request.abort();
            }
            if (url.pathname === '/lead' && request.method() === 'POST') {
                return request.respond({status: leadStatus, contentType: 'application/json', body: JSON.stringify(
                    leadStatus === 201 ? {id: 'browser-check'} : {errors: {name: 'Укажите имя'}},
                )});
            }
            request.continue();
        });
        for (const width of [320, 375, 768, 1024, 1440]) {
            await page.setViewport({width, height: 900});
            const response = await page.goto('http://localhost:8001/', {waitUntil: 'networkidle0'});
            assert.equal(response.status(), 200);
            await page.evaluate(() => document.fonts.ready);
            await page.$eval('[aria-labelledby="home-team-title"]', node => node.scrollIntoView());
            await page.waitForFunction(() => [...document.querySelectorAll('[aria-labelledby="home-team-title"] img')].every(img => img.complete && img.naturalWidth > 0));
            const result = await page.evaluate(() => ({
                overflow: document.documentElement.scrollWidth > innerWidth,
                images: [...document.querySelectorAll('[aria-labelledby="home-team-title"] img')].map(img => img.currentSrc),
                hero: document.querySelector('#home-hero-title').closest('section').querySelector('img').currentSrc,
                canonical: document.querySelector('link[rel="canonical"]').href,
                metrika: (window.ym.a || []).some(args => args[0] === '105455340' && args[1] === 'init'),
            }));
            assert.equal(result.overflow, false);
            assert.equal(result.canonical, 'https://vashfindir.ru/');
            assert.equal(result.metrika, true);
            assert.equal(result.images.length, 3);
            assert.ok(result.images.every(src => src.endsWith('.webp')));
            assert.ok(/\.(avif|webp)$/.test(result.hero));
            console.log(`Главная ${width}px: layout, responsive images, SEO, Metrika OK`);
        }
        await page.setViewport({width: 375, height: 900});
        await page.reload({waitUntil: 'networkidle0'});
        await page.click('[data-vf-menu-open]');
        assert.equal(await page.$eval('[data-vf-menu-dialog]', node => node.open), true);
        await page.click('[data-vf-menu-close]');
        await page.waitForFunction(() => !document.querySelector('[data-vf-menu-dialog]').open);
        await page.click('#cookieNecessary');
        await page.waitForFunction(() => document.querySelector('#cookieNotice').hidden);
        // Проверяем вызов цели в браузере, без передачи событий в production-счётчик.
        await page.evaluate(() => {
            window.seoGoals = [];
            window.ym = (...args) => window.seoGoals.push(args);
            document.querySelector('[data-vf-lead-form]').dispatchEvent(new Event('submit', {cancelable: true}));
        });
        await page.waitForFunction(() => !document.querySelector('[data-vf-lead-error]').hidden);
        assert.equal(await page.evaluate(() => window.seoGoals.length), 0);
        assert.equal(await page.$eval('[name="name"]', node => node.getAttribute('aria-invalid')), 'true');
        leadStatus = 201;
        await page.evaluate(() => document.querySelector('[data-vf-lead-form]').dispatchEvent(new Event('submit', {cancelable: true})));
        await page.waitForFunction(() => !document.querySelector('[data-vf-lead-success]').hidden);
        assert.deepEqual(await page.evaluate(() => window.seoGoals), [['105455340', 'reachGoal', 'lead_form_submit']]);
        console.log('Меню, cookie, цель заявки после 201 и отсутствие цели после 422: OK');
        for (const [path, status] of [['/about', 200], ['/services', 200], ['/services/finansovyy-direktor-na-autsorsinge', 200], ['/gazeta', 200], ['/privacy', 200], ['/seo-check-missing-page', 404]]) {
            const response = await page.goto('http://localhost:8001' + path, {waitUntil: 'domcontentloaded'});
            assert.equal(response.status(), status, path);
            console.log(`${path}: HTTP ${status}`);
        }
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
