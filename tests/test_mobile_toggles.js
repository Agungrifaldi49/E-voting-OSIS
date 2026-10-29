// tests/test_mobile_toggles.js
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const sleep = ms => new Promise(r => setTimeout(r, ms));

function getJson(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    resolve(JSON.parse(data));
                } catch(e) {
                    reject(e);
                }
            });
        }).on('error', reject);
    });
}

async function run() {
    console.log('--- STARTING MOBILE TOGGLES VERIFICATION ---');
    const profileDir = path.join(__dirname, 'edge_temp_profile_mobile');
    if (!fs.existsSync(profileDir)) {
        fs.mkdirSync(profileDir, { recursive: true });
    }

    const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
    const chrome = spawn(edgePath, [
        '--headless=new',
        '--remote-debugging-port=9224',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        '--user-data-dir=' + profileDir,
        'http://localhost/E-vooting%20OSIS/index.php'
    ]);

    chrome.on('error', (err) => {
        console.error('Failed to spawn Edge:', err);
        process.exit(1);
    });

    let wsUrl = null;
    for (let i = 0; i < 15; i++) {
        await sleep(500);
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
        console.error('Could not connect to CDP on port 9224');
        chrome.kill();
        process.exit(1);
    }

    console.log('Connected to CDP:', wsUrl);
    const ws = new globalThis.WebSocket(wsUrl);

    let msgId = 1;
    const pending = new Map();

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.id && pending.has(data.id)) {
            pending.get(data.id)(data.result);
            pending.delete(data.id);
        }
    };

    function send(method, params = {}) {
        return new Promise((resolve) => {
            const id = msgId++;
            pending.set(id, resolve);
            ws.send(JSON.stringify({ id, method, params }));
        });
    }

    async function evaluate(expression) {
        const res = await send('Runtime.evaluate', {
            expression,
            returnByValue: true,
            awaitPromise: true
        });
        return res?.result?.value;
    }

    async function navigate(url) {
        await send('Page.navigate', { url });
        await sleep(1500);
    }

    ws.onopen = async () => {
        try {
            // Emulate Mobile Device (iPhone 12 / Modern Android: 390 x 844, touch: true)
            await send('Emulation.setDeviceMetricsOverride', {
                width: 390,
                height: 844,
                deviceScaleFactor: 3,
                mobile: true
            });
            await send('Emulation.setTouchEmulationEnabled', {
                enabled: true
            });

            // ===============================================
            // TEST 1: Public Navbar Toggle on index.php
            // ===============================================
            console.log('\n[TEST 1] Public Navbar Toggle on index.php...');
            await navigate('http://localhost/E-vooting%20OSIS/index.php');

            const state1 = await evaluate(`(() => {
                const btn = document.getElementById('navbar-hamburger-btn');
                const target = document.getElementById('navbarContent');
                return {
                    btnFound: !!btn,
                    btnVisible: btn ? window.getComputedStyle(btn).display !== 'none' : false,
                    targetDisplay: target ? window.getComputedStyle(target).display : null,
                    targetClasses: target ? target.className : null,
                    isShown: target ? target.classList.contains('show') : false
                };
            })()`);
            console.log('Before toggle click:', state1);

            // Tap the hamburger button
            await evaluate(`document.getElementById('navbar-hamburger-btn').click()`);
            await sleep(500);

            const state2 = await evaluate(`(() => {
                const target = document.getElementById('navbarContent');
                const btn = document.getElementById('navbar-hamburger-btn');
                return {
                    targetDisplay: target ? window.getComputedStyle(target).display : null,
                    targetClasses: target ? target.className : null,
                    targetHeight: target ? target.offsetHeight : 0,
                    isShown: target ? target.classList.contains('show') : false,
                    ariaExpanded: btn ? btn.getAttribute('aria-expanded') : null
                };
            })()`);
            console.log('After toggle click:', state2);

            // Test auto-close when clicking a link
            await evaluate(`(() => {
                const link = document.querySelector('#navbarContent a.nav-link');
                if (link) link.click();
            })()`);
            await sleep(500);

            const state3 = await evaluate(`(() => {
                const target = document.getElementById('navbarContent');
                return {
                    targetDisplay: target ? window.getComputedStyle(target).display : null,
                    isShown: target ? target.classList.contains('show') : false
                };
            })()`);
            console.log('After clicking nav-link (auto-close):', state3);

            const test1Passed = state1.btnFound && state1.btnVisible && !state1.isShown && state2.isShown && state2.targetHeight > 50 && !state3.isShown;
            console.log('TEST 1 PASSED:', test1Passed);

            // ===============================================
            // TEST 2: Dashboard Sidebar Mobile Toggle
            // ===============================================
            console.log('\n[TEST 2] Dashboard Mobile Sidebar Toggle...');
            // Login as admin or siswa
            await navigate('http://localhost/E-vooting%20OSIS/login.php');
            await evaluate(`(() => {
                const userInput = document.querySelector('[name="username"]');
                const passInput = document.querySelector('[name="password"]');
                const form = document.querySelector('form');
                if (userInput) userInput.value = 'admin';
                if (passInput) passInput.value = 'admin123';
                if (form) form.submit();
            })()`);
            await sleep(2000);

            await navigate('http://localhost/E-vooting%20OSIS/admin/dashboard.php');

            const dashState1 = await evaluate(`(() => {
                const btn = document.getElementById('dashboardSidebarToggle');
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                return {
                    btnFound: !!btn,
                    btnVisible: btn ? window.getComputedStyle(btn).display !== 'none' : false,
                    sidebarFound: !!sidebar,
                    sidebarLeft: sidebar ? window.getComputedStyle(sidebar).left : null,
                    sidebarHasShow: sidebar ? sidebar.classList.contains('show-mobile') : false,
                    backdropHasDNone: backdrop ? backdrop.classList.contains('d-none') : false
                };
            })()`);
            console.log('Before dashboard sidebar toggle click:', dashState1);

            // Tap the sidebar toggle button
            await evaluate(`document.getElementById('dashboardSidebarToggle').click()`);
            await sleep(500);

            const dashState2 = await evaluate(`(() => {
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                return {
                    sidebarLeft: sidebar ? window.getComputedStyle(sidebar).left : null,
                    sidebarHasShow: sidebar ? sidebar.classList.contains('show-mobile') : false,
                    backdropHasDNone: backdrop ? backdrop.classList.contains('d-none') : false,
                    backdropDisplay: backdrop ? window.getComputedStyle(backdrop).display : null
                };
            })()`);
            console.log('After dashboard sidebar toggle click:', dashState2);

            // Tap backdrop to close
            await evaluate(`document.getElementById('sidebarBackdrop').click()`);
            await sleep(500);

            const dashState3 = await evaluate(`(() => {
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                return {
                    sidebarLeft: sidebar ? window.getComputedStyle(sidebar).left : null,
                    sidebarHasShow: sidebar ? sidebar.classList.contains('show-mobile') : false,
                    backdropHasDNone: backdrop ? backdrop.classList.contains('d-none') : false
                };
            })()`);
            console.log('After backdrop click (drawer close):', dashState3);

            const test2Passed = dashState1.btnFound && dashState1.btnVisible && !dashState1.sidebarHasShow && dashState2.sidebarHasShow && dashState2.sidebarLeft === '0px' && !dashState3.sidebarHasShow;
            console.log('TEST 2 PASSED:', test2Passed);

            console.log('\n=============================================');
            console.log(`TEST 1 (Public Mobile Navbar Toggle): ${test1Passed ? '✓ PASSED' : '✗ FAILED'}`);
            console.log(`TEST 2 (Dashboard Mobile Sidebar Toggle): ${test2Passed ? '✓ PASSED' : '✗ FAILED'}`);
            console.log('=============================================');

            const allPassed = test1Passed && test2Passed;
            ws.close();
            chrome.kill();
            process.exit(allPassed ? 0 : 1);
        } catch (err) {
            console.error('Fatal Test Error:', err);
            ws.close();
            chrome.kill();
            process.exit(1);
        }
    };
}

run();
