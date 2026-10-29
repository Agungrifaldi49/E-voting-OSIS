// tests/debug_browser.js
const { spawn } = require('child_process');
const http = require('http');

const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const chromeProcess = spawn(edgePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--user-data-dir=' + __dirname + '/edge_temp_profile',
    'http://localhost/E-vooting%20OSIS/index.php'
]);

setTimeout(() => {
    http.get('http://127.0.0.1:9222/json', (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
            console.log('EDGE CDP PAGES:', data);
            chromeProcess.kill();
            process.exit(0);
        });
    }).on('error', (err) => {
        console.error('CDP ERROR:', err.message);
        chromeProcess.kill();
        process.exit(1);
    });
}, 2500);
