// tests/test_real_browser.js
const { spawn } = require('child_process');
const http = require('http');
const sleep = ms => new Promise(r => setTimeout(r, ms));

const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const chromeProcess = spawn(edgePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--user-data-dir=' + __dirname + '/edge_temp_profile_test',
    'http://localhost/E-vooting%20OSIS/index.php'
]);

setTimeout(async () => {
    try {
        const pages = await getJson('http://127.0.0.1:9222/json');
        const page = pages.find(p => p.type === 'page' && p.url.includes('index.php'));
        if (!page) {
            console.error('Target page not found in CDP list');
            cleanup();
            return;
        }

        console.log('Connecting to WebSocket:', page.webSocketDebuggerUrl);
        const ws = new WebSocket(page.webSocketDebuggerUrl);

        let msgId = 1;
        const pending = new Map();

        ws.onopen = async () => {
            console.log('WS Connected!');

            // Listen for console logs
            send(ws, 'Console.enable');
            send(ws, 'Runtime.enable');

            // 1. Check bootstrap availability
            const bsCheck = await evalJs(ws, `({
                hasBootstrap: typeof bootstrap !== 'undefined',
                hasModal: typeof bootstrap !== 'undefined' && !!bootstrap.Modal,
                hasDropdown: typeof bootstrap !== 'undefined' && !!bootstrap.Dropdown,
                hasCollapse: typeof bootstrap !== 'undefined' && !!bootstrap.Collapse,
                title: document.title,
                scripts: Array.from(document.querySelectorAll('script')).map(s => s.src || 'inline')
            })`);
            console.log('\n[INDEX.PHP RUNTIME CHECK]:');
            console.log(JSON.stringify(bsCheck, null, 2));

            // 2. Test clicking mobile navbar hamburger
            const clickNav = await evalJs(ws, `(() => {
                const btn = document.getElementById('navbar-hamburger-btn');
                const navContent = document.getElementById('navbarContent');
                if (!btn || !navContent) return { error: 'elements not found' };
                const beforeShow = navContent.classList.contains('show');
                btn.click();
                const afterClickShow = navContent.classList.contains('show') || navContent.classList.contains('collapsing');
                return {
                    beforeShow,
                    afterClickShow,
                    classes: navContent.className
                };
            })()`);
            console.log('\n[NAVBAR HAMBURGER CLICK TEST]:', JSON.stringify(clickNav, null, 2));

            // 3. Log in as admin via login form
            console.log('\nNavigating to login.php...');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/login.php' });
            await sleep(1500);

            console.log('Logging in as admin...');
            await evalJs(ws, `(() => {
                document.querySelector('input[name="username"]').value = 'admin';
                document.querySelector('input[name="password"]').value = 'admin123';
                document.querySelector('form').submit();
            })()`);
            await sleep(2500);

            // 4. Now navigate to admin/elections.php
            console.log('\nNavigating to admin/elections.php...');
            await send(ws, 'Page.navigate', { url: 'http://localhost/E-vooting%20OSIS/admin/elections.php' });
            await sleep(2000);

            const adminCheck = await evalJs(ws, `({
                url: window.location.href,
                title: document.title,
                hasBootstrap: typeof bootstrap !== 'undefined',
                hasModal: typeof bootstrap !== 'undefined' && !!bootstrap.Modal,
                hasDropdown: typeof bootstrap !== 'undefined' && !!bootstrap.Dropdown
            })`);
            console.log('\n[ADMIN/ELECTIONS RUNTIME CHECK]:');
            console.log(JSON.stringify(adminCheck, null, 2));

            // Test User Dropdown in Admin Header
            const dropdownTest = await evalJs(ws, `(() => {
                const btn = document.getElementById('dashboardUserDropdown');
                const menu = document.getElementById('dashboardUserDropdownMenu');
                if (!btn || !menu) return { error: 'dropdown btn or menu not found' };
                const beforeShow = menu.classList.contains('show');
                btn.click();
                const afterShow = menu.classList.contains('show');
                const display = window.getComputedStyle(menu).display;
                const visibility = window.getComputedStyle(menu).visibility;
                const rect = menu.getBoundingClientRect();
                return {
                    beforeShow,
                    afterShow,
                    display,
                    visibility,
                    rect: { top: rect.top, left: rect.left, width: rect.width, height: rect.height }
                };
            })()`);
            console.log('\n[ADMIN USER DROPDOWN CLICK TEST]:', JSON.stringify(dropdownTest, null, 2));

            // Test Direct bootstrap.Modal.show() call
            const directModalTest = await evalJs(ws, `(() => {
                const modalEl = document.getElementById('electionModal');
                try {
                    const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                    inst.show();
                    return {
                        success: true,
                        hasShow: modalEl.classList.contains('show'),
                        display: window.getComputedStyle(modalEl).display,
                        classes: modalEl.className
                    };
                } catch (err) {
                    return { error: err.message, stack: err.stack };
                }
            })()`);
            console.log('\n[DIRECT BOOTSTRAP.MODAL.SHOW() TEST]:', JSON.stringify(directModalTest, null, 2));

            // Wait 500ms to see if transition finished
            await sleep(500);
            const afterDirectWait = await evalJs(ws, `(() => {
                const modalEl = document.getElementById('electionModal');
                return {
                    hasShow: modalEl.classList.contains('show'),
                    display: window.getComputedStyle(modalEl).display,
                    classes: modalEl.className
                };
            })()`);
            console.log('[AFTER 500MS DIRECT WAIT]:', JSON.stringify(afterDirectWait, null, 2));

            // Test WHY Data-API click failed
            const whyDataApiFailed = await evalJs(ws, `(() => {
                const btn = document.querySelector('[data-bs-target="#electionModal"]');
                const targetSelector = btn.getAttribute('data-bs-target');
                const target = document.querySelector(targetSelector);
                const listeners = getEventListeners ? 'has getEventListeners' : 'no getEventListeners';
                
                // Let's test dispatching a PointerEvent / MouseEvent with bubbles: true
                const clickEvent = new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                });
                btn.dispatchEvent(clickEvent);
                
                return {
                    targetSelector,
                    targetFound: !!target,
                    targetId: target?.id,
                    targetDisplayImmediately: window.getComputedStyle(target).display,
                    targetClassesImmediately: target?.className
                };
            })()`);
            console.log('\n[DISPATCH MOUSE EVENT TEST]:', JSON.stringify(whyDataApiFailed, null, 2));

            await sleep(500);
            const afterMouseEventWait = await evalJs(ws, `(() => {
                const modalEl = document.getElementById('electionModal');
                return {
                    hasShow: modalEl.classList.contains('show'),
                    display: window.getComputedStyle(modalEl).display,
                    classes: modalEl.className
                };
            })()`);
            console.log('[AFTER 500MS MOUSE EVENT WAIT]:', JSON.stringify(afterMouseEventWait, null, 2));

            cleanup();
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.method === 'Runtime.consoleAPICalled') {
                console.log('BROWSER CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description).join(' '));
            }
            if (data.method === 'Runtime.exceptionThrown') {
                console.error('BROWSER JS EXCEPTION:', data.params.exceptionDetails.text, data.params.exceptionDetails.exception?.description);
            }
            if (data.id && pending.has(data.id)) {
                pending.get(data.id)(data.result);
                pending.delete(data.id);
            }
        };

        function send(ws, method, params = {}) {
            return new Promise((resolve) => {
                const id = msgId++;
                pending.set(id, resolve);
                ws.send(JSON.stringify({ id, method, params }));
            });
        }

        async function evalJs(ws, expression) {
            const res = await send(ws, 'Runtime.evaluate', {
                expression,
                returnByValue: true,
                awaitPromise: true
            });
            return res.result?.value;
        }

    } catch (e) {
        console.error('Test Error:', e);
        cleanup();
    }
}, 3000);

function getJson(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });
}

function cleanup() {
    chromeProcess.kill();
    setTimeout(() => process.exit(0), 500);
}
