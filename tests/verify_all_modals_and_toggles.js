// tests/verify_all_modals_and_toggles.js
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

async function main() {
    console.log('--- STARTING REAL EDGE E2E TEST ---');
    const profileDir = path.join(__dirname, 'edge_temp_profile_verify');
    if (!fs.existsSync(profileDir)) {
        fs.mkdirSync(profileDir, { recursive: true });
    }

    const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
    const chrome = spawn(edgePath, [
        '--headless=new',
        '--remote-debugging-port=9223',
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

    // Wait for CDP port to open
    let wsUrl = null;
    for (let i = 0; i < 15; i++) {
        await sleep(500);
        try {
            const pages = await getJson('http://127.0.0.1:9223/json');
            const p = pages.find(item => item.type === 'page');
            if (p && p.webSocketDebuggerUrl) {
                wsUrl = p.webSocketDebuggerUrl;
                break;
            }
        } catch (e) {}
    }

    if (!wsUrl) {
        console.error('Could not connect to CDP WebSocket on 9223');
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
            const testResults = [];

            // ==========================================
            // TEST 1: Mobile Navbar on index.php
            // ==========================================
            console.log('\n[1/6] Testing Mobile Navbar on index.php...');
            await send('Emulation.setDeviceMetricsOverride', {
                width: 375,
                height: 812,
                deviceScaleFactor: 2,
                mobile: true
            });
            await navigate('http://localhost/E-vooting%20OSIS/index.php');

            const navBefore = await evaluate(`(() => {
                const btn = document.getElementById('navbar-hamburger-btn');
                const menu = document.getElementById('navbarContent');
                return {
                    btnFound: !!btn,
                    menuShown: menu ? menu.classList.contains('show') : false
                };
            })()`);

            // Click hamburger
            await evaluate(`document.getElementById('navbar-hamburger-btn').click()`);
            await sleep(500);

            const navAfter = await evaluate(`(() => {
                const menu = document.getElementById('navbarContent');
                return {
                    menuShown: menu ? menu.classList.contains('show') : false,
                    display: menu ? window.getComputedStyle(menu).display : null,
                    height: menu ? menu.offsetHeight : 0
                };
            })()`);

            const test1Passed = navBefore.btnFound && navAfter.menuShown && navAfter.height > 50;
            console.log('Mobile Navbar Result:', { navBefore, navAfter, passed: test1Passed });
            testResults.push({ name: 'Mobile Navbar Toggler', passed: test1Passed });

            // Reset viewport to desktop
            await send('Emulation.setDeviceMetricsOverride', {
                width: 1280,
                height: 800,
                deviceScaleFactor: 1,
                mobile: false
            });

            // ==========================================
            // Login as Admin
            // ==========================================
            console.log('\nLogging in as admin...');
            await navigate('http://localhost/E-vooting%20OSIS/login.php');
            await evaluate(`(() => {
                const form = document.querySelector('form');
                if (form) {
                    const userInput = form.querySelector('[name="username"]');
                    const passInput = form.querySelector('[name="password"]');
                    if (userInput) userInput.value = 'admin';
                    if (passInput) passInput.value = 'admin123';
                    form.submit();
                }
            })()`);
            await sleep(2000);

            // ==========================================
            // TEST 2: User Dropdown in Dashboard Topbar
            // ==========================================
            console.log('\n[2/6] Testing User Dropdown in Admin Topbar...');
            await navigate('http://localhost/E-vooting%20OSIS/admin/elections.php');

            const dropdownCheck = await evaluate(`(() => {
                const btn = document.getElementById('dashboardUserDropdown');
                const menu = document.getElementById('dashboardUserDropdownMenu');
                const hasDuplicateCaretClass = btn ? btn.classList.contains('dropdown-toggle') : false;
                const beforeShow = menu ? menu.classList.contains('show') : false;

                // Click button
                btn.click();

                return {
                    btnFound: !!btn,
                    menuFound: !!menu,
                    hasDuplicateCaretClass,
                    beforeShow
                };
            })()`);

            await sleep(400);

            const dropdownAfter = await evaluate(`(() => {
                const menu = document.getElementById('dashboardUserDropdownMenu');
                const rect = menu ? menu.getBoundingClientRect() : null;
                return {
                    afterShow: menu ? menu.classList.contains('show') : false,
                    display: menu ? window.getComputedStyle(menu).display : null,
                    rect: rect ? { width: rect.width, height: rect.height } : null
                };
            })()`);

            const test2Passed = !dropdownCheck.hasDuplicateCaretClass && dropdownAfter.afterShow && dropdownAfter.display !== 'none';
            console.log('User Dropdown Result:', { dropdownCheck, dropdownAfter, passed: test2Passed });
            testResults.push({ name: 'User Profile Dropdown', passed: test2Passed });

            // ==========================================
            // TEST 3: Admin Elections Modals (Tambah & Edit)
            // ==========================================
            console.log('\n[3/6] Testing Admin Elections Modals (Tambah & Edit)...');
            // Test Tambah
            await evaluate(`(() => {
                const btn = document.querySelector('[data-bs-target="#electionModal"]');
                if (btn) btn.click();
            })()`);
            await sleep(500);

            const tambahElectionResult = await evaluate(`(() => {
                const modal = document.getElementById('electionModal');
                const title = document.getElementById('modalElectionTitle');
                return {
                    hasShow: modal ? modal.classList.contains('show') : false,
                    display: modal ? window.getComputedStyle(modal).display : null,
                    title: title ? title.innerText : null
                };
            })()`);

            // Close modal
            await evaluate(`(() => {
                const closeBtn = document.querySelector('#electionModal [data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            })()`);
            await sleep(500);

            // Test Edit
            await evaluate(`(() => {
                const editBtn = document.querySelector('.btn-edit-election');
                if (editBtn) editBtn.click();
            })()`);
            await sleep(500);

            const editElectionResult = await evaluate(`(() => {
                const modal = document.getElementById('electionModal');
                const title = document.getElementById('modalElectionTitle');
                const idVal = document.getElementById('el-id');
                return {
                    hasShow: modal ? modal.classList.contains('show') : false,
                    display: modal ? window.getComputedStyle(modal).display : null,
                    title: title ? title.innerText : null,
                    id: idVal ? idVal.value : null
                };
            })()`);

            const test3Passed = tambahElectionResult.hasShow && editElectionResult.hasShow && editElectionResult.id !== '0';
            console.log('Elections Modals Result:', { tambahElectionResult, editElectionResult, passed: test3Passed });
            testResults.push({ name: 'Elections Modals (Tambah & Edit)', passed: test3Passed });

            // Close modal
            await evaluate(`(() => {
                const closeBtn = document.querySelector('#electionModal [data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            })()`);
            await sleep(500);

            // ==========================================
            // TEST 4: Admin Candidates Modals (Tambah & Edit)
            // ==========================================
            console.log('\n[4/6] Testing Admin Candidates Modals (Tambah & Edit)...');
            await navigate('http://localhost/E-vooting%20OSIS/admin/candidates.php');

            // Click Tambah Paslon
            await evaluate(`(() => {
                const btn = document.querySelector('[data-bs-target="#candidateModal"]');
                if (btn) btn.click();
            })()`);
            await sleep(500);

            const tambahCandResult = await evaluate(`(() => {
                const modal = document.getElementById('candidateModal');
                const title = document.getElementById('candModalTitle');
                return {
                    hasShow: modal ? modal.classList.contains('show') : false,
                    display: modal ? window.getComputedStyle(modal).display : null,
                    title: title ? title.innerText : null
                };
            })()`);

            // Close modal
            await evaluate(`(() => {
                const closeBtn = document.querySelector('#candidateModal [data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            })()`);
            await sleep(500);

            // Click Edit Paslon
            await evaluate(`(() => {
                const editBtn = document.querySelector('.btn-edit-cand');
                if (editBtn) editBtn.click();
            })()`);
            await sleep(500);

            const editCandResult = await evaluate(`(() => {
                const modal = document.getElementById('candidateModal');
                const title = document.getElementById('candModalTitle');
                const idVal = document.getElementById('cand-id');
                return {
                    hasShow: modal ? modal.classList.contains('show') : false,
                    display: modal ? window.getComputedStyle(modal).display : null,
                    title: title ? title.innerText : null,
                    id: idVal ? idVal.value : null
                };
            })()`);

            const test4Passed = tambahCandResult.hasShow && editCandResult.hasShow && editCandResult.id !== '0';
            console.log('Candidates Modals Result:', { tambahCandResult, editCandResult, passed: test4Passed });
            testResults.push({ name: 'Candidates Modals (Tambah & Edit)', passed: test4Passed });

            // Close modal
            await evaluate(`(() => {
                const closeBtn = document.querySelector('#candidateModal [data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            })()`);
            await sleep(500);

            // ==========================================
            // TEST 5: Admin Students Modal Hapus
            // ==========================================
            console.log('\n[5/6] Testing Admin Students Modal Hapus...');
            await navigate('http://localhost/E-vooting%20OSIS/admin/students.php');

            await evaluate(`(() => {
                const delBtn = document.querySelector('[onclick*="openDeleteStudentModal"]');
                if (delBtn) delBtn.click();
            })()`);
            await sleep(500);

            const deleteStudentResult = await evaluate(`(() => {
                const modal = document.getElementById('deleteStudentModal');
                const nameEl = document.getElementById('modalStudentNama');
                return {
                    hasShow: modal ? modal.classList.contains('show') : false,
                    display: modal ? window.getComputedStyle(modal).display : null,
                    nama: nameEl ? nameEl.innerText : null
                };
            })()`);

            const test5Passed = deleteStudentResult.hasShow && !!deleteStudentResult.nama;
            console.log('Students Delete Modal Result:', { deleteStudentResult, passed: test5Passed });
            testResults.push({ name: 'Students Delete Modal', passed: test5Passed });

            // Close modal
            await evaluate(`(() => {
                const closeBtn = document.querySelector('#deleteStudentModal [data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            })()`);
            await sleep(500);

            // ==========================================
            // TEST 6: Admin Teachers Modal Hapus
            // ==========================================
            console.log('\n[6/6] Testing Admin Teachers Modal Hapus...');
            await navigate('http://localhost/E-vooting%20OSIS/admin/teachers.php');

            await evaluate(`(() => {
                const delBtn = document.querySelector('[onclick*="openDeleteTeacherModal"]');
                if (delBtn) {
                    delBtn.click();
                } else if (typeof openDeleteTeacherModal === 'function') {
                    const fakeBtn = document.createElement('button');
                    fakeBtn.setAttribute('data-id', '99');
                    fakeBtn.setAttribute('data-nama', 'Bpk. Ahmad Fauzi, S.Pd.');
                    fakeBtn.setAttribute('data-nip', '198501012010011001');
                    fakeBtn.setAttribute('data-mapel', 'Bahasa Indonesia');
                    openDeleteTeacherModal(fakeBtn);
                }
            })()`);
            await sleep(500);

            const deleteTeacherResult = await evaluate(`(() => {
                const modal = document.getElementById('deleteTeacherModal');
                const nameEl = document.getElementById('modalTeacherNama');
                return {
                    hasShow: modal ? modal.classList.contains('show') : false,
                    display: modal ? window.getComputedStyle(modal).display : null,
                    nama: nameEl ? nameEl.innerText : null
                };
            })()`);

            const test6Passed = deleteTeacherResult.hasShow && !!deleteTeacherResult.nama;
            console.log('Teachers Delete Modal Result:', { deleteTeacherResult, passed: test6Passed });
            testResults.push({ name: 'Teachers Delete Modal', passed: test6Passed });

            // ==========================================
            // SUMMARY
            // ==========================================
            console.log('\n==========================================');
            console.log('E2E TEST VERIFICATION SUMMARY:');
            console.log('==========================================');
            let allPassed = true;
            testResults.forEach(t => {
                const status = t.passed ? '✓ PASSED' : '✗ FAILED';
                console.log(`[${status}] ${t.name}`);
                if (!t.passed) allPassed = false;
            });

            console.log('\nALL TESTS PASSED:', allPassed);

            ws.close();
            chrome.kill();
            process.exit(allPassed ? 0 : 1);
        } catch (err) {
            console.error('Fatal Test Exception:', err);
            ws.close();
            chrome.kill();
            process.exit(1);
        }
    };
}

main();
