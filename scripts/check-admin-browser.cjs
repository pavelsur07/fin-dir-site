// Реальный браузер + HTML, отрендеренный Symfony на откатываемых тестовых данных.
// Не отправляет формы и не обращается к production. Запуск: make admin-browser-check.
const fs = require('node:fs');
const assert = require('node:assert/strict');
const {chromium} = require('playwright');

const root = '/workspace/site/var/tests/admin-browser';
const names = ['login', 'dashboard', 'posts', 'cases', 'leads', 'post-form', 'case-form', 'lead-card'];
const origin = 'http://127.0.0.1:8001';

async function load(page, name) {
    await page.goto(`${origin}/__admin_fixture/${name}`, {waitUntil: 'networkidle'});
    await page.evaluate(() => document.fonts.ready);
}

async function checkPage(page, name) {
    const state = await page.evaluate(() => ({
        width: innerWidth,
        scroll: document.documentElement.scrollWidth,
        styles: [...document.querySelectorAll('link[rel="stylesheet"]')].map(el => el.getAttribute('href')),
        scripts: [...document.querySelectorAll('script[src]')].map(el => el.getAttribute('src')),
    }));
    assert.ok(state.scroll <= state.width + 1, `${name}: horizontal overflow ${JSON.stringify(state)}`);
    assert.ok(state.styles.length === 1 && state.styles[0].startsWith('/assets/admin/'), `${name}: CSS isolation`);
    assert.ok(state.scripts.every(src => src.startsWith('/assets/admin/')), `${name}: JS isolation`);
}

async function clickMenuButton(page, button) {
    await button.scrollIntoViewIfNeeded();
    await button.focus();
    // Даём завершиться прокрутке, вызванной автоматизацией, до открытия меню.
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await button.click();
}

async function checkMenu(page, name) {
    const table = page.locator('table');
    assert.ok(await table.evaluate(el => el.getBoundingClientRect().width >= 719), `${name}: readable table width`);
    const button = page.locator('button[data-admin-menu-toggle]').last();
    await button.scrollIntoViewIfNeeded();
    const id = await button.getAttribute('popovertarget');
    const menu = page.locator(`[id="${id}"]`);
    assert.equal(await menu.isVisible(), false, `${name}: menu initially hidden`);
    await button.focus();
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await page.keyboard.press('Enter');
    await menu.waitFor({state: 'visible'});
    await page.waitForFunction(id => {
        const box = document.getElementById(id).getBoundingClientRect();
        return box.left >= 15 && box.top >= 15 && box.right <= innerWidth - 15 && box.bottom <= innerHeight - 15;
    }, id, {timeout: 3000}).catch(async error => {
        console.error(name, await menu.evaluate(el => ({box: el.getBoundingClientRect().toJSON(), width: innerWidth, height: innerHeight, style: el.getAttribute("style"), display: getComputedStyle(el).display, position: getComputedStyle(el).position})));
        throw error;
    });
    await menu.evaluate(el => { el.scrollTop = el.scrollHeight; });
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    assert.ok(await menu.evaluate(el => el.matches(':popover-open')), `${name}: internal scrolling keeps menu open`);
    await menu.evaluate(el => { el.scrollTop = 0; });
    const itemStyles = await menu.locator('a, button').evaluateAll(items => items.map(item => {
        const style = getComputedStyle(item);
        return {
            text: item.textContent.trim(),
            border: style.borderTopWidth,
            align: style.textAlign,
            decoration: style.textDecorationLine,
            height: item.getBoundingClientRect().height,
        };
    }));
    for (const item of itemStyles) {
        assert.equal(item.border, '0px', `${name}: ${item.text} should look like a menu item`);
        assert.equal(item.align, 'left', `${name}: ${item.text} alignment`);
        assert.equal(item.decoration, 'none', `${name}: ${item.text} decoration`);
        assert.ok(item.height >= 44, `${name}: ${item.text} touch target`);
    }
    const topLayer = await menu.evaluate(el => {
        const box = el.getBoundingClientRect();
        const hit = document.elementFromPoint(box.left + box.width / 2, box.top + Math.min(25, box.height / 2));
        return el.matches(':popover-open') && (hit === el || el.contains(hit));
    });
    assert.ok(topLayer, `${name}: menu clipped or covered`);
    if (fs.existsSync('/artifacts') && name === 'leads 320x900') {
        await page.screenshot({path: '/artifacts/leads-menu-320.png'});
    }
    await page.keyboard.press('Tab');
    assert.ok(await menu.evaluate(el => el.contains(document.activeElement)), `${name}: Tab enters menu`);
    await page.keyboard.press('Escape');
    await menu.waitFor({state: 'hidden'});
    assert.ok(await button.evaluate(el => el === document.activeElement), `${name}: Escape restores focus`);
    await clickMenuButton(page, button);
    await menu.waitFor({state: 'visible'});
    const firstButton = page.locator('button[data-admin-menu-toggle]').first();
    await firstButton.focus();
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await page.keyboard.press('Enter');
    await menu.waitFor({state: 'hidden'});
    await page.locator('[data-admin-row-menu]:popover-open').waitFor({state: 'visible'});
    assert.equal(await page.locator('[data-admin-row-menu]:popover-open').count(), 1, `${name}: only one menu open`);
    await page.keyboard.press('Escape');
    await clickMenuButton(page, button);
    await menu.waitFor({state: 'visible'});
    await page.mouse.click(1, 1);
    await menu.waitFor({state: 'hidden'});
    await clickMenuButton(page, button);
    await menu.waitFor({state: 'visible'});
    // Scroll события таблицы закрывают меню даже если оно находится в top layer.
    await page.locator('.overflow-x-auto').evaluate(el => el.dispatchEvent(new Event('scroll')));
    await menu.waitFor({state: 'hidden'});
    await clickMenuButton(page, button);
    await menu.waitFor({state: 'visible'});
    const viewport = page.viewportSize();
    await page.setViewportSize({width: viewport.width, height: viewport.height + 1});
    await menu.waitFor({state: 'hidden'});
}

(async () => {
    const browser = await chromium.launch({headless: true, args: ['--no-sandbox']});
    let checks = 0;
    try {
        for (const viewport of [
            ...[320, 375, 768, 1024, 1440].map(width => ({width, height: 900})),
            {width: 375, height: 320},
        ]) {
            const context = await browser.newContext({viewport});
            const page = await context.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('response', response => {
                if (response.url().includes('/assets/') && response.status() >= 400) errors.push(`Asset HTTP ${response.status()}: ${response.url()}`);
            });
            await page.route('**/__admin_fixture/*', route => route.fulfill({
                contentType: 'text/html', body: fs.readFileSync(`${root}/${route.request().url().split('/').pop()}.html`, 'utf8'),
            }));
            for (const name of names) {
                await load(page, name);
                await checkPage(page, name);
                if (['posts', 'cases', 'leads'].includes(name)) {
                    await checkMenu(page, `${name} ${viewport.width}x${viewport.height}`);
                }
                if (name === 'post-form') {
                    await page.locator('[role="tab"]').first().focus();
                    await page.keyboard.press('End');
                    assert.equal(await page.locator('#post-tab-seo').getAttribute('aria-selected'), 'true');
                    assert.equal(await page.locator('#post-panel-seo').isVisible(), true);
                    assert.equal(await page.locator('#post-panel-main').isVisible(), false);
                }
                if (name === 'dashboard' && viewport.height === 320) {
                    const navigation = page.locator('[data-admin-mobile-menu]');
                    await navigation.locator('summary').click();
                    assert.ok(await navigation.evaluate(el => el.getBoundingClientRect().height <= innerHeight + 1));
                    await navigation.locator('button[type="submit"]').scrollIntoViewIfNeeded();
                    assert.ok(await navigation.locator('button[type="submit"]').isVisible());
                }
                if (fs.existsSync('/artifacts') && (
                    (viewport.width === 320 && ['login', 'posts', 'lead-card'].includes(name)) ||
                    (viewport.width === 1440 && name === 'post-form')
                )) await page.screenshot({path: `/artifacts/${name}-${viewport.width}.png`, fullPage: true});
                checks++;
            }
            console.log(`Admin ${viewport.width}x${viewport.height}: ${names.length} pages passed`);
            assert.deepEqual(errors, [], `Browser errors at ${viewport.width}x${viewport.height}`);
            await context.close();
        }

        // Без JS раскрытие остаётся в потоке, доступно клавиатурой и не обрезается.
        for (const javaScriptEnabled of [false, true]) {
            const fallback = await browser.newContext({javaScriptEnabled, viewport: {width: 320, height: 600}});
            if (javaScriptEnabled) await fallback.addInitScript(() => { delete HTMLElement.prototype.popover; });
            const page = await fallback.newPage();
            await page.route('**/__admin_fixture/*', route => route.fulfill({
                contentType: 'text/html', body: fs.readFileSync(`${root}/${route.request().url().split('/').pop()}.html`, 'utf8'),
            }));
            for (const name of ['posts', 'cases', 'leads']) {
                await load(page, name);
                const summary = page.locator('summary[data-admin-menu-toggle]').last();
                await summary.focus();
                await page.keyboard.press('Enter');
                const details = summary.locator('..');
                assert.notEqual(await details.getAttribute('open'), null, `${name}: details fallback`);
                const menu = details.locator('[data-admin-row-menu]');
                assert.equal(await menu.isVisible(), true);
                const geometry = await menu.evaluate(el => ({
                    position: getComputedStyle(el).position,
                    bottom: el.getBoundingClientRect().bottom,
                    containerBottom: el.closest('.overflow-x-auto').getBoundingClientRect().bottom,
                }));
                assert.equal(geometry.position, 'static');
                assert.ok(geometry.bottom <= geometry.containerBottom + 1, `${name}: fallback clipping`);
                checks++;
            }
            await fallback.close();
        }
        console.log(`Admin browser: ${checks} page/viewport checks passed; menus, keyboard, isolation, tabs, low screen, no-JS fallback.`);
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
