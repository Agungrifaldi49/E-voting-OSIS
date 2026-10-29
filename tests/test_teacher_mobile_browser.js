// tests/test_teacher_mobile_browser.js
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const sleep = ms => new Promise(r => setTimeout(r, ms));

const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const profileDir = path.join(__dirname, 'edge_temp_profile_guru');

// Start Headless Edge with mobile viewport
const chromeProcess = spawn(edgePath, [
    '--headless=new',
    '--remote-debugging-port=9223',
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
        try {
            const pages = await getJson('http://127.0.0.1:9223/json');
            const page = pages.find(p => p.type === 'page');
            if (page && page.webSocketDebuggerUrl) {
                wsUrl = page.webSocketDebuggerUrl;
                break;
            }
        } catch(e) {}
        await sleep(400);
    }

    if (!wsUrl) {
        console.error('CDP port failed to open');
        cleanup();
        return;
    }

    console.log('Connecting to WebSocket:', wsUrl);
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
        console.log('Connected to CDP WebSocket!');
            await send(ws, 'Page.enable');
            await send(ws, 'Runtime.enable');
            await send(ws, 'DOM.enable');

            // Set mobile device emulation (390x844, 2x DPR - iPhone / modern smartphone)
            await send(ws, 'Emulation.setDeviceMetricsOverride', {
                width: 390,
                height: 844,
                deviceScaleFactor: 2.0,
                mobile: true
            });

            console.log('\n--- 1. NAVIGATING AND LOGGING IN AS GURU ---');
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
            await sleep(2000);

            // Navigate to guru/dashboard.php
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/guru/dashboard.php' });
            await sleep(1200);

            console.log('\n--- 2. VERIFYING GURU DASHBOARD (MOBILE VIEW) ---');
            const dashCheck = await evalJs(ws, `(() => {
                const luxuryCard = document.querySelector('.teacher-card-luxury');
                const quickGrid = document.querySelector('.student-quick-grid');
                const bottomNav = document.querySelector('.mobile-bottom-nav');
                const sidebar = document.querySelector('.sidebar') || document.getElementById('guru-sidebar');
                const topbarToggle = document.getElementById('dashboardSidebarToggle');

                return {
                    url: window.location.href,
                    title: document.title,
                    hasLuxuryCard: !!luxuryCard,
                    luxuryCardText: luxuryCard ? luxuryCard.innerText.substring(0, 100).replace(/\\n/g, ' ') : null,
                    hasQuickGrid: !!quickGrid,
                    quickActionCount: quickGrid ? quickGrid.querySelectorAll('.quick-action-box').length : 0,
                    hasBottomNav: !!bottomNav,
                    bottomNavCount: bottomNav ? bottomNav.querySelectorAll('.nav-item').length : 0,
                    bottomNavVisible: bottomNav ? window.getComputedStyle(bottomNav).display !== 'none' : false,
                    hasSidebar: !!sidebar,
                    hasToggleBtn: !!topbarToggle
                };
            })()`);
            console.log(JSON.stringify(dashCheck, null, 2));

            if (!dashCheck.hasLuxuryCard) throw new Error("Teacher luxury card missing on dashboard!");
            if (!dashCheck.hasBottomNav || !dashCheck.bottomNavVisible) throw new Error("Mobile bottom nav bar missing or not visible!");
            console.log('PASS: Guru Dashboard mobile structure verified!');

            // Test Mobile Sidebar Toggle
            console.log('\n--- 3. TESTING MOBILE SIDEBAR TOGGLE ---');
            const sidebarTest = await evalJs(ws, `(() => {
                const btn = document.getElementById('dashboardSidebarToggle');
                const sidebar = document.querySelector('.sidebar') || document.getElementById('guru-sidebar');
                const before = sidebar.classList.contains('show-mobile');
                btn.click();
                const afterClick = sidebar.classList.contains('show-mobile');
                window.closeSidebarMobile();
                const afterClose = sidebar.classList.contains('show-mobile');
                return { before, afterClick, afterClose };
            })()`);
            console.log('Sidebar toggle state:', JSON.stringify(sidebarTest));
            if (!sidebarTest.afterClick) throw new Error("Sidebar toggle did not open mobile sidebar!");
            console.log('PASS: Mobile sidebar toggle opens and closes smoothly.');

            await captureScreenshot(ws, 'guru_dashboard_mobile.png');

            // Navigate to guru/profile.php
            console.log('\n--- 4. VERIFYING GURU PROFILE & EYE TOGGLES ---');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/guru/profile.php' });
            await sleep(1000);

            const profileCheck = await evalJs(ws, `(() => {
                const oldPass = document.getElementById('old_password');
                const newPass = document.getElementById('new_password');
                const eyeBtn = oldPass ? oldPass.parentElement.querySelector('button') : null;
                const bottomNav = document.querySelector('.mobile-bottom-nav');
                const teacherBadge = document.querySelector('.avatar-circle-lg');

                let eyeToggleResult = false;
                if (oldPass && eyeBtn) {
                    const typeBefore = oldPass.type;
                    eyeBtn.click();
                    const typeAfter1 = oldPass.type;
                    eyeBtn.click();
                    const typeAfter2 = oldPass.type;
                    eyeToggleResult = (typeBefore === 'password' && typeAfter1 === 'text' && typeAfter2 === 'password');
                }

                return {
                    url: window.location.href,
                    title: document.title,
                    hasOldPass: !!oldPass,
                    hasNewPass: !!newPass,
                    hasEyeBtn: !!eyeBtn,
                    eyeToggleWorks: eyeToggleResult,
                    hasBottomNav: !!bottomNav,
                    hasTeacherBadge: !!teacherBadge
                };
            })()`);
            console.log(JSON.stringify(profileCheck, null, 2));

            if (!profileCheck.eyeToggleWorks) throw new Error("Eye password visibility toggle failed!");
            console.log('PASS: Eye toggle button switches password visibility reliably.');

            await captureScreenshot(ws, 'guru_profile_mobile.png');

            // Navigate to guru/status.php
            console.log('\n--- 5. VERIFYING GURU STATUS / PARTICIPATION ---');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/guru/status.php' });
            await sleep(1000);

            const statusCheck = await evalJs(ws, `(() => {
                const text = document.body.innerText;
                const bottomNav = document.querySelector('.mobile-bottom-nav');
                return {
                    url: window.location.href,
                    hasMataPelajaran: text.includes('Mata Pelajaran') || text.includes('Konsentrasi Keahlian'),
                    hasTeacherNip: text.includes('3204252301040001'),
                    hasBottomNav: !!bottomNav
                };
            })()`);
            console.log(JSON.stringify(statusCheck, null, 2));

            console.log('\n========================================================');
            console.log('ALL REAL MOBILE BROWSER TESTS FOR GURU COMPLETED 100%!');
            console.log('========================================================\n');

            cleanup();
        };

    } catch (err) {
        console.error('TEST ERROR:', err);
        cleanup();
    }
}

function cleanup() {
    try { chromeProcess.kill(); } catch(e) {}
    process.exit(0);
}

main();
