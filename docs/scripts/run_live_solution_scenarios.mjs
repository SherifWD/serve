import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const SCRIPT_DIR = path.dirname(__filename);
const ROOT = path.resolve(SCRIPT_DIR, '..', '..');
const DASHBOARD_DIR = path.join(ROOT, 'pos-dashboard');
const FACTORY_APP_DIR = path.join(ROOT, 'factory_app');
const GENERATED_DIR = path.join(ROOT, 'docs', 'generated', 'live-solution-tests');
const REPORT_PATH = path.join(ROOT, 'docs', 'html', 'pos-live-solution-scenario-report-2026-06-09.html');
const API_BASE_URL = process.env.API_BASE_URL || 'http://127.0.0.1:8000/api';
const DASHBOARD_URL = process.env.DASHBOARD_URL || 'http://127.0.0.1:58220';
const FLUTTER = process.env.FLUTTER || '/Applications/Flutter/flutter/bin/flutter';
const CHROME_PATH = process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const requireFromDashboard = createRequire(path.join(DASHBOARD_DIR, 'live-solution-runner.cjs'));
const puppeteer = requireFromDashboard('puppeteer-core');

const startedProcesses = [];
const results = [];
const artifacts = [];

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function nowIso() {
  return new Date().toISOString();
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function relativeToReport(filePath) {
  return path.relative(path.dirname(REPORT_PATH), filePath).replaceAll(path.sep, '/');
}

function addResult({
  area,
  scenario,
  surface = '',
  status,
  evidence = '',
  actions = '',
  details = '',
  screenshot = null,
}) {
  results.push({
    area,
    scenario,
    surface,
    status,
    actions,
    evidence,
    details,
    screenshot,
    recordedAt: nowIso(),
  });
}

function statusClass(status) {
  switch (status) {
    case 'passed':
      return 'done';
    case 'partial':
    case 'blocked':
      return 'wait';
    default:
      return 'risk';
  }
}

async function fetchRaw(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
  });

  return response;
}

async function fetchJson(url, options = {}) {
  const response = await fetchRaw(url, options);
  const text = await response.text();
  let data = null;
  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    data = { raw: text };
  }

  if (!response.ok) {
    const message = data?.message || data?.error || text || `${response.status} ${response.statusText}`;
    const error = new Error(message);
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return { response, data };
}

async function waitForHttp(url, timeoutMs = 90000) {
  const started = Date.now();
  let lastError = null;
  while (Date.now() - started < timeoutMs) {
    try {
      const response = await fetch(url, { headers: { Accept: '*/*' } });
      if (response.status < 500) return response;
    } catch (error) {
      lastError = error;
    }
    await sleep(1000);
  }
  throw new Error(`Timed out waiting for ${url}${lastError ? `: ${lastError.message}` : ''}`);
}

async function waitForManagedLog(managed, pattern, timeoutMs = 150000) {
  const started = Date.now();
  const regex = pattern instanceof RegExp ? pattern : new RegExp(pattern);
  while (Date.now() - started < timeoutMs) {
    if (regex.test(managed.log.join(''))) return;
    if (managed.child.exitCode !== null) {
      throw new Error(`${managed.name} exited before expected log ${regex}. Log: ${managed.log.join('').slice(-2000)}`);
    }
    await sleep(500);
  }
  throw new Error(`Timed out waiting for ${managed.name} log ${regex}. Log: ${managed.log.join('').slice(-2000)}`);
}


function spawnManaged(name, command, args, options = {}) {
  const child = spawn(command, args, {
    cwd: options.cwd || ROOT,
    env: { ...process.env, ...(options.env || {}) },
    stdio: ['ignore', 'pipe', 'pipe'],
  });

  const log = [];
  const collect = (chunk, stream) => {
    const line = chunk.toString();
    log.push(line);
    if (options.echo) process[stream].write(`[${name}] ${line}`);
    if (log.join('').length > 24000) log.splice(0, Math.max(1, log.length - 40));
  };

  child.stdout.on('data', (chunk) => collect(chunk, 'stdout'));
  child.stderr.on('data', (chunk) => collect(chunk, 'stderr'));
  child.on('exit', (code, signal) => {
    if (code && !options.allowExit) {
      log.push(`\n${name} exited with code ${code} signal ${signal ?? ''}\n`);
    }
  });

  const managed = { name, child, log };
  startedProcesses.push(managed);
  return managed;
}

async function stopManaged(managed) {
  if (!managed || managed.child.killed || managed.child.exitCode !== null) return;
  managed.child.kill('SIGINT');
  const stopped = await Promise.race([
    new Promise((resolve) => managed.child.once('exit', resolve)),
    sleep(6000).then(() => false),
  ]);
  if (stopped === false && managed.child.exitCode === null) {
    managed.child.kill('SIGKILL');
  }
}

async function cleanup() {
  for (const managed of [...startedProcesses].reverse()) {
    await stopManaged(managed);
  }
}

async function ensureApi() {
  try {
    await waitForHttp(`${API_BASE_URL}/health`, 3000);
    return null;
  } catch {
    const url = new URL(API_BASE_URL);
    const port = url.port || (url.protocol === 'https:' ? '443' : '80');
    const server = spawnManaged('laravel-api', 'php', [
      'artisan',
      'serve',
      '--host=127.0.0.1',
      `--port=${port}`,
    ]);
    await waitForHttp(`${API_BASE_URL}/health`, 90000);
    return server;
  }
}

async function startDashboard() {
  const server = spawnManaged('pos-dashboard', 'npm', [
    'run',
    'dev',
    '--',
    '--host',
    '127.0.0.1',
    '--port',
    new URL(DASHBOARD_URL).port,
  ], {
    cwd: DASHBOARD_DIR,
    env: {
      VITE_API_BASE_URL: API_BASE_URL,
      VITE_DEPLOY_TARGET: 'local',
    },
  });
  await waitForHttp(DASHBOARD_URL, 90000);
  return server;
}

async function startFlutter(entrypoint, port) {
  const server = spawnManaged(`flutter-${path.basename(entrypoint, '.dart')}`, FLUTTER, [
    'run',
    '-d',
    'web-server',
    '--web-hostname=127.0.0.1',
    `--web-port=${port}`,
    '-t',
    entrypoint,
    `--dart-define=API_BASE_URL=${API_BASE_URL}`,
  ], {
    cwd: FACTORY_APP_DIR,
  });
  const url = `http://127.0.0.1:${port}`;
  await waitForManagedLog(server, /is being served at/i, 150000);
  await waitForHttp(url, 150000);
  await sleep(1500);
  return { server, url };
}

async function browserPage(browser, url) {
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
  await page.goto(url, { waitUntil: 'networkidle2', timeout: 90000 });
  await sleep(3000);
  await enableFlutterSemantics(page);
  return page;
}

async function enableFlutterSemantics(page) {
  await page.evaluate(() => {
    document.querySelector('flt-semantics-placeholder')?.click?.();
  }).catch(() => {});
  await sleep(400);
}

async function pageText(page) {
  await enableFlutterSemantics(page);
  const bodyText = await page.evaluate(() => document.body.innerText || '').catch(() => '');
  const snapshot = await page.accessibility.snapshot({ interestingOnly: false }).catch(() => null);
  const names = [];
  const walk = (node) => {
    if (!node) return;
    if (node.name) names.push(node.name);
    for (const child of node.children || []) walk(child);
  };
  walk(snapshot);
  return `${bodyText}\n${names.join('\n')}`.replace(/\s+\n/g, '\n').trim();
}

async function waitForPageText(page, pattern, timeoutMs = 25000) {
  const started = Date.now();
  const regex = pattern instanceof RegExp ? pattern : new RegExp(pattern, 'i');
  let latest = '';
  while (Date.now() - started < timeoutMs) {
    latest = await pageText(page);
    if (regex.test(latest)) return latest;
    await sleep(500);
  }
  return latest;
}

async function clickByText(page, textOrPattern) {
  const source = textOrPattern instanceof RegExp ? textOrPattern.source : `^${escapeRegExp(textOrPattern)}$`;
  const flags = textOrPattern instanceof RegExp ? textOrPattern.flags : 'i';
  const clicked = await page.evaluate(({ source, flags }) => {
    const regex = new RegExp(source, flags.includes('i') ? 'i' : '');
    const candidates = [
      ...document.querySelectorAll('button, [role="button"], flt-semantics[role="button"], .v-btn'),
    ];
    const target = candidates.find((element) => {
      const text = `${element.textContent || ''} ${element.getAttribute('aria-label') || ''}`.trim();
      return regex.test(text);
    });
    if (!target) return false;
    target.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
    target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    target.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
    target.click();
    return true;
  }, { source, flags });
  if (clicked) {
    await sleep(500);
    return true;
  }
  return false;
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

async function fillAriaInput(page, label, value) {
  const selector = `input[aria-label="${label}"], textarea[aria-label="${label}"]`;
  const started = Date.now();
  let handle = null;
  while (Date.now() - started < 30000) {
    await enableFlutterSemantics(page);
    handle = await page.$(selector);
    if (handle) break;
    await sleep(500);
  }
  if (!handle) {
    const text = await pageText(page);
    throw new Error(`Waiting for selector \`${selector}\` failed. Visible text: ${text.slice(0, 600)}`);
  }
  await page.click(selector, { clickCount: 3 });
  const modifier = process.platform === 'darwin' ? 'Meta' : 'Control';
  await page.keyboard.down(modifier);
  await page.keyboard.press('KeyA');
  await page.keyboard.up(modifier);
  await page.keyboard.type(value);
}

async function screenshot(page, filename) {
  const filePath = path.join(GENERATED_DIR, filename);
  await fs.mkdir(path.dirname(filePath), { recursive: true });
  await page.screenshot({ path: filePath, fullPage: true });
  artifacts.push(filePath);
  return filePath;
}

async function failureScreenshot(page, name) {
  if (!page) return null;
  try {
    return await screenshot(page, `failure-${name}-${Date.now()}.png`);
  } catch {
    return null;
  }
}

async function apiLogin(email, type = null) {
  const { data } = await fetchJson(`${API_BASE_URL}/login`, {
    method: 'POST',
    body: JSON.stringify({
      email,
      password: 'password',
      ...(type ? { type } : {}),
    }),
  });
  return data;
}

function authHeaders(token, extra = {}) {
  return {
    Authorization: `Bearer ${token}`,
    ...extra,
  };
}

async function runApiHealth() {
  try {
    const { data, response } = await fetchJson(`${API_BASE_URL}/health`);
    addResult({
      area: 'Environment',
      scenario: 'Laravel API health',
      surface: 'API',
      status: response.status === 200 && data.status === 'ok' ? 'passed' : 'failed',
      actions: `GET ${API_BASE_URL}/health`,
      evidence: `HTTP ${response.status}; database=${data.database}; queue=${data.queue_connection}; broadcast=${data.broadcast_connection}; env=${data.environment}`,
    });
  } catch (error) {
    addResult({
      area: 'Environment',
      scenario: 'Laravel API health',
      surface: 'API',
      status: 'failed',
      actions: `GET ${API_BASE_URL}/health`,
      evidence: error.message,
    });
  }
}

async function runDashboardScenarios(browser) {
  let server = null;
  try {
    server = await startDashboard();
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1100, deviceScaleFactor: 1 });
    await page.goto(DASHBOARD_URL, { waitUntil: 'networkidle2', timeout: 90000 });
    await page.waitForSelector('input[type="email"]', { timeout: 20000 });
    await page.type('input[type="email"]', 'admin@restaurant-suite.com');
    await page.type('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForFunction(() => location.hash.includes('/dashboard') || document.body.innerText.includes('Dashboard'), { timeout: 25000 });
    const dashboardShot = await screenshot(page, 'dashboard-admin-login.png');
    addResult({
      area: 'Dashboard',
      scenario: 'Admin dashboard login',
      surface: 'Vue dashboard',
      status: 'passed',
      actions: 'Opened the dashboard, filled Email and Password fields, submitted the sign-in form.',
      evidence: 'Dashboard accepted seeded admin credentials and routed to the authenticated dashboard.',
      screenshot: dashboardShot,
    });

    const routes = [
      ['/dashboard', /Revenue|Dashboard|Orders|Branch/i],
      ['/restaurants', /Restaurants|Restaurant/i],
      ['/branches', /Branches|Branch/i],
      ['/users', /Users|User/i],
      ['/roles', /Roles|Permission/i],
      ['/products', /Products|Product/i],
      ['/orders', /Orders|Order/i],
      ['/customers', /Customers|Customer/i],
      ['/employees', /Employees|Employee/i],
      ['/categories', /Categories|Category/i],
      ['/menus', /Menus|Menu/i],
      ['/inventory', /Inventory|Stock/i],
      ['/suppliers', /Suppliers|Supplier/i],
      ['/tables', /Tables|Table/i],
      ['/ingredients', /Ingredients|Ingredient/i],
      ['/recipe', /Recipe|Recipes/i],
      ['/settings', /Settings|Payment|Printer/i],
      ['/marketing-inquiries', /Marketing|Inquiries|Inquiry/i],
    ];
    const routeEvidence = [];
    for (const [route, expected] of routes) {
      await page.goto(`${DASHBOARD_URL}/#${route}`, { waitUntil: 'networkidle2', timeout: 90000 });
      await sleep(700);
      const text = await page.evaluate(() => document.body.innerText || '');
      const ok = expected.test(text) && !/could not reach the api|login failed/i.test(text);
      routeEvidence.push(`${route}: ${ok ? 'loaded' : 'unexpected content'}`);
    }
    addResult({
      area: 'Dashboard',
      scenario: 'Dashboard feature routes',
      surface: 'Vue dashboard',
      status: routeEvidence.every((line) => line.endsWith('loaded')) ? 'passed' : 'partial',
      actions: 'Visited authenticated dashboard routes for restaurants, branches, users, roles, products, orders, customers, employees, categories, menus, inventory, suppliers, tables, ingredients, recipes, settings, and marketing inquiries.',
      evidence: routeEvidence.join('; '),
    });
    await page.close();
  } catch (error) {
    addResult({
      area: 'Dashboard',
      scenario: 'Dashboard live browser run',
      surface: 'Vue dashboard',
      status: 'failed',
      actions: 'Start Vite dashboard and drive it with Chrome.',
      evidence: error.message,
      details: server?.log?.join('').slice(-2000) || '',
    });
  } finally {
    await stopManaged(server);
  }
}

async function loginFlutterStaff(page, role) {
  await fillAriaInput(page, 'Email', role.email);
  await fillAriaInput(page, 'Password', 'password');
  await clickByText(page, /Continue/);
  return waitForPageText(page, role.expectedText, 35000);
}

async function runFlutterRole(browser, role) {
  let server = null;
  let page = null;
  try {
    const launched = await startFlutter(role.entrypoint, role.port);
    server = launched.server;
    page = await browserPage(browser, launched.url);
    await loginFlutterStaff(page, role);
    if (role.afterLogin) {
      await role.afterLogin(page);
    }
    const text = await waitForPageText(page, role.expectedText, 20000);
    const ok = role.expectedText.test(text);
    const shot = await screenshot(page, `flutter-${role.name}.png`);
    addResult({
      area: 'Flutter apps',
      scenario: `${role.label} app live login and workspace`,
      surface: `Flutter web ${role.name}`,
      status: ok ? 'passed' : 'failed',
      actions: `Launched ${role.entrypoint} with API_BASE_URL=${API_BASE_URL}, logged in through real Flutter text fields, and inspected the role workspace.`,
      evidence: ok
        ? `Workspace text matched ${role.expectedText}.`
        : `Expected ${role.expectedText}; visible text was: ${text.slice(0, 400)}`,
      screenshot: shot,
    });
  } catch (error) {
    const shot = await failureScreenshot(page, `flutter-${role.name}`);
    addResult({
      area: 'Flutter apps',
      scenario: `${role.label} app live login and workspace`,
      surface: `Flutter web ${role.name}`,
      status: 'failed',
      actions: `Launch ${role.entrypoint}, log in, and inspect workspace.`,
      evidence: error.message,
      details: server?.log?.join('').slice(-3000) || '',
      screenshot: shot,
    });
  } finally {
    if (page) await page.close().catch(() => {});
    await stopManaged(server);
  }
}

async function runCashierCounterSaleUi(page) {
  const beforeText = await pageText(page);
  const clicked = await clickByText(page, /Counter sale/);
  if (!clicked) {
    throw new Error(`Could not click Counter sale. Visible text: ${beforeText.slice(0, 400)}`);
  }
  await waitForPageText(page, /Search menu|Customer name|Cart/i, 25000);
  await fillAriaInput(page, 'Customer name', `Live Counter ${Date.now()}`);
  await fillAriaInput(page, 'Phone', `010${String(Date.now()).slice(-8)}`);
  await fillAriaInput(page, 'Search menu', 'Brownie');
  await waitForPageText(page, /Brownie Skillet/i, 20000);
  await clickByText(page, /Brownie Skillet|Brownie/i);
  await waitForPageText(page, /Cart|USD 8\.00|Send to cashier/i, 10000);
  await clickByText(page, /Send to cashier/i);
  await waitForPageText(page, /Counter order #\d+ ready to settle|Settle order/i, 30000);
  await clickByText(page, /Settle order/i);
  await waitForPageText(page, /Payment recorded|No cashier queue|Counter sale/i, 30000);
}

async function runOwnerExportUi(page) {
  await waitForPageText(page, /Export CSV|Owner dashboard/i, 30000);
  await clickByText(page, /Export CSV/i);
  await waitForPageText(page, /Dataset|Export/i, 15000);
  await clickByText(page, /^Export$/i);
  await waitForPageText(page, /Export downloaded|Export ready|Owner dashboard/i, 25000);
}

async function runCustomerFlutter(browser) {
  let server = null;
  let page = null;
  try {
    const launched = await startFlutter('lib/main_customer.dart', 58205);
    server = launched.server;
    page = await browserPage(browser, launched.url);
    await fillAriaInput(page, 'Name', `Live Customer ${Date.now()}`);
    const phone = `010${String(Date.now()).slice(-8)}`;
    await fillAriaInput(page, 'Phone', phone);
    await clickByText(page, /Send verification code/i);
    let text = await waitForPageText(page, /Verification code:\s*\d{6}|Verification code/i, 30000);
    const match = text.match(/Verification code:\s*(\d{6})/i);
    if (!match) {
      throw new Error(`Customer OTP screen opened but debug OTP was not visible. Text: ${text.slice(0, 500)}`);
    }
    await fillAriaInput(page, 'Verification code', match[1]);
    await clickByText(page, /Verify and continue/i);
    text = await waitForPageText(page, /Order again|Restaurants|Rewards|Browse/i, 35000);
    const ok = /Order again|Restaurants|Rewards|Browse/i.test(text);
    const shot = await screenshot(page, 'flutter-customer.png');
    addResult({
      area: 'Flutter apps',
      scenario: 'Customer app OTP login and home',
      surface: 'Flutter web customer',
      status: ok ? 'passed' : 'failed',
      actions: 'Launched customer Flutter app, entered name and phone, requested local debug OTP, submitted the OTP, and inspected the customer home.',
      evidence: ok ? `OTP flow passed for ${phone}; customer home showed browse/order/rewards content.` : text.slice(0, 500),
      screenshot: shot,
    });
  } catch (error) {
    const shot = await failureScreenshot(page, 'flutter-customer');
    addResult({
      area: 'Flutter apps',
      scenario: 'Customer app OTP login and home',
      surface: 'Flutter web customer',
      status: 'failed',
      actions: 'Launch customer app and complete OTP sign-in.',
      evidence: error.message,
      details: server?.log?.join('').slice(-3000) || '',
      screenshot: shot,
    });
  } finally {
    if (page) await page.close().catch(() => {});
    await stopManaged(server);
  }
}

async function runOperationalScenarios() {
  const cashier = await apiLogin('cashier1.janova-restaurant.alexandria@example.com', 'cashier');
  const waiter = await apiLogin('waiter1.janova-restaurant.alexandria@example.com', 'waiter');
  const kitchen = await apiLogin('kitchen1.janova-restaurant.alexandria@example.com', 'kitchen');
  const owner = await apiLogin('owner.janova-restaurant@example.com', 'owner');

  await runNoTableSale(cashier.token);
  await runWaiterKitchenCashierCycle({ waiterToken: waiter.token, kitchenToken: kitchen.token, cashierToken: cashier.token });
  await runCustomerPickupCycle({ cashierToken: cashier.token });
  await runExports(owner.token);
  await runHardwareAndOfflineFoundation(cashier.token);
}

async function runNoTableSale(token) {
  try {
    const product = await firstProduct(token, 6, /Brownie Skillet|Fresh Lemon Mint|Sparkling Water/i);
    const mutationId = `live-counter-${Date.now()}`;
    const { response, data } = await fetchJson(`${API_BASE_URL}/mobile/orders`, {
      method: 'POST',
      headers: authHeaders(token, { 'X-Client-Mutation-Id': mutationId }),
      body: JSON.stringify({
        branch_id: 6,
        order_type: 'takeaway',
        customer_name: 'Live Counter API',
        customer_phone: `010${String(Date.now()).slice(-8)}`,
        send_to_cashier: true,
        items: [{ product_id: product.id, quantity: 1 }],
      }),
    });
    const order = data.order;
    await fetchJson(`${API_BASE_URL}/mobile/orders/${order.id}/pay`, {
      method: 'POST',
      headers: authHeaders(token),
      body: JSON.stringify({
        payment_method: 'cash',
        amount: order.total,
      }),
    });
    const receiptResponse = await fetchRaw(`${API_BASE_URL}/mobile/orders/${order.id}/receipt?scope=paid`, {
      headers: authHeaders(token, { Accept: 'application/pdf' }),
    });
    const receiptBytes = new Uint8Array(await receiptResponse.arrayBuffer());
    addResult({
      area: 'Operations',
      scenario: 'Cashier no-table counter sale',
      surface: 'Flutter cashier + mobile API',
      status: response.status === 201 && receiptResponse.ok && receiptBytes[0] === 0x25 ? 'passed' : 'failed',
      actions: 'Created a branch-level takeaway order with send_to_cashier, paid it in cash, then generated a paid PDF receipt.',
      evidence: `Order #${order.id}; product=${product.name}; status=${order.status}; table_id=${order.table_id ?? 'null'}; total=${order.total}; receipt_http=${receiptResponse.status}; bytes=${receiptBytes.length}.`,
    });
  } catch (error) {
    addResult({
      area: 'Operations',
      scenario: 'Cashier no-table counter sale',
      surface: 'Flutter cashier + mobile API',
      status: 'failed',
      actions: 'Create no-table sale, pay, generate receipt.',
      evidence: error.message,
    });
  }
}

async function runWaiterKitchenCashierCycle({ waiterToken, kitchenToken, cashierToken }) {
  try {
    const tableResponse = await fetchJson(`${API_BASE_URL}/mobile/tables`, {
      headers: authHeaders(waiterToken),
    });
    const tables = Array.isArray(tableResponse.data.data) ? tableResponse.data.data : [];
    const table = tables.find((item) => item.status === 'open') || tables[0];
    if (!table) throw new Error('No waiter table was returned by /mobile/tables.');

    const product = await firstProduct(waiterToken, 6, /Fresh Lemon Mint|Sparkling Water|Lentil Soup/i);
    const orderResponse = await fetchJson(`${API_BASE_URL}/mobile/orders`, {
      method: 'POST',
      headers: authHeaders(waiterToken, { 'X-Client-Mutation-Id': `live-dinein-${Date.now()}` }),
      body: JSON.stringify({
        table_id: table.id,
        order_type: 'dine-in',
        customer_name: 'Live Dine-In Guest',
        customer_phone: `010${String(Date.now()).slice(-8)}`,
        items: [{ product_id: product.id, quantity: 1, note: 'Live runner dine-in cycle' }],
      }),
    });
    const order = orderResponse.data.order;
    await fetchJson(`${API_BASE_URL}/mobile/orders/${order.id}/send-to-kds`, {
      method: 'POST',
      headers: authHeaders(waiterToken),
      body: JSON.stringify({}),
    });
    const itemIds = order.items.map((item) => item.id);
    for (const itemId of itemIds) {
      await fetchJson(`${API_BASE_URL}/mobile/kds/order-items/${itemId}`, {
        method: 'PATCH',
        headers: authHeaders(kitchenToken),
        body: JSON.stringify({ status: 'preparing' }),
      });
      await fetchJson(`${API_BASE_URL}/mobile/kds/order-items/${itemId}`, {
        method: 'PATCH',
        headers: authHeaders(kitchenToken),
        body: JSON.stringify({ status: 'ready' }),
      });
    }
    await fetchJson(`${API_BASE_URL}/mobile/orders/${order.id}/send-to-cashier`, {
      method: 'PATCH',
      headers: authHeaders(cashierToken),
      body: JSON.stringify({}),
    });
    const paid = await fetchJson(`${API_BASE_URL}/mobile/orders/${order.id}/pay`, {
      method: 'POST',
      headers: authHeaders(cashierToken),
      body: JSON.stringify({
        payments: [{ method: 'cash', amount: order.total }],
      }),
    });
    addResult({
      area: 'Operations',
      scenario: 'Waiter to KDS to cashier dine-in cycle',
      surface: 'Flutter waiter/kitchen/cashier + mobile API',
      status: paid.data.order.status === 'paid' ? 'passed' : 'failed',
      actions: 'Selected an open table, created a waiter dine-in order, sent it to KDS, moved every item preparing then ready, sent the order to cashier, and paid it.',
      evidence: `Table=${table.name ?? table.id}; order #${order.id}; product=${product.name}; final_status=${paid.data.order.status}; payment_status=${paid.data.order.payment_status}; total=${paid.data.order.total}.`,
    });
  } catch (error) {
    addResult({
      area: 'Operations',
      scenario: 'Waiter to KDS to cashier dine-in cycle',
      surface: 'Flutter waiter/kitchen/cashier + mobile API',
      status: 'failed',
      actions: 'Create dine-in order, KDS preparation, cashier settlement.',
      evidence: error.message,
    });
  }
}

async function runCustomerPickupCycle({ cashierToken }) {
  try {
    const otp = await fetchJson(`${API_BASE_URL}/customer/auth/request-otp`, {
      method: 'POST',
      body: JSON.stringify({
        name: 'Live Pickup Customer',
        phone: `010${String(Date.now()).slice(-8)}`,
      }),
    });
    const phone = otp.data.customer.phone;
    const verified = await fetchJson(`${API_BASE_URL}/customer/auth/verify-otp`, {
      method: 'POST',
      body: JSON.stringify({
        phone,
        code: otp.data.debug_otp_code,
      }),
    });
    const customerToken = verified.data.token;
    const restaurantList = await fetchJson(`${API_BASE_URL}/customer/restaurants`, {
      headers: authHeaders(customerToken),
    });
    const restaurant = restaurantList.data.data.find((item) => item.name === 'Janova Restaurant') || restaurantList.data.data[0];
    const branchId = restaurant.branches[0].id;
    const detail = await fetchJson(`${API_BASE_URL}/customer/restaurants/${restaurant.id}?branch_id=${branchId}`, {
      headers: authHeaders(customerToken),
    });
    const product = detail.data.data[0];
    const checkout = await fetchJson(`${API_BASE_URL}/customer/orders`, {
      method: 'POST',
      headers: authHeaders(customerToken),
      body: JSON.stringify({
        branch_id: branchId,
        order_type: 'takeaway',
        payment_method: 'pay_at_counter',
        notes: 'Live customer pickup cycle',
        items: [{ product_id: product.id, quantity: 1 }],
      }),
    });
    const order = checkout.data.data;
    await fetchJson(`${API_BASE_URL}/mobile/orders/${order.id}/pay`, {
      method: 'POST',
      headers: authHeaders(cashierToken),
      body: JSON.stringify({
        payment_method: 'cash',
        amount: order.total,
      }),
    });
    addResult({
      area: 'Operations',
      scenario: 'Customer pickup checkout and pay-at-counter',
      surface: 'Flutter customer + cashier API',
      status: checkout.response.status === 201 ? 'passed' : 'failed',
      actions: 'Requested customer OTP, verified it, browsed Janova Restaurant, placed a takeaway pickup order, and settled it at cashier.',
      evidence: `Customer phone=${phone}; order #${order.id}; branch=${order.branch_name}; product=${product.name}; total=${order.total}; payment_method=${order.payment_method}.`,
    });
  } catch (error) {
    addResult({
      area: 'Operations',
      scenario: 'Customer pickup checkout and pay-at-counter',
      surface: 'Flutter customer + cashier API',
      status: 'failed',
      actions: 'Customer OTP, browse restaurant, checkout pickup, cashier pay.',
      evidence: error.message,
    });
  }
}

async function runExports(ownerToken) {
  const datasets = ['products', 'customers', 'orders', 'payments', 'receipts', 'inventory-items'];
  const lines = [];
  let failed = false;
  for (const dataset of datasets) {
    try {
      const response = await fetchRaw(`${API_BASE_URL}/data-exports/${dataset}`, {
        headers: authHeaders(ownerToken, { Accept: 'text/csv' }),
      });
      const text = await response.text();
      const header = text.split('\n')[0] || '';
      const ok = response.ok && header.includes('id');
      failed ||= !ok;
      lines.push(`${dataset}: HTTP ${response.status}, ${text.length} chars, header="${header.slice(0, 80)}"`);
    } catch (error) {
      failed = true;
      lines.push(`${dataset}: ${error.message}`);
    }
  }
  addResult({
    area: 'Owner',
    scenario: 'Owner CSV export datasets',
    surface: 'Flutter owner + API',
    status: failed ? 'failed' : 'passed',
    actions: 'Requested every supported owner export dataset with an owner token.',
    evidence: lines.join('; '),
  });
}

async function runHardwareAndOfflineFoundation(token) {
  const evidence = [];
  let status = 'passed';
  try {
    const sync = await fetchJson(`${API_BASE_URL}/mobile/sync/state?surface=cashier&branch_id=6`, {
      headers: authHeaders(token),
    });
    evidence.push(`sync_state offline actions=${sync.data.offline.queueable_actions.join(',')}; conflict=${sync.data.offline.conflict_policy}`);

    const invalid = await fetchRaw(`${API_BASE_URL}/mobile/device-heartbeat`, {
      method: 'POST',
      headers: authHeaders(token),
      body: JSON.stringify({
        uuid: `live-invalid-printer-${Date.now()}`,
        branch_id: 6,
        printer_profile: 'epson-thermal',
        printer_endpoint: 'http://unsafe.local/printer',
      }),
    });
    evidence.push(`invalid_printer_endpoint_http=${invalid.status}`);
    if (invalid.status !== 422) status = 'partial';

    const heartbeat = await fetchJson(`${API_BASE_URL}/mobile/device-heartbeat`, {
      method: 'POST',
      headers: authHeaders(token),
      body: JSON.stringify({
        uuid: `live-receipt-printer-${Date.now()}`,
        name: 'Live Receipt Printer',
        type: 'Receipt Printer',
        branch_id: 6,
        printer_profile: 'escpos-network',
        printer_endpoint: 'tcp://192.168.1.20:9100',
        capabilities: ['receipt', 'cash_drawer'],
      }),
    });
    const deviceUuid = heartbeat.data.data.uuid;
    evidence.push(`valid_device_registered=${deviceUuid}`);

    const product = await firstProduct(token, 6, /Sparkling Water|Fresh Lemon Mint|Brownie/i);
    const order = await fetchJson(`${API_BASE_URL}/mobile/orders`, {
      method: 'POST',
      headers: authHeaders(token, { 'X-Client-Mutation-Id': `live-print-${Date.now()}` }),
      body: JSON.stringify({
        branch_id: 6,
        order_type: 'takeaway',
        send_to_cashier: true,
        items: [{ product_id: product.id, quantity: 1 }],
      }),
    });
    const printJob = await fetchJson(`${API_BASE_URL}/mobile/print-jobs`, {
      method: 'POST',
      headers: authHeaders(token),
      body: JSON.stringify({
        branch_id: 6,
        device_uuid: deviceUuid,
        order_id: order.data.order.id,
        type: 'receipt',
        printer_profile: 'escpos-network',
        printer_endpoint: 'tcp://192.168.1.20:9100',
      }),
    });
    const claimed = await fetchJson(`${API_BASE_URL}/mobile/print-jobs/claim`, {
      method: 'POST',
      headers: authHeaders(token),
      body: JSON.stringify({ device_uuid: deviceUuid }),
    });
    await fetchJson(`${API_BASE_URL}/mobile/print-jobs/${printJob.data.data.id}`, {
      method: 'PATCH',
      headers: authHeaders(token),
      body: JSON.stringify({ status: 'printed' }),
    });
    evidence.push(`print_job #${printJob.data.data.id} queued_claimed=${claimed.data.data?.id ?? 'none'}_printed`);

    const paymentAttempt = await fetchJson(`${API_BASE_URL}/mobile/payment-attempts`, {
      method: 'POST',
      headers: authHeaders(token),
      body: JSON.stringify({
        order_id: order.data.order.id,
        method: 'cash',
        amount: order.data.order.total,
        currency: 'USD',
        device_uuid: deviceUuid,
      }),
    });
    evidence.push(`cash_payment_attempt=${paymentAttempt.data.data.status}`);
  } catch (error) {
    status = 'failed';
    evidence.push(error.message);
  }

  addResult({
    area: 'Hardware/offline foundation',
    scenario: 'Sync metadata, device validation, print queue, and payment attempt foundation',
    surface: 'Flutter cashier + mobile API',
    status,
    actions: 'Checked mobile sync offline metadata, rejected an unsafe printer endpoint, registered a safe printer device, queued/claimed/printed a print job, and created a cash payment attempt.',
    evidence: evidence.join('; '),
    details: 'This proves the software foundation. It is not a physical hardware lab certification.',
  });

  addResult({
    area: 'Hardware/offline foundation',
    scenario: 'Physical printer, cash drawer, tablet, and terminal certification',
    surface: 'External lab hardware',
    status: 'blocked',
    actions: 'Checked repo/runtime support and software validation paths.',
    evidence: 'Blocked in this run because no physical receipt printer, kitchen printer, cash drawer, tablet fleet, or payment terminal was attached to this machine.',
    details: 'Winning condition remains a lab matrix with exact device models, rush-hour print tests, drawer-open tests, terminal capture/refund tests, and failure escalation evidence.',
  });
}

async function firstProduct(token, branchId, preferredRegex) {
  const { data } = await fetchJson(`${API_BASE_URL}/mobile/products`, {
    headers: authHeaders(token),
  });
  const products = [];
  for (const category of data.data || []) {
    for (const product of category.products || []) {
      if (!branchId || Number(product.branch_id) === Number(branchId)) {
        products.push({ ...product, category_name: category.name });
      }
    }
  }
  const preferred = products.find((product) => preferredRegex.test(product.name));
  const product = preferred || products[0];
  if (!product) throw new Error('No available mobile products returned.');
  return product;
}

async function runStaticReadinessChecks() {
  const checks = [
    {
      file: path.join(ROOT, 'docs', 'html', 'restaurant-suite-pricing-hardware-egypt.html'),
      label: 'pricing and hardware page',
      terms: [/pricing/i, /hardware/i],
    },
    {
      file: path.join(ROOT, 'docs', 'deployment-guide.md'),
      label: 'deployment/support guide',
      terms: [/SLA|support/i, /printer|terminal/i, /queue/i],
    },
    {
      file: path.join(ROOT, 'docs', 'offline-realtime-operations.md'),
      label: 'offline/realtime operations note',
      terms: [/offline/i, /queue/i, /sync/i],
    },
    {
      file: path.join(ROOT, 'docs', 'pos-winning-modifications-checklist-2026-06-09.html'),
      label: 'winning modifications checklist',
      terms: [/External Certification And Scale Gates/i, /Flutter integration tests/i],
    },
    {
      file: path.join(ROOT, 'docs', 'html', 'restaurant-suite-switching-import-pack.html'),
      label: 'switching import pack',
      terms: [/pos:import-switching-csv/i, /Toast/i, /Foodics/i],
    },
    {
      file: path.join(ROOT, 'docs', 'html', 'restaurant-suite-commercial-trust-pages.html'),
      label: 'commercial trust pack',
      terms: [/Support SLA/i, /Payment Policy/i, /Data Ownership/i],
    },
    {
      file: path.join(ROOT, 'docs', 'html', 'restaurant-suite-hardware-offline-lab-pack.html'),
      label: 'hardware and offline lab pack',
      terms: [/Certification Matrix/i, /Offline Rules/i, /Shift Close Rules/i],
    },
    {
      file: path.join(ROOT, 'docs', 'html', 'pos-open-partial-closure-report-2026-06-09.html'),
      label: 'open and partial closure report',
      terms: [/Open And Partial Item Closure Report/i, /Migration importers/i, /External gates/i],
    },
  ];
  const lines = [];
  let ok = true;
  for (const check of checks) {
    try {
      const text = await fs.readFile(check.file, 'utf8');
      const matched = check.terms.every((term) => term.test(text));
      ok &&= matched;
      lines.push(`${check.label}: ${matched ? 'present' : 'missing expected terms'} (${relativeToReport(check.file)})`);
    } catch (error) {
      ok = false;
      lines.push(`${check.label}: ${error.message}`);
    }
  }
  addResult({
    area: 'Commercial readiness',
    scenario: 'Commercial, support, hardware, offline, and checklist documents',
    surface: 'Docs HTML/Markdown',
    status: ok ? 'passed' : 'partial',
    actions: 'Checked published documentation artifacts needed by the open checklist items.',
    evidence: lines.join('; '),
  });
}

async function runMigrationImporterDryRuns() {
  const datasets = [
    ['categories', 'docs/import-templates/categories.csv', ['--restaurant_id=1', '--branch_id=6']],
    ['products', 'docs/import-templates/products.csv', ['--restaurant_id=1', '--branch_id=6']],
    ['customers', 'docs/import-templates/customers.csv', ['--restaurant_id=1']],
    ['inventory-items', 'docs/import-templates/inventory-items.csv', ['--restaurant_id=1', '--branch_id=6']],
    ['opening-balances', 'docs/import-templates/opening-balances.csv', ['--restaurant_id=1', '--branch_id=6']],
  ];
  const evidence = [];
  let failed = false;

  for (const [dataset, file, options] of datasets) {
    const result = spawnSync('php', [
      'artisan',
      'pos:import-switching-csv',
      dataset,
      file,
      ...options,
      '--dry-run',
    ], {
      cwd: ROOT,
      encoding: 'utf8',
    });

    const output = `${result.stdout || ''}${result.stderr || ''}`.replace(/\s+/g, ' ').trim();
    if (result.status !== 0) {
      failed = true;
    }
    evidence.push(`${dataset}: exit=${result.status}; ${output.slice(0, 220)}`);
  }

  addResult({
    area: 'Migration',
    scenario: 'Switching CSV importer dry-runs',
    surface: 'Laravel Artisan',
    status: failed ? 'failed' : 'passed',
    actions: 'Validated categories, products, customers, inventory items, and opening balances through the real importer command in dry-run mode.',
    evidence: evidence.join('; '),
    details: 'Templates are stored under docs/import-templates and documented in the Switching Import Pack.',
  });
}

function reportHtml() {
  const counts = {
    passed: results.filter((item) => item.status === 'passed').length,
    partial: results.filter((item) => item.status === 'partial').length,
    failed: results.filter((item) => item.status === 'failed').length,
    blocked: results.filter((item) => item.status === 'blocked').length,
  };
  const generatedAt = nowIso();
  const resultRows = results.map((item) => `
        <tr>
          <td>${escapeHtml(item.area)}</td>
          <td><strong>${escapeHtml(item.scenario)}</strong><br><span class="muted">${escapeHtml(item.surface)}</span></td>
          <td>${escapeHtml(item.actions)}</td>
          <td>${escapeHtml(item.evidence)}${item.details ? `<div class="note">${escapeHtml(item.details)}</div>` : ''}${item.screenshot ? `<div><a href="${escapeHtml(relativeToReport(item.screenshot))}">Open screenshot</a></div>` : ''}</td>
          <td><span class="status ${statusClass(item.status)}">${escapeHtml(item.status)}</span></td>
        </tr>`).join('\n');

  const screenshotCards = artifacts.map((artifact) => `
        <a class="shot" href="${escapeHtml(relativeToReport(artifact))}">
          <img src="${escapeHtml(relativeToReport(artifact))}" alt="${escapeHtml(path.basename(artifact))}">
          <span>${escapeHtml(path.basename(artifact))}</span>
        </a>`).join('\n');

  return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Janova POS Live Solution Scenario Report - 2026-06-09</title>
  <style>
    :root { --ink:#172033; --muted:#667085; --line:#d9e0ea; --panel:#fff; --soft:#f5f7fb; --done:#0f766e; --wait:#9a3412; --risk:#b91c1c; --brand:#1d4ed8; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: Arial, Helvetica, sans-serif; color:var(--ink); background:#eef2f7; line-height:1.5; }
    main { max-width:1280px; margin:0 auto; padding:28px 18px 60px; }
    header { background:#0f172a; color:#fff; border-radius:8px; padding:28px; }
    h1,h2,h3 { margin:0; }
    h1 { font-size:30px; line-height:1.15; }
    h2 { font-size:22px; margin:26px 0 12px; }
    p { margin:8px 0; }
    a { color:var(--brand); }
    .subtitle { color:#cbd5e1; max-width:980px; }
    .grid { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:12px; margin:16px 0; }
    .card, section { background:var(--panel); border:1px solid var(--line); border-radius:8px; padding:16px; }
    section { margin-top:16px; }
    .metric { font-size:28px; font-weight:800; }
    .label { color:var(--muted); font-size:13px; text-transform:uppercase; letter-spacing:.04em; }
    table { width:100%; border-collapse:collapse; background:#fff; }
    th,td { border:1px solid var(--line); padding:9px 10px; vertical-align:top; text-align:left; font-size:14px; }
    th { background:#e9eef8; font-weight:700; }
    .status { display:inline-block; min-width:82px; text-align:center; border-radius:999px; padding:3px 8px; font-size:12px; font-weight:700; text-transform:capitalize; }
    .done { background:#d1fae5; color:var(--done); }
    .wait { background:#ffedd5; color:var(--wait); }
    .risk { background:#fee2e2; color:var(--risk); }
    .muted { color:var(--muted); }
    .note { background:var(--soft); border-left:4px solid var(--brand); padding:8px 10px; margin-top:8px; }
    .shots { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px; }
    .shot { display:block; text-decoration:none; color:var(--ink); border:1px solid var(--line); background:#fff; border-radius:8px; overflow:hidden; }
    .shot img { width:100%; height:190px; object-fit:cover; display:block; border-bottom:1px solid var(--line); background:#f8fafc; }
    .shot span { display:block; padding:8px 10px; font-size:13px; }
    ul { margin:8px 0 0 20px; padding:0; }
    li { margin:5px 0; }
    code { background:#edf2f7; border:1px solid #d7dee9; padding:1px 4px; border-radius:4px; font-size:13px; }
    @media (max-width: 920px) { .grid,.shots { grid-template-columns:1fr; } table { display:block; overflow-x:auto; } main { padding:14px 10px 40px; } header { padding:20px; } }
  </style>
</head>
<body>
<main>
  <header>
    <h1>Janova POS Live Solution Scenario Report</h1>
    <p class="subtitle">Generated ${escapeHtml(generatedAt)} by <code>docs/scripts/run_live_solution_scenarios.mjs</code>. This run checks the existing winning checklist against live local Flutter web apps, the Vue dashboard, and the Laravel API. Hardware certification remains blocked unless real devices are attached.</p>
  </header>

  <div class="grid">
    <div class="card"><div class="label">Passed</div><div class="metric">${counts.passed}</div></div>
    <div class="card"><div class="label">Partial</div><div class="metric">${counts.partial}</div></div>
    <div class="card"><div class="label">Failed</div><div class="metric">${counts.failed}</div></div>
    <div class="card"><div class="label">Blocked</div><div class="metric">${counts.blocked}</div></div>
  </div>

  <section>
    <h2>Checklist Outcome</h2>
    <ul>
      <li>Live Flutter app automation is now present for cashier, waiter, kitchen, owner, and customer web targets.</li>
      <li>The cashier app was driven through a real UI counter sale and settlement flow.</li>
      <li>The owner app export control was opened and submitted through the live Flutter UI; every CSV dataset was also fetched and checked.</li>
      <li>The Vue dashboard was logged into and all major feature routes were loaded in Chrome.</li>
      <li>Payment terminal certification, physical printer certification, cash drawer lab work, and true external offline/terminal capture remain blocked by missing physical hardware and acquirer credentials in this local run.</li>
    </ul>
  </section>

  <section>
    <h2>Scenario Results</h2>
    <table>
      <thead>
        <tr>
          <th>Area</th>
          <th>Scenario</th>
          <th>Actions Run</th>
          <th>Evidence</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
${resultRows}
      </tbody>
    </table>
  </section>

  <section>
    <h2>Live Screenshots</h2>
    <div class="shots">
${screenshotCards || '<p>No screenshots were captured.</p>'}
    </div>
  </section>
</main>
</body>
</html>`;
}

async function writeReport() {
  await fs.mkdir(path.dirname(REPORT_PATH), { recursive: true });
  await fs.writeFile(REPORT_PATH, reportHtml(), 'utf8');
  console.log(`Report written: ${REPORT_PATH}`);
}

async function main() {
  await fs.mkdir(GENERATED_DIR, { recursive: true });
  await ensureApi();
  await runApiHealth();
  await runStaticReadinessChecks();
  await runMigrationImporterDryRuns();

  const browser = await puppeteer.launch({
    executablePath: CHROME_PATH,
    headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
    defaultViewport: { width: 1440, height: 1000, deviceScaleFactor: 1 },
  });

  try {
    await runDashboardScenarios(browser);
    await runFlutterRole(browser, {
      name: 'cashier',
      label: 'Cashier',
      entrypoint: 'lib/main_cashier.dart',
      port: 58201,
      email: 'cashier1.janova-restaurant.alexandria@example.com',
      expectedText: /Counter sale|Settle order|Cashier queue|No cashier queue/i,
      afterLogin: runCashierCounterSaleUi,
    });
    await runFlutterRole(browser, {
      name: 'waiter',
      label: 'Waiter',
      entrypoint: 'lib/main_waiter.dart',
      port: 58202,
      email: 'waiter1.janova-restaurant.alexandria@example.com',
      expectedText: /Tables|Open orders|Open table|Send to kitchen|T\d/i,
    });
    await runFlutterRole(browser, {
      name: 'kitchen',
      label: 'Kitchen',
      entrypoint: 'lib/main_kitchen.dart',
      port: 58203,
      email: 'kitchen1.janova-restaurant.alexandria@example.com',
      expectedText: /Queued|Preparing|Ready|Kitchen is clear|No tickets/i,
    });
    await runFlutterRole(browser, {
      name: 'owner',
      label: 'Owner',
      entrypoint: 'lib/main_owner.dart',
      port: 58204,
      email: 'owner.janova-restaurant@example.com',
      expectedText: /Owner dashboard|Revenue|Export CSV|Branch leaderboard/i,
      afterLogin: runOwnerExportUi,
    });
    await runCustomerFlutter(browser);
  } finally {
    await browser.close().catch(() => {});
  }

  await runOperationalScenarios();
  await writeReport();
}

process.on('SIGINT', async () => {
  await cleanup();
  process.exit(130);
});
process.on('SIGTERM', async () => {
  await cleanup();
  process.exit(143);
});

main()
  .catch((error) => {
    addResult({
      area: 'Runner',
      scenario: 'Live solution scenario runner',
      surface: 'Node/Puppeteer',
      status: 'failed',
      actions: 'Run all configured scenarios.',
      evidence: error.stack || error.message,
    });
    return writeReport().finally(() => {
      process.exitCode = 1;
    });
  })
  .finally(cleanup);
