// tests/test_modal_visual.js
const http = require('http');

function get(url, headers = {}) {
    return new Promise((resolve, reject) => {
        const u = new URL(url);
        const req = http.request({
            hostname: u.hostname,
            port: u.port,
            path: u.pathname + u.search,
            method: 'GET',
            headers
        }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve({ statusCode: res.statusCode, headers: res.headers, body: data }));
        });
        req.on('error', reject);
        req.end();
    });
}

function post(url, postData, headers = {}) {
    return new Promise((resolve, reject) => {
        const u = new URL(url);
        const req = http.request({
            hostname: u.hostname,
            port: u.port,
            path: u.pathname + u.search,
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Content-Length': Buffer.byteLength(postData),
                ...headers
            }
        }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve({ statusCode: res.statusCode, headers: res.headers, body: data }));
        });
        req.on('error', reject);
        req.write(postData);
        req.end();
    });
}

async function run() {
    console.log("Memverifikasi struktur HTML modal pembersihan log...");
    
    // 1. Get login page
    const loginPage = await get('http://localhost/E-vooting%20OSIS/login.php');
    const setCookie = loginPage.headers['set-cookie'];
    const sessionCookie = setCookie ? setCookie[0].split(';')[0] : '';
    
    const csrfMatch = loginPage.body.match(/name="csrf_token"\s+value="([^"]+)"/);
    const csrf = csrfMatch ? csrfMatch[1] : '';

    // 2. Login as admin
    const postData = `csrf_token=${encodeURIComponent(csrf)}&username=admin&password=admin123`;
    const loginRes = await post('http://localhost/E-vooting%20OSIS/login.php', postData, {
        'Cookie': sessionCookie
    });

    const adminCookie = loginRes.headers['set-cookie'] ? loginRes.headers['set-cookie'][0].split(';')[0] : sessionCookie;

    // 3. Fetch admin/logs.php
    const logsPage = await get('http://localhost/E-vooting%20OSIS/admin/logs.php', {
        'Cookie': adminCookie
    });

    const html = logsPage.body;

    // Checks
    const hasModalLg = html.includes('modal-dialog modal-lg modal-dialog-centered');
    console.log(`[CHECK 1] Modal lebar (modal-lg): ${hasModalLg ? 'OK (PASSED)' : 'FAIL'}`);

    const hasPremiumHeader = html.includes('modal-header modal-header-premium');
    console.log(`[CHECK 2] Header modal-header-premium: ${hasPremiumHeader ? 'OK (PASSED)' : 'FAIL'}`);

    const hasWhiteTitle = html.includes('id="modalPurgeLogsTitle" style="color: #ffffff !important;"');
    console.log(`[CHECK 3] Judul modal kontras tinggi (#ffffff !important): ${hasWhiteTitle ? 'OK (PASSED)' : 'FAIL'}`);

    const hasStorageSnapshot = html.includes('Jumlah Rekaman Saat Ini') && html.includes('Ukuran Ruang Tabel');
    console.log(`[CHECK 4] Ringkasan snapshot kapasitas tabel dalam modal: ${hasStorageSnapshot ? 'OK (PASSED)' : 'FAIL'}`);

    const has2ColGrid = html.includes('Berdasarkan Umur Rekaman') && html.includes('Berdasarkan Kuota &amp; Reset');
    console.log(`[CHECK 5] Tata letak 2 kolom terstruktur rapi: ${has2ColGrid ? 'OK (PASSED)' : 'FAIL'}`);

    const hasInteractiveCard = html.includes('modal-purge-option');
    console.log(`[CHECK 6] Kelas kartu interaktif (modal-purge-option): ${hasInteractiveCard ? 'OK (PASSED)' : 'FAIL'}`);

    if (hasModalLg && hasPremiumHeader && hasWhiteTitle && hasStorageSnapshot && has2ColGrid && hasInteractiveCard) {
        console.log("\nSEMUA VERIFIKASI TAMPILAN MODAL SUKSES 100%!");
    } else {
        process.exit(1);
    }
}

run().catch(err => {
    console.error(err);
    process.exit(1);
});
