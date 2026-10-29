// tests/check_browser_runtime.js
const { spawn } = require('child_process');
const http = require('http');
const WebSocket = require('ws') || null;

// Use Node's built-in or simple WebSocket
const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const chromeProcess = spawn(edgePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--user-data-dir=' + __dirname + '/edge_temp_profile_2',
    'http://localhost/E-vooting%20OSIS/index.php'
]);

setTimeout(() => {
    http.get('http://127.0.0.1:9222/json', (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
            const list = JSON.parse(data);
            const page = list.find(p => p.type === 'page' && p.url.includes('index.php'));
            if (!page) {
                console.log('No index.php page found');
                chromeProcess.kill();
                process.exit(1);
            }
            console.log('Found page:', page.title);
            console.log('WebSocket URL:', page.webSocketDebuggerUrl);

            // Connect using native WebSocket or net
            testWithCdp(page.webSocketDebuggerUrl, chromeProcess);
        });
    });
}, 2500);

function testWithCdp(wsUrl, proc) {
    // Check if ws package is available
    let WebSocketClass;
    try {
        WebSocketClass = require('ws');
    } catch(e) {
        // We can inspect DOM dump directly instead
        console.log('ws package not available');
        proc.kill();
        process.exit(0);
    }
}
