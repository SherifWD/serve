import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';

const [, , route = '/dashboard', outputPath, ...rest] = process.argv;

if (!outputPath) {
  console.error('Usage: node docs/scripts/capture_dashboard.mjs <route> <outputPath> [--open-order <id>] [--wait-ms <ms>]');
  process.exit(1);
}

const baseUrl = process.env.DASHBOARD_URL || 'http://127.0.0.1:4173';
const token = process.env.TOKEN;
const userJson = process.env.USER_JSON || JSON.stringify({
  id: 1,
  name: 'Main Owner',
  email: 'owner@example.com',
});
const requireFromCwd = createRequire(path.join(process.cwd(), 'capture-dashboard.cjs'));
const puppeteer = requireFromCwd('puppeteer-core');

if (!token) {
  console.error('TOKEN env var is required');
  process.exit(1);
}

let openOrderId = null;
let waitMs = 1500;

for (let i = 0; i < rest.length; i += 1) {
  if (rest[i] === '--open-order') {
    openOrderId = rest[i + 1];
    i += 1;
  } else if (rest[i] === '--wait-ms') {
    waitMs = Number(rest[i + 1] || 1500);
    i += 1;
  }
}

const chromePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const browser = await puppeteer.launch({
  executablePath: chromePath,
  headless: 'new',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
  defaultViewport: { width: 1440, height: 1400, deviceScaleFactor: 1 },
});

const page = await browser.newPage();

await page.evaluateOnNewDocument((authToken, authUser) => {
  localStorage.setItem('token', authToken);
  localStorage.setItem('user', authUser);
}, token, userJson);

await page.goto(`${baseUrl}${route}`, { waitUntil: 'networkidle2' });
await sleep(waitMs);

if (openOrderId) {
  const targetId = String(openOrderId);
  const opened = await page.evaluate((orderId) => {
    const row = Array.from(document.querySelectorAll('tr')).find((el) =>
      el.innerText.includes(`\n${orderId}\n`) ||
      el.innerText.startsWith(`${orderId}\n`) ||
      el.innerText.includes(`${orderId}\t`) ||
      el.innerText.split(/\s+/).includes(orderId)
    );

    if (!row) return false;

    const button = row.querySelector('button');
    if (!button) return false;
    button.click();
    return true;
  }, targetId);

  if (!opened) {
    console.error(`Could not open order row for ${targetId}`);
  }

  await sleep(1000);
}

await fs.mkdir(path.dirname(outputPath), { recursive: true });
await page.screenshot({ path: outputPath, fullPage: true });

await browser.close();
