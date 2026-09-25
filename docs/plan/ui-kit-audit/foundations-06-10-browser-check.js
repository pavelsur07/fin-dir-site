const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const outputDir = process.env.OUTPUT_DIR || __dirname;
const siteUrl = process.env.BASE_URL || 'http://127.0.0.1:8002';
const baselineUrl = process.env.BASELINE_URL;
const referenceUrl = process.env.REFERENCE_URL || 'file:///reference/Design%20System.dc.html';
const sections = [['06', 'radii'], ['07', 'spacing'], ['08', 'elevation'], ['09', 'icons'], ['10', 'components']];

(async () => {
  const browser = await chromium.launch({headless: true});
  const findings = [];
  for (const width of [320, 375, 768, 1024, 1440]) {
    const targets = [['reference', referenceUrl], ['current', siteUrl + '/ui-kit']];
    if (baselineUrl) targets.push(['baseline', baselineUrl + '/ui-kit']);
    for (const [kind, url] of targets) {
      const page = await browser.newPage({viewport: {width, height: 900}, deviceScaleFactor: 1});
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      page.on('response', response => { if (response.status() >= 400 && !response.url().includes('google-analytics')) errors.push(`${response.status()} ${response.url()}`); });
      if (kind !== 'reference') await page.addInitScript(() => localStorage.setItem('vf_cookie_notice_accepted', '1'));
      const response = await page.goto(url, {waitUntil: 'networkidle'});
      await page.evaluate(async () => {
        await Promise.all([document.fonts.load('400 16px Inter'), document.fonts.load('700 36px Manrope')]);
        await document.fonts.ready;
      });
      if (kind !== 'reference') await page.getByRole('link', {name: 'Перейти к содержанию'}).evaluate(link => { link.style.visibility = 'hidden'; });
      const metrics = await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        h1: document.querySelectorAll('h1').length,
        loadedFonts: [...new Set([...document.fonts].filter(font => font.status === 'loaded').map(font => font.family))],
      }));
      if (kind !== 'reference') {
        if (metrics.scrollWidth !== width) errors.push('document overflow');
        if (metrics.h1 !== 1) errors.push('H1 count');
        for (const family of ['Inter', 'Manrope']) if (!metrics.loadedFonts.includes(family)) errors.push(`font ${family} not loaded`);
      }
      for (let index = 0; index < sections.length; index++) {
        const [number, id] = sections[index];
        const section = kind === 'reference' ? page.locator('section').nth(6 + index) : page.locator('#' + id);
        if (await section.count() !== 1) { errors.push(`missing section ${number}`); continue; }
        await section.screenshot({path: path.join(outputDir, `foundations-${kind}-${width}-${number}.png`)});
      }
      if (kind === 'current') {
        const barWidths = await page.locator('[data-vf-ui-kit-spacing-row] i').evaluateAll(bars => bars.map(bar => bar.getBoundingClientRect().width));
        if (barWidths.length !== 10 || barWidths.some(width => width <= 0)) errors.push('spacing scale bars not visible');
        for (const name of ['wallet', 'credit-card', 'arrow-left-right', 'receipt', 'file-text', 'landmark', 'piggy-bank', 'trending-up', 'calendar', 'shield-check', 'bell', 'download']) {
          const image = page.locator(`#icons img[src*="/${name}.svg?v="]`).first();
          if (!await image.evaluate(img => img.complete && img.naturalWidth > 0)) errors.push(`icon ${name} not loaded`);
        }
      }
      findings.push({kind, width, status: response.status(), ...metrics, errors});
      await page.close();
    }
  }
  fs.writeFileSync(path.join(outputDir, 'foundations-06-10-findings.json'), JSON.stringify(findings, null, 2) + '\n');
  console.log(JSON.stringify(findings));
  await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
