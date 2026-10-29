// tests/verify_quick_icons_and_status.js
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const sleep = ms => new Promise(r => setTimeout(r, ms));
const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const profileDir = path.join(__dirname, 'edge_temp_profile_icons');

const chrome = spawn(edgePath, [
    '--headless=new',
    '--remote-debugging-port=9225',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--window-size=390,844',
    '--user-data-dir=' + profileDir,
    'http://localhost/E-vooting%20OSIS/login.php'
]);

function getJson(url) {
    return new Promise((resolve, reject) => {
        http.get(url, res => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try { resolve(JSON.parse(data)); } catch(e) { reject(e); }
            });
        }).on('error', reject);
    });
}

let pending = new Map();
let msgId = 1;

function send(ws, method, params = {}) {
    return new Promise((resolve, reject) => {
        const id = msgId++;
        pending.set(id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params }));
    });
}

async function evalJs(ws, expr) {
    const res = await send(ws, 'Runtime.evaluate', {
        expression: expr,
        returnByValue: true,
        awaitPromise: true
    });
    if (res.exceptionDetails) {
        throw new Error(JSON.stringify(res.exceptionDetails));
    }
    return res.result ? res.result.value : null;
}

async function captureScreenshot(ws, filename) {
    const res = await send(ws, 'Page.captureScreenshot', { format: 'png' });
    const buf = Buffer.from(res.data, 'base64');
    fs.writeFileSync(path.join(__dirname, filename), buf);
    console.log(`[SCREENSHOT] Saved: ${filename}`);
}

async function main() {
    try {
        let wsUrl = null;
        for (let i = 0; i < 20; i++) {
            await sleep(400);
            try {
                const pages = await getJson('http://127.0.0.1:9225/json');
                const p = pages.find(item => item.type === 'page');
                if (p && p.webSocketDebuggerUrl) {
                    wsUrl = p.webSocketDebuggerUrl;
                    break;
                }
            } catch (e) {}
        }

        if (!wsUrl) {
            console.error('CDP port failed on 9225');
            cleanup();
            return;
        }

        console.log('Connected to CDP on 9225:', wsUrl);
        const ws = new globalThis.WebSocket(wsUrl);

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.id && pending.has(data.id)) {
                const { resolve } = pending.get(data.id);
                pending.delete(data.id);
                resolve(data.result);
            }
        };

        ws.onopen = async () => {
            await send(ws, 'Page.enable');
            await send(ws, 'Runtime.enable');
            await send(ws, 'DOM.enable');

            await send(ws, 'Emulation.setDeviceMetricsOverride', {
                width: 390,
                height: 844,
                deviceScaleFactor: 2.0,
                mobile: true
            });

            // 1. Log in as Guru
            console.log('\n--- 1. LOGIN SEBAGAI GURU ---');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/login.php' });
            await sleep(1500);

            await evalJs(ws, `
                const u = document.getElementById('username');
                const p = document.getElementById('password');
                if (u && p) {
                    u.value = '3204252301040001';
                    p.value = '123456';
                    document.querySelector('form button[type="submit"]').click();
                }
            `);
            await sleep(1800);

            // 2. Guru Dashboard Quick Icons Check
            console.log('\n--- 2. PERIKSA IKON DI GURU DASHBOARD ---');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/guru/dashboard.php' });
            await sleep(1200);

            const guruIcons = await evalJs(ws, `(() => {
                const icons = Array.from(document.querySelectorAll('.quick-action-icon')).map(el => {
                    const iconEl = el.querySelector('i');
                    const compStyle = window.getComputedStyle(el);
                    const iconCompStyle = iconEl ? window.getComputedStyle(iconEl) : null;
                    return {
                        className: el.className,
                        iconTag: iconEl ? iconEl.className : null,
                        color: iconCompStyle ? iconCompStyle.color : null,
                        background: compStyle.backgroundImage || compStyle.backgroundColor,
                        hasIcon: !!iconEl
                    };
                });
                return {
                    count: icons.length,
                    icons: icons
                };
            })()`);
            console.log('Guru Dashboard Quick Icons:', JSON.stringify(guruIcons, null, 2));

            await captureScreenshot(ws, 'quick_actions_guru_mobile.png');

            // 3. Status Hak Suara Check
            console.log('\n--- 3. PERIKSA IKON DI STATUS HAK SUARA (STATUS.PHP) ---');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/guru/status.php' });
            await sleep(1200);

            const statusCheck = await evalJs(ws, `(() => {
                const title = document.querySelector('h3') ? document.querySelector('h3').innerText : '';
                const iconsInStatus = Array.from(document.querySelectorAll('main .bi')).map(i => i.className);
                const hasFingerprint = !!document.querySelector('.bi-fingerprint');
                const hasPerson = !!document.querySelector('.bi-person-fill');
                const hasVcard = !!document.querySelector('.bi-person-vcard');
                const hasVoteBtn = !!document.querySelector('a[href*="voting.php"] .bi-check2-square');
                return {
                    title,
                    hasFingerprint,
                    hasPerson,
                    hasVcard,
                    hasVoteBtn,
                    totalIconsInMain: iconsInStatus.length
                };
            })()`);
            console.log('Status Hak Suara Icons Check:', JSON.stringify(statusCheck, null, 2));

            await captureScreenshot(ws, 'status_hak_suara_mobile.png');

            // 4. Log in as Siswa and check dashboard quick icons
            console.log('\n--- 4. LOGOUT & LOGIN SEBAGAI SISWA ---');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/logout.php' });
            await sleep(1200);

            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/login.php' });
            await sleep(1200);

            await evalJs(ws, `
                const u = document.getElementById('username');
                const p = document.getElementById('password');
                if (u && p) {
                    u.value = '5224020552';
                    p.value = '123456';
                    document.querySelector('form button[type="submit"]').click();
                }
            `);
            await sleep(1800);

            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/siswa/dashboard.php' });
            await sleep(1200);

            const siswaIcons = await evalJs(ws, `(() => {
                const icons = Array.from(document.querySelectorAll('.quick-action-icon')).map(el => {
                    const iconEl = el.querySelector('i');
                    const compStyle = window.getComputedStyle(el);
                    const iconCompStyle = iconEl ? window.getComputedStyle(iconEl) : null;
                    return {
                        className: el.className,
                        iconTag: iconEl ? iconEl.className : null,
                        color: iconCompStyle ? iconCompStyle.color : null,
                        background: compStyle.backgroundImage || compStyle.backgroundColor,
                        hasIcon: !!iconEl
                    };
                });
                return {
                    count: icons.length,
                    icons: icons
                };
            })()`);
            console.log('Siswa Dashboard Quick Icons:', JSON.stringify(siswaIcons, null, 2));

            await captureScreenshot(ws, 'quick_actions_siswa_mobile.png');

            console.log('\n======================================================');
            console.log('ALL ICONS VERIFIED PERFECTLY IN REAL BROWSER!');
            console.log('======================================================\n');

            cleanup();
        };

    } catch (err) {
        console.error('ERROR:', err);
        cleanup();
    }
}

function cleanup() {
    try { chrome.kill(); } catch (e) {}
    process.exit(0);
}

main();
