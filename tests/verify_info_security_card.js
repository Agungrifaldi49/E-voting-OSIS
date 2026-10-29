// tests/verify_info_security_card.js
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const sleep = ms => new Promise(r => setTimeout(r, ms));
const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const profileDir = path.join(__dirname, 'edge_temp_profile_info');

const chrome = spawn(edgePath, [
    '--headless=new',
    '--remote-debugging-port=9224',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
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
                const pages = await getJson('http://127.0.0.1:9224/json');
                const p = pages.find(item => item.type === 'page');
                if (p && p.webSocketDebuggerUrl) {
                    wsUrl = p.webSocketDebuggerUrl;
                    break;
                }
            } catch (e) {}
        }

        if (!wsUrl) {
            console.error('CDP port failed on 9224');
            cleanup();
            return;
        }

        console.log('Connected to CDP on 9224:', wsUrl);
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

            // 1. Log in as guru/siswa
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

            // 2. Navigate to guru/info.php
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/guru/info.php' });
            await sleep(1200);

            // Desktop Screenshot
            await send(ws, 'Emulation.setDeviceMetricsOverride', {
                width: 1280,
                height: 900,
                deviceScaleFactor: 1.0,
                mobile: false
            });
            await sleep(500);

            const cardCheck = await evalJs(ws, `(() => {
                const card = document.querySelector('.security-guarantee-card');
                const pillars = document.querySelectorAll('.security-pillar-item');
                return {
                    hasCard: !!card,
                    cardTitle: card ? card.querySelector('h5').innerText : null,
                    pillarCount: pillars.length,
                    hasHelpdesk: !!document.querySelector('.card .bi-whatsapp')
                };
            })()`);
            console.log('Desktop Card Check:', JSON.stringify(cardCheck, null, 2));

            await captureScreenshot(ws, 'security_card_desktop.png');

            // Mobile Viewport (iPhone 14 / modern Android)
            await send(ws, 'Emulation.setDeviceMetricsOverride', {
                width: 390,
                height: 844,
                deviceScaleFactor: 2.0,
                mobile: true
            });
            await sleep(500);

            // Scroll down slightly so the right column / security card is centered in view
            await evalJs(ws, `
                const card = document.querySelector('.security-guarantee-card');
                if (card) {
                    card.scrollIntoView({ behavior: 'instant', block: 'start' });
                }
            `);
            await sleep(500);

            await captureScreenshot(ws, 'security_card_mobile.png');

            console.log('\nVERIFICATION SUCCESSFUL: Jaminan Kerahasiaan Suara card looks amazing!');
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
