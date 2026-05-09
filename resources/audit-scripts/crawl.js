#!/usr/bin/env node
'use strict';

/**
 * KynexEdu audit crawler — Playwright headless Chromium
 *
 * IPC: one JSON object per line (NDJSON) written to stdout via fs.writeSync.
 * fs.writeSync(fd, ...) makes a synchronous write(2) syscall, bypassing Node's
 * Writable stream buffer. Without this, PHP's getIncrementalOutput() poll would
 * see buffered chunks rather than complete NDJSON lines, causing json_decode null.
 *
 * Event shapes (all newline-terminated JSON):
 *   { event: 'login_complete', url }
 *   { event: 'nav_start',     url }
 *   { event: 'nav_complete',  url, http_status, duration_ms }
 *   { event: 'finding',       severity, finding_type, url, title,
 *                             details, screenshot, http_status }
 *   { event: 'progress',      pages_crawled, findings_so_far }
 *   { event: 'timeout',       at_url, at_step }
 *   { event: 'error',         message, stack }
 *   { event: 'done',          pages_crawled, findings_total }
 *
 * Dependencies: 'playwright' only. Built-ins: 'fs', 'path'.
 * Tested with Playwright v1.59.1 / Node v20.
 *
 * Login selector verified against live DOM at https://aqmdigital.com/login:
 *   name="email", type="email", name="password", type="password"
 *   No wire:model bindings in static HTML — standard field names apply.
 */

const { chromium } = require('playwright');
const fs   = require('fs');
const path = require('path');

// ─── stdout IPC ───────────────────────────────────────────────────────────────
// fs.writeSync to fd 1 bypasses Node stream buffering entirely.
// PHP polls getIncrementalOutput() every 100ms — partial/buffered lines
// would parse as null from json_decode and be silently dropped.

function emit(obj) {
    const line = JSON.stringify(obj) + '\n';
    fs.writeSync(process.stdout.fd, line);
}

function fatal(msg, err = null) {
    emit({ event: 'error', message: String(msg), stack: err?.stack ?? null });
    process.exit(1);
}

// ─── CLI arg parsing ──────────────────────────────────────────────────────────

function arg(name, required = false) {
    const prefix = `--${name}=`;
    const found  = process.argv.slice(2).find(a => a.startsWith(prefix));
    if (!found) {
        if (required) fatal(`Missing required argument: --${name}`);
        return null;
    }
    return found.slice(prefix.length);
}

const CREDS_FILE      = arg('creds-file',      true);
const RUN_ID          = arg('run-id',           true);   // passed through for tracing
const TENANT          = arg('tenant',           true);   // passed through for tracing
const ROLE            = arg('role',             true);
const PANEL           = arg('panel',            true);   // 'admin' | 'parent' | 'saas'
const LOGIN_URL       = arg('login-url',        true);
const PANEL_URL       = arg('panel-url',        true);
const SCREENSHOTS_DIR = arg('screenshots-dir',  true);
const SINGLE_URL      = arg('single-url',       false);  // null unless --url mode
const PAGE_TIMEOUT    = parseInt(arg('page-timeout') ?? '30000',    10);
const ROLE_TIMEOUT_MS = parseInt(arg('role-timeout')  ?? '1200000', 10);
const MAX_URLS        = parseInt(arg('max-urls')       ?? '0',      10);
const HEADLESS        = process.argv.includes('--headless');

// ─── Per-role deadline ────────────────────────────────────────────────────────
// Node enforces this independently of PHP's Symfony Process.setTimeout() so it
// can emit { event: 'timeout' } before PHP force-kills the process with SIGKILL.

const ROLE_DEADLINE = Date.now() + ROLE_TIMEOUT_MS;

function deadlineExceeded() {
    return Date.now() >= ROLE_DEADLINE;
}

// ─── Mutable state ────────────────────────────────────────────────────────────

let pagesCrawled  = 0;
let findingsTotal = 0;
let screenshotSeq = 0;

// ─── Screenshot helpers ───────────────────────────────────────────────────────

function makeScreenshotPath(label) {
    screenshotSeq++;
    const safe = label.replace(/[^a-z0-9_-]/gi, '_').slice(0, 60);
    return path.join(SCREENSHOTS_DIR, `${String(screenshotSeq).padStart(4, '0')}-${safe}.png`);
}

async function takeScreenshot(page, label) {
    try {
        const p = makeScreenshotPath(label);
        await page.screenshot({ path: p, fullPage: false });
        return p;
    } catch {
        return null; // screenshot failure must never abort the crawl
    }
}

// ─── Finding emission ─────────────────────────────────────────────────────────

function emitFinding(obj) {
    findingsTotal++;
    emit({ event: 'finding', ...obj });
    if (findingsTotal % 10 === 0) {
        emit({ event: 'progress', pages_crawled: pagesCrawled, findings_so_far: findingsTotal });
    }
}

// ─── Severity mapping (design doc §7) ─────────────────────────────────────────
// Returns null for 2xx/3xx — those are not findings.

function httpSeverity(status) {
    if (status >= 500)  return 'critical';
    if (status === 403) return 'high';
    if (status === 404) return 'high';
    if (status >= 400)  return 'medium';
    return null;
}

// ─── Per-page checks ──────────────────────────────────────────────────────────
// Nine checks run on every page after navigation completes.
// jsErrors        = console 'error' messages       (page.on('console'))
// pageErrors      = uncaught exceptions/rejections  (page.on('pageerror'))
// networkFailures = failed non-image sub-resources  (page.on('requestfailed'))

async function runPageChecks(
    page, url, httpStatus, jsErrors, pageErrors, networkFailures, durationMs
) {
    const findings = [];

    // 1. HTTP status ──────────────────────────────────────────────────────────
    const httpSev = httpSeverity(httpStatus);
    if (httpSev) {
        const shot = await takeScreenshot(page, `http-${httpStatus}`);
        findings.push({
            severity:     httpSev,
            finding_type: httpStatus >= 500 ? '5xx' : '4xx',
            url,
            title:        `HTTP ${httpStatus} at ${url}`,
            details:      { http_status: httpStatus, duration_ms: durationMs },
            screenshot:   shot,
            http_status:  httpStatus,
        });
    }

    // 2. Blank page ───────────────────────────────────────────────────────────
    // DOM-based: requires ALL THREE conditions to fire to avoid false positives
    // on Filament's empty-state pages (which render .fi-ta-empty-state but have
    // minimal body text — those are valid empty resource lists, not blank pages).
    const bodyState = await page.evaluate(() => ({
        hasMain:       !!document.querySelector('main, [role="main"], .fi-main'),
        hasEmptyState: !!document.querySelector('.fi-ta-empty-state, [data-empty-state]'),
        bodyText:      (document.body?.innerText ?? '').trim(),
    })).catch(() => ({ hasMain: false, hasEmptyState: false, bodyText: '' }));

    if (
        !bodyState.hasMain       &&
        !bodyState.hasEmptyState &&
        bodyState.bodyText.length < 100 &&
        httpStatus < 400
    ) {
        const shot = await takeScreenshot(page, 'blank-page');
        findings.push({
            severity:     'high',
            finding_type: 'blank_page',
            url,
            title:        `Blank page at ${url}`,
            details:      { body_text_length: bodyState.bodyText.length, duration_ms: durationMs },
            screenshot:   shot,
            http_status:  httpStatus,
        });
    }

    // 3. Filament error toast ─────────────────────────────────────────────────
    // Filament v3 marks danger notifications with x-filament-notification-type="danger".
    const toastText = await page.evaluate(() => {
        const nodes = [...document.querySelectorAll('[x-filament-notification-type="danger"]')];
        return nodes.map(n => (n.innerText ?? '').trim()).filter(Boolean).join(' | ');
    }).catch(() => '');

    if (toastText) {
        const shot = await takeScreenshot(page, 'filament-toast');
        findings.push({
            severity:     'high',
            finding_type: 'filament_error_toast',
            url,
            title:        `Filament error toast at ${url}`,
            details:      { toast_text: toastText.slice(0, 500) },
            screenshot:   shot,
            http_status:  httpStatus,
        });
    }

    // 4. Panel chrome (sidebar) missing ───────────────────────────────────────
    // HIGH: a missing sidebar on a 200 page means the panel layout is broken.
    // Only checked on 200 — error pages legitimately render without a sidebar.
    // Two selectors tried: nav.fi-sidebar (Filament v3 default) and .fi-sidebar
    // (class-only for layout variants that omit the <nav> wrapper).
    // evaluate() errors default to true (assume chrome present) to avoid false positives.
    if (httpStatus === 200) {
        const hasNav = await page.evaluate(() =>
            !!(document.querySelector('nav.fi-sidebar') ||
               document.querySelector('.fi-sidebar'))
        ).catch(() => true);

        if (!hasNav) {
            const shot = await takeScreenshot(page, 'no-chrome');
            findings.push({
                severity:     'high',
                finding_type: 'panel_chrome_missing',
                url,
                title:        `Panel sidebar missing at ${url}`,
                details:      { duration_ms: durationMs },
                screenshot:   shot,
                http_status:  httpStatus,
            });
        }
    }

    // 5. JS console errors ────────────────────────────────────────────────────
    // Collected via page.on('console') — attached before page.goto in navigateAndCheck().
    for (const msg of jsErrors) {
        findings.push({
            severity:     'medium',
            finding_type: 'js_error',
            url,
            title:        `JS error: ${msg.slice(0, 120)}`,
            details:      { message: msg },
            screenshot:   null,
            http_status:  httpStatus,
        });
    }

    // 6. Uncaught page errors (pageerror) ─────────────────────────────────────
    // page.on('pageerror') fires for uncaught exceptions and unhandled promise
    // rejections — distinct from console.error(), covered by check 5.
    for (const msg of pageErrors) {
        findings.push({
            severity:     'medium',
            finding_type: 'js_error',
            url,
            title:        `Uncaught page error: ${msg.slice(0, 120)}`,
            details:      { message: msg, type: 'pageerror' },
            screenshot:   null,
            http_status:  httpStatus,
        });
    }

    // 7. Network failures ─────────────────────────────────────────────────────
    // Non-image sub-resource failures. Image failures are intentionally excluded
    // here and caught by the DOM naturalWidth check (9) to avoid double-reporting.
    for (const req of networkFailures) {
        findings.push({
            severity:     'medium',
            finding_type: 'network_failure',
            url,
            title:        `Network failure: ${req.url.slice(0, 120)}`,
            details:      { failed_url: req.url, failure: req.failure },
            screenshot:   null,
            http_status:  httpStatus,
        });
    }

    // 8. Slow page ────────────────────────────────────────────────────────────
    if (durationMs > 8000 && httpStatus < 400) {
        findings.push({
            severity:     'low',
            finding_type: 'slow_page',
            url,
            title:        `Slow page (${Math.round(durationMs / 1000)}s) at ${url}`,
            details:      { duration_ms: durationMs },
            screenshot:   null,
            http_status:  httpStatus,
        });
    }

    // 9. Broken / malformed images ────────────────────────────────────────────
    // rawSrc: img.getAttribute('src') — literal attribute value (null coerced to '')
    // absSrc: img.src (browser property) — browser-resolved absolute URL
    // naturalWidth === 0 when complete means the browser failed to load the image.
    //
    // Mutually exclusive sub-cases via else-if (prevents double-reporting):
    //   a. empty rawSrc          → empty_image_src    (LOW)
    //   b. non-http absSrc       → malformed_image_url (MEDIUM)
    //   c. complete && natW === 0 → broken_image        (MEDIUM)
    const imgResults = await page.evaluate(() =>
        [...document.querySelectorAll('img')].map(img => ({
            rawSrc:   img.getAttribute('src') ?? '',
            absSrc:   img.src,
            natW:     img.naturalWidth,
            complete: img.complete,
        }))
    ).catch(() => []);

    for (const img of imgResults) {
        if (!img.rawSrc) {
            findings.push({
                severity:     'low',
                finding_type: 'empty_image_src',
                url,
                title:        'Image with empty or missing src attribute',
                details:      {},
                screenshot:   null,
                http_status:  httpStatus,
            });
        } else if (!img.absSrc.startsWith('http://') && !img.absSrc.startsWith('https://')) {
            findings.push({
                severity:     'medium',
                finding_type: 'malformed_image_url',
                url,
                title:        `Malformed image URL: ${img.rawSrc.slice(0, 80)}`,
                details:      { img_src: img.rawSrc },
                screenshot:   null,
                http_status:  httpStatus,
            });
        } else if (img.complete && img.natW === 0) {
            findings.push({
                severity:     'medium',
                finding_type: 'broken_image',
                url,
                title:        `Broken image: ${img.absSrc.slice(0, 80)}`,
                details:      { img_src: img.absSrc },
                screenshot:   null,
                http_status:  httpStatus,
            });
        }
    }

    return findings;
}

// ─── Navigate + check ─────────────────────────────────────────────────────────
// Core crawl primitive. Attaches per-page event listeners before navigation,
// detaches them in finally (even on throw), then runs all nine page checks.
// Returns true to continue crawling, false to stop (deadline or hard timeout).

async function navigateAndCheck(page, url) {
    if (deadlineExceeded()) {
        emit({ event: 'timeout', at_url: url, at_step: 'deadline_before_nav' });
        return false;
    }

    emit({ event: 'nav_start', url });

    const jsErrors        = [];
    const pageErrors      = [];
    const networkFailures = [];

    // Listeners attached before goto so events fired during navigation are captured.
    const onConsole = msg => {
        if (msg.type() === 'error') jsErrors.push(msg.text());
    };
    const onPageError = err => {
        pageErrors.push(err.message ?? String(err));
    };
    const onRequestFailed = req => {
        const reqUrl = req.url();
        // Skip image URLs — handled by DOM naturalWidth check (9) to avoid
        // double-reporting the same broken asset as both network_failure and broken_image.
        if (/\.(png|jpe?g|gif|webp|svg|ico|avif)(\?.*)?$/i.test(reqUrl)) return;
        networkFailures.push({ url: reqUrl, failure: req.failure()?.errorText ?? 'unknown' });
    };

    page.on('console',       onConsole);
    page.on('pageerror',     onPageError);
    page.on('requestfailed', onRequestFailed);

    let httpStatus = 0;
    let durationMs = 0;
    let timedOut   = false;

    try {
        const t0       = Date.now();
        const response = await page.goto(url, {
            waitUntil: 'networkidle', // waits for ≤2 in-flight requests for 500ms; covers Livewire/Alpine init
            timeout:   PAGE_TIMEOUT,
        }).catch(err => {
            if (err.name === 'TimeoutError') { timedOut = true; return null; }
            throw err; // re-throw unexpected navigation errors
        });

        durationMs = Date.now() - t0;
        httpStatus = response?.status() ?? 0;

    } finally {
        // Always detach — prevents listener accumulation across pages
        page.off('console',       onConsole);
        page.off('pageerror',     onPageError);
        page.off('requestfailed', onRequestFailed);
    }

    if (timedOut) {
        emit({ event: 'timeout', at_url: url, at_step: 'networkidle' });
        // Soft timeout: emit breadcrumb but keep crawling unless global deadline blown
        return !deadlineExceeded();
    }

    const findings = await runPageChecks(
        page, url, httpStatus, jsErrors, pageErrors, networkFailures, durationMs
    );
    for (const f of findings) emitFinding(f);

    pagesCrawled++;
    emit({ event: 'nav_complete', url, http_status: httpStatus, duration_ms: durationMs });

    if (pagesCrawled % 20 === 0) {
        emit({ event: 'progress', pages_crawled: pagesCrawled, findings_so_far: findingsTotal });
    }

    return true;
}

// ─── Sidebar nav link harvester ───────────────────────────────────────────────
// Collects unique absolute hrefs from Filament's sidebar navigation.
// Three selectors in priority order:
//   1. nav.fi-sidebar a[href]             — Filament v3 standard (element + class)
//   2. .fi-sidebar a[href]               — class-only, covers layout variants
//   3. nav[aria-label="sidebar"] a[href] — aria fallback for customised panels
// Links are filtered to the same panel origin+prefix to avoid crawling the
// public CMS, external CDNs, or other tenants' subdomains.
// Fragment anchors (#section) are stripped — they don't trigger a new page load.

async function harvestNavLinks(page, panelUrl) {
    const base = panelUrl.replace(/\/$/, '');

    return page.evaluate((base) => {
        const links = new Set();
        const selectors = [
            'nav.fi-sidebar a[href]',
            '.fi-sidebar a[href]',
            'nav[aria-label="sidebar"] a[href]',
        ];
        for (const sel of selectors) {
            document.querySelectorAll(sel).forEach(a => {
                const href = a.href; // browser-resolved absolute URL
                if (href && (href.startsWith(base + '/') || href === base)) {
                    links.add(href.split('#')[0]);
                }
            });
        }
        return [...links];
    }, base).catch(() => []);
}

// ─── Form-submit pass ─────────────────────────────────────────────────────────
// Navigates to a resource's create form, submits it empty, and verifies
// Filament renders validation errors rather than a 500 or blank page.
//
// Heuristic for resource list pages:
//   LIST_PAGE_RE = /\/(admin|parent)\/[a-z][a-z0-9-]*$/
//
//   Matches:   /admin/students, /admin/fee-structures, /parent/children
//   Rejects:   /admin/students/1/edit  (has ID segment)
//              /admin/students/create  (already a create form)
//              /admin                  (no resource slug)
//
// False-positive safety: if no "New/Create" button exists or no <form> is found,
// the function returns early with no finding. The pattern gates entry only.

const LIST_PAGE_RE = /\/(admin|parent)\/[a-z][a-z0-9-]*$/;

async function runFormSubmitPass(page, listUrl) {
    if (!LIST_PAGE_RE.test(listUrl)) return;

    const createHref = await page.evaluate(() => {
        const anchors = [...document.querySelectorAll('a[href]')].filter(a =>
            /\b(new|create)\b/i.test((a.textContent ?? '').trim()) && a.href
        );
        return anchors[0]?.href ?? null;
    }).catch(() => null);

    if (!createHref) return; // list is view-only for this role — no finding

    const ok = await navigateAndCheck(page, createHref);
    if (!ok) return;

    const hasForm = await page.evaluate(() =>
        !!document.querySelector('form')
    ).catch(() => false);

    if (!hasForm) return; // no form found — skip gracefully

    const beforeUrl   = page.url();
    let   afterStatus = 0;

    const onResponse = res => { afterStatus = res.status(); };
    page.on('response', onResponse);

    try {
        const submitted = await page.evaluate(() => {
            // Filament v5 renders the sidebar Logout as a form[POST] > button[type=submit]
            // which appears before the page's own form buttons in DOM order.
            // Filter it out by text so we only click the page's primary action button.
            const btns = [...document.querySelectorAll('button[type="submit"]')];
            const btn  = btns.find(b => !/log.?out|sign.?out/i.test((b.textContent ?? '').trim()));
            if (btn) { btn.click(); return true; }
            return false;
        }).catch(() => false);

        if (!submitted) return;

        // Wait for Livewire/Alpine validation re-render.
        // networkidle catches the XHR Livewire fires for validation (~100-300ms).
        await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});

    } finally {
        page.off('response', onResponse);
    }

    const afterUrl  = page.url();
    const afterBody = await page.evaluate(() =>
        (document.body?.innerText ?? '').trim()
    ).catch(() => '');

    // Filament v3 validation error selectors
    const hasValidationErrors = await page.evaluate(() =>
        !!(document.querySelector('[data-validation-error]')    ||
           document.querySelector('.fi-fo-field-wrp-error')     ||
           document.querySelector('.fi-fo-field-error-message') ||
           document.querySelector('[data-fs-error]'))
    ).catch(() => false);

    // ✓ Stayed on create page with validation errors visible
    if (afterUrl === beforeUrl && hasValidationErrors) return;

    // ✗ Blank page after submit
    if (afterBody.length < 30) {
        const shot = await takeScreenshot(page, 'form-blank');
        emitFinding({
            severity:     'high',
            finding_type: 'blank_page',
            url:          createHref,
            title:        `Blank page after empty form submit at ${createHref}`,
            details:      { before_url: beforeUrl, after_url: afterUrl, http_status: afterStatus },
            screenshot:   shot,
            http_status:  afterStatus,
        });
        return;
    }

    // ✗ HTTP 500 after submit
    if (afterStatus >= 500) {
        const shot = await takeScreenshot(page, 'form-500');
        emitFinding({
            severity:     'critical',
            finding_type: 'form_500_on_validation',
            url:          createHref,
            title:        `HTTP 500 on empty form submit at ${createHref}`,
            details:      { before_url: beforeUrl, after_url: afterUrl, http_status: afterStatus },
            screenshot:   shot,
            http_status:  afterStatus,
        });
        return;
    }

    // ✗ Silent redirect — no 500, no blank, but navigated away without validation errors
    if (!hasValidationErrors && afterUrl !== beforeUrl) {
        emitFinding({
            severity:     'medium',
            finding_type: 'form_500_on_validation',
            url:          createHref,
            title:        `Silent redirect on empty form submit at ${createHref}`,
            details:      { before_url: beforeUrl, after_url: afterUrl, http_status: afterStatus },
            screenshot:   null,
            http_status:  afterStatus,
        });
    }
}

// ─── STUDENT negative test ────────────────────────────────────────────────────
// STUDENT is not a school_admin panel role. After navigating to /admin, Filament
// should block access. Two valid blocking outcomes:
//
//   hasNav  + hasAccessDenied  → ok (Filament AccessDenied component, chrome intact)
//   !hasNav + hasAccessDenied  → ok (canAccessPanel()=false → plain 403, more secure)
//   hasNav  + !hasAccessDenied → security_violation (student sees real panel)
//   !hasNav + !hasAccessDenied → security_violation (unknown redirect, no denial shown)

async function runStudentNegativeTest(page) {
    const currentUrl = page.url();

    // A. Sidebar (panel chrome) check
    const hasNav = await page.evaluate(() =>
        !!(document.querySelector('nav.fi-sidebar') ||
           document.querySelector('.fi-sidebar'))
    ).catch(() => false);

    // B. AccessDenied content check
    const bodyText = await page.evaluate(() =>
        (document.body?.innerText ?? '')
    ).catch(() => '');

    const hasAccessDenied = /forbidden|403|you do not have access|access.?denied/i.test(bodyText);

    // ✓ Correct outcomes: access denied regardless of whether chrome is present.
    // canAccessPanel()=false renders a plain 403 without chrome — equally secure.
    if (hasAccessDenied) {
        emitFinding({
            severity:     'ok',
            finding_type: 'access_denied_ok',
            url:          currentUrl,
            title:        hasNav
                ? 'STUDENT correctly denied — panel chrome intact'
                : 'STUDENT correctly denied — plain 403 (no chrome, more secure)',
            details:      { has_nav: hasNav },
            screenshot:   null,
            http_status:  403,
        });
        return;
    }

    // ✗ No access-denied text (student may have landed on real panel content)
    const shot = await takeScreenshot(page, 'student-no-denied');
    emitFinding({
        severity:     'medium',
        finding_type: 'security_violation',
        url:          currentUrl,
        title:        'STUDENT landed on panel without AccessDenied message',
        details:      { body_preview: bodyText.slice(0, 300), has_nav: hasNav },
        screenshot:   shot,
        http_status:  200,
    });
}

// ─── Login ────────────────────────────────────────────────────────────────────
// Selectors verified against live DOM at https://aqmdigital.com/login:
//   input[type="email"] / name="email"  — confirmed present
//   input[type="password"]              — confirmed present
//   No wire:model bindings in static HTML — Livewire is not used on login page.

async function doLogin(page, email, password) {
    await page.goto(LOGIN_URL, {
        waitUntil: 'networkidle',
        timeout:   PAGE_TIMEOUT,
    });

    const emailField = await page.$('input[type="email"], input[name="email"]');
    const passField  = await page.$('input[type="password"]');

    if (!emailField || !passField) {
        fatal(`Login form fields not found at ${LOGIN_URL}. DOM may have changed.`);
    }

    await emailField.fill(email);
    await passField.fill(password);

    // Promise.all ensures waitForNavigation is registered BEFORE press fires —
    // avoids a race where navigation completes before the listener is attached.
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: PAGE_TIMEOUT }),
        passField.press('Enter'),
    ]);

    const afterUrl = page.url();

    // Detect login failure: still on the login/auth URL after navigation
    if (afterUrl.includes('/login') || afterUrl.includes('/auth')) {
        const bodyText = await page.evaluate(() =>
            (document.body?.innerText ?? '').slice(0, 200)
        ).catch(() => '');
        fatal(
            `Login failed for ${email} at ${LOGIN_URL}. ` +
            `Post-login URL: ${afterUrl}. Body: ${bodyText}`
        );
    }

    emit({ event: 'login_complete', url: afterUrl });
}

// ─── Main ─────────────────────────────────────────────────────────────────────

(async () => {
    // 1. Read credentials from the 0600 temp file ─────────────────────────────
    // PHP writes this file atomically (tmp+rename) then deletes it on login_complete.
    // We parse immediately and discard the object. V8 strings are immutable —
    // we cannot zero-fill memory; dropping the reference makes it GC-eligible.
    // Physical file deletion by PHP is the primary security control.
    let email, password;
    try {
        const raw   = fs.readFileSync(CREDS_FILE, 'utf8');
        const creds = JSON.parse(raw);
        email          = creds.email;
        password       = creds.password;
        creds.password = null; // drop parsed object's reference immediately
    } catch (err) {
        fatal(`Cannot read or parse creds file: ${err.message}`, err);
    }

    // 2. Launch Chromium ───────────────────────────────────────────────────────
    const browser = await chromium.launch({ headless: HEADLESS });
    const context = await browser.newContext({
        ignoreHTTPSErrors: false,
        viewport: { width: 1440, height: 900 },
    });
    const page = await context.newPage();

    try {
        // 3. Login ─────────────────────────────────────────────────────────────
        await doLogin(page, email, password);
        password = null; // drop local ref; PHP deletes the creds file on login_complete

        // 4. Dispatch by mode ──────────────────────────────────────────────────

        // STUDENT: negative test only — no breadth crawl
        if (ROLE === 'STUDENT') {
            await runStudentNegativeTest(page);
            emit({ event: 'done', pages_crawled: pagesCrawled, findings_total: findingsTotal });
            return;
        }

        // Single-URL mode (--url flag from PHP)
        if (SINGLE_URL) {
            await navigateAndCheck(page, SINGLE_URL);
            emit({ event: 'done', pages_crawled: pagesCrawled, findings_total: findingsTotal });
            return;
        }

        // Full breadth crawl ───────────────────────────────────────────────────

        // Step A: check panel root and harvest sidebar links from its nav
        const rootOk = await navigateAndCheck(page, PANEL_URL);
        if (!rootOk) {
            emit({ event: 'done', pages_crawled: pagesCrawled, findings_total: findingsTotal });
            return;
        }

        const navLinks = await harvestNavLinks(page, PANEL_URL);
        const visited  = new Set([PANEL_URL]);

        // Step B: visit each sidebar link
        for (const url of navLinks) {
            if (deadlineExceeded()) {
                emit({ event: 'timeout', at_url: url, at_step: 'deadline_between_pages' });
                break;
            }

            if (visited.has(url)) continue;
            visited.add(url);

            if (MAX_URLS > 0 && pagesCrawled >= MAX_URLS) break;

            const ok = await navigateAndCheck(page, url);
            if (!ok) break;

            // Step C: form-submit pass (gated by LIST_PAGE_RE; exits early if no form)
            if (!deadlineExceeded()) {
                await runFormSubmitPass(page, url);
            }
        }

        emit({ event: 'done', pages_crawled: pagesCrawled, findings_total: findingsTotal });

    } catch (err) {
        fatal(`Unhandled error: ${err.message}`, err);
    } finally {
        await browser.close().catch(() => {});
    }
})();
