// tests/take_modal_screenshot.js
const http = require('http');
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

function getJson(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });
}

async function run() {
    const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
    const remotePort = 9225;
    const userDataDir = path.join(__dirname, '..', 'scratch_edge_profile_modal');

    console.log("Launching Edge in headless mode...");
    const edge = spawn(edgePath, [
        `--remote-debugging-port=${remotePort}`,
        '--headless=new',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        `--user-data-dir=${userDataDir}`,
        '--window-size=1280,900',
        'about:blank'
    ]);

    await new Promise(r => setTimeout(r, 2000));

    try {
        const targets = await getJson(`http://127.0.0.1:${remotePort}/json`);
        const target = targets.find(t => t.type === 'page');
        if (!target) throw new Error("No page target found");

        const WebSocketClient = globalThis.WebSocket;
        const ws = new WebSocketClient(target.webSocketDebuggerUrl);

        let id = 1;
        const pending = new Map();

        ws.addEventListener('message', (event) => {
            const data = JSON.parse(event.data);
            if (data.id && pending.has(data.id)) {
                const { resolve, reject } = pending.get(data.id);
                pending.delete(data.id);
                if (data.error) reject(data.error);
                else resolve(data.result);
            }
        });

        function send(method, params = {}) {
            return new Promise((resolve, reject) => {
                const reqId = id++;
                pending.set(reqId, { resolve, reject });
                ws.send(JSON.stringify({ id: reqId, method, params }));
            });
        }

        await new Promise(r => ws.addEventListener('open', r, { once: true }));
        await send('Page.enable');
        await send('DOM.enable');

        // Navigate to login
        await send('Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/login.php' });
        await new Promise(r => setTimeout(r, 1200));

        // Fill credentials & login
        await send('Runtime.evaluate', {
            expression: `
                document.getElementById('username').value = 'admin';
                document.getElementById('password').value = 'admin123';
                document.querySelector('button[type="submit"]').click();
            `
        });
        await new Promise(r => setTimeout(r, 1500));

        // Navigate to logs.php
        await send('Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/admin/logs.php' });
        await new Promise(r => setTimeout(r, 1200));

        // Open modal
        await send('Runtime.evaluate', {
            expression: `
                const modal = new bootstrap.Modal(document.getElementById('modalPurgeLogs'));
                modal.show();
            `
        });
        await new Promise(r => setTimeout(r, 800));

        // Check computed style of title
        const titleStyle = await send('Runtime.evaluate', {
            expression: `
                const el = document.getElementById('modalPurgeLogsTitle');
                const computed = window.getComputedStyle(el);
                JSON.stringify({
                    color: computed.color,
                    fontSize: computed.fontSize,
                    fontWeight: computed.fontWeight
                });
            `,
            returnByValue: true
        });

        console.log("Modal Title Computed Style:", titleStyle.result.value);

        // Capture screenshot
        const screenshot = await send('Page.captureScreenshot', { format: 'png' });
        const screenshotPath = path.join(__dirname, 'modal_purge_screenshot.png');
        fs.writeFileSync(screenshotPath, Buffer.from(screenshot.data, 'base64'));
        console.log(`Screenshot saved to: ${screenshotPath}`);

        ws.close();
    } finally {
        edge.kill();
        setTimeout(() => {
            try {
                fs.rmSync(userDataDir, { recursive: true, force: true });
            } catch (e) {}
        }, 1000);
    }
}

run().catch(err => {
    console.error(err);
    process.exit(1);
});
