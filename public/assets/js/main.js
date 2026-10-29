// public/assets/js/main.js
// Logika Interaktif E-Voting OSIS SMK Muthia Harapan Cicalengka

document.addEventListener('DOMContentLoaded', function () {
    initMobileNavbar();
    initCountdown();
    initVoteConfirmation();
    initCandidateModal();
    initRealtimeResults();
});

/**
 * 0. Mobile Navigation Toggler & Auto-Close
 */
function initMobileNavbar() {
    const hamburgerBtn = document.getElementById('navbar-hamburger-btn');
    const navbarContent = document.getElementById('navbarContent');
    if (!hamburgerBtn || !navbarContent) return;

    hamburgerBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(navbarContent, { toggle: false });
            bsCollapse.toggle();
        } else {
            navbarContent.classList.toggle('show');
            const isShown = navbarContent.classList.contains('show');
            hamburgerBtn.setAttribute('aria-expanded', isShown ? 'true' : 'false');
        }
    });

    navbarContent.querySelectorAll('.nav-link, .btn').forEach(function (el) {
        el.addEventListener('click', function () {
            if (window.innerWidth < 992 && navbarContent.classList.contains('show')) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarContent);
                    if (bsCollapse) bsCollapse.hide();
                } else {
                    navbarContent.classList.remove('show');
                    hamburgerBtn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });
}

/**
 * 1. Countdown Pemilihan
 */
function initCountdown() {
    const countdownEl = document.getElementById('election-countdown');
    if (!countdownEl) return;

    const startStr = countdownEl.getAttribute('data-start');
    const endStr = countdownEl.getAttribute('data-end');
    if (!startStr || !endStr) return;

    // Server-time synchronization (avoids client clock timezone/drift errors)
    const serverNowAttr = countdownEl.getAttribute('data-server-now');
    const serverNow = serverNowAttr ? parseInt(serverNowAttr, 10) : 0;
    const timeSkew = serverNow ? (serverNow - Date.now()) : 0;

    const startDate = new Date(startStr.replace(' ', 'T') + '+07:00').getTime();
    const endDate = new Date(endStr.replace(' ', 'T') + '+07:00').getTime();

    function update() {
        const now = Date.now() + timeSkew;
        let target = startDate;
        let isStarted = false;
        let isEnded = false;

        const badge = document.getElementById('countdown-status-badge');
        const label = document.getElementById('countdown-main-label');

        if (now < startDate) {
            target = startDate;
            if (badge) badge.innerHTML = '<i class="bi bi-clock-history me-1 text-warning"></i> Menuju Pembukaan Pemilihan';
            if (label) label.innerText = 'Menuju Waktu Pemungutan Suara:';
        } else if (now >= startDate && now <= endDate) {
            target = endDate;
            isStarted = true;
            if (badge) badge.innerHTML = '<span class="live-pulse me-2"></span> <strong class="text-success">Pemilihan Sedang Berlangsung</strong>';
            if (label) label.innerText = 'Sisa Waktu Pemilihan:';
        } else {
            isEnded = true;
            const box = document.getElementById('countdown-digits-wrapper');
            if (box) {
                box.innerHTML = '<div class="alert alert-secondary text-center w-100 py-3 mb-0 fw-bold"><i class="bi bi-lock-fill me-2"></i> Pemungutan Suara Telah Ditutup</div>';
            }
            return;
        }

        const distance = target - now;
        if (distance < 0) {
            if (!isStarted) {
                location.reload();
            }
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const elDays = document.getElementById('cd-days');
        const elHours = document.getElementById('cd-hours');
        const elMinutes = document.getElementById('cd-minutes');
        const elSeconds = document.getElementById('cd-seconds');

        if (elDays) elDays.innerText = String(days).padStart(2, '0');
        if (elHours) elHours.innerText = String(hours).padStart(2, '0');
        if (elMinutes) elMinutes.innerText = String(minutes).padStart(2, '0');
        if (elSeconds) elSeconds.innerText = String(seconds).padStart(2, '0');
    }

    update();
    setInterval(update, 1000);
}

/**
 * 2. Konfirmasi Voting Modal
 */
function initVoteConfirmation() {
    const voteButtons = document.querySelectorAll('.btn-vote-trigger');
    const modalEl = document.getElementById('voteConfirmationModal');
    if (!voteButtons.length || !modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    const formCandidateId = document.getElementById('confirm-candidate-id');
    const modalCandNumber = document.getElementById('confirm-candidate-number');
    const modalCandNames = document.getElementById('confirm-candidate-names');

    voteButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const cid = this.getAttribute('data-id');
            const cnum = this.getAttribute('data-nomor');
            const cnames = this.getAttribute('data-names');

            if (formCandidateId) formCandidateId.value = cid;
            if (modalCandNumber) modalCandNumber.innerText = cnum;
            if (modalCandNames) modalCandNames.innerText = cnames;

            modal.show();
        });
    });
}

/**
 * 3. Modal Visi Misi Kandidat
 */
function initCandidateModal() {
    const detailButtons = document.querySelectorAll('.btn-detail-trigger');
    const modalEl = document.getElementById('candidateDetailModal');
    if (!detailButtons.length || !modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    const titleEl = document.getElementById('modal-cand-title');
    const visiEl = document.getElementById('modal-cand-visi');
    const misiEl = document.getElementById('modal-cand-misi');
    const prokerEl = document.getElementById('modal-cand-proker');

    function formatListPoints(raw) {
        if (!raw || !raw.trim()) return '<em class="text-muted small">Tidak ada data yang dicantumkan.</em>';
        const lines = raw.trim().split('\n');
        let html = '<div class="d-flex flex-column gap-2">';
        lines.forEach(line => {
            const trimmed = line.trim();
            if (!trimmed) return;
            const match = trimmed.match(/^(\d+[\.\)]|[a-zA-Z][\.\)]|[•\*\-])\s*(.*)$/);
            if (match) {
                const marker = match[1];
                const content = match[2];
                html += `<div class="d-flex align-items-start gap-2">
                    <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1 mt-1 font-monospace">${marker}</span>
                    <span class="text-dark">${content}</span>
                </div>`;
            } else {
                html += `<div class="text-dark">${trimmed}</div>`;
            }
        });
        html += '</div>';
        return html;
    }

    detailButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const num = this.getAttribute('data-nomor');
            const names = this.getAttribute('data-names');
            const visi = this.getAttribute('data-visi');
            const misi = this.getAttribute('data-misi');
            const proker = this.getAttribute('data-proker');

            if (titleEl) titleEl.innerText = `Pasangan Calon No. ${num} - ${names}`;
            if (visiEl) visiEl.innerHTML = `<div class="lead fst-italic text-dark">"${visi || '-'}"</div>`;
            if (misiEl) misiEl.innerHTML = formatListPoints(misi);
            if (prokerEl) prokerEl.innerHTML = formatListPoints(proker);

            modal.show();
        });
    });
}

/**
 * 4. Realtime Polling Hasil Voting (Chart.js + Fetch API)
 */
let resultsChartInstance = null;
let pollingIntervalId = null;
let currentApiUrl = null;

function initRealtimeResults() {
    const chartCanvas = document.getElementById('liveResultsChart');
    if (!chartCanvas) return;

    const electionId = chartCanvas.getAttribute('data-election-id') || '';
    const apiUrl = chartCanvas.getAttribute('data-api-url') || (window.location.origin + '/E-vooting%20OSIS/api/results.php?election_id=' + electionId);
    currentApiUrl = apiUrl;

    // Initial fetch
    fetchResults(apiUrl);

    // Set polling interval 3.5 seconds
    pollingIntervalId = setInterval(() => {
        if (document.visibilityState === 'visible') {
            fetchResults(apiUrl);
        }
    }, 3500);

    // Handle tab visibility
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            fetchResults(apiUrl);
        }
    });
}

function triggerManualRefresh() {
    if (!currentApiUrl) return;
    const icon = document.getElementById('refresh-icon');
    if (icon) icon.classList.add('spin-animation');
    fetchResults(currentApiUrl).finally(() => {
        setTimeout(() => {
            if (icon) icon.classList.remove('spin-animation');
        }, 600);
    });
}
window.triggerManualRefresh = triggerManualRefresh;

async function fetchResults(apiUrl) {
    try {
        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) return;
        const data = await response.json();

        if (!data.success) {
            const hiddenMsg = document.getElementById('results-hidden-message');
            const liveContainer = document.getElementById('results-live-container');
            if (hiddenMsg && liveContainer) {
                hiddenMsg.classList.remove('d-none');
                liveContainer.classList.add('d-none');
            }
            return;
        }

        updateResultsUI(data);
    } catch (err) {
        console.warn('Realtime polling network note:', err);
    }
}

function updateResultsUI(data) {
    const hiddenMsg = document.getElementById('results-hidden-message');
    const liveContainer = document.getElementById('results-live-container');
    if (hiddenMsg && liveContainer) {
        hiddenMsg.classList.add('d-none');
        liveContainer.classList.remove('d-none');
    }

    // Update total suara sah
    const totalSuaraEl = document.getElementById('total-votes-count');
    if (totalSuaraEl) {
        totalSuaraEl.innerText = data.total_suara_sah.toLocaleString('id-ID');
    }

    // Update timestamp
    const updatedTimeEl = document.getElementById('results-last-updated');
    if (updatedTimeEl) {
        updatedTimeEl.innerText = data.updated_at_formatted || 'Baru saja';
    }

    // Calculate max votes for leading badge
    let maxVotes = 0;
    if (Array.isArray(data.candidates) && data.total_suara_sah > 0) {
        data.candidates.forEach(c => {
            if (c.total_suara > maxVotes) maxVotes = c.total_suara;
        });
    }

    // Update per candidate counts, percentages, progress bars, and photo cards
    if (Array.isArray(data.candidates)) {
        data.candidates.forEach(cand => {
            const voteEl = document.getElementById(`cand-votes-${cand.id}`);
            const voteNumEl = document.getElementById(`cand-votes-num-${cand.id}`);
            const pctEl = document.getElementById(`cand-pct-${cand.id}`);
            const pctBadge = document.getElementById(`cand-pct-badge-${cand.id}`);
            const barEl = document.getElementById(`cand-bar-${cand.id}`);
            const cardEl = document.getElementById(`live-cand-card-${cand.id}`);
            const leadBadge = document.getElementById(`cand-lead-badge-${cand.id}`);

            const formattedVotes = cand.total_suara.toLocaleString('id-ID');
            if (voteEl) voteEl.innerText = formattedVotes + ' Suara';
            if (voteNumEl) voteNumEl.innerText = formattedVotes;
            if (pctEl) pctEl.innerText = cand.persentase + '%';
            if (pctBadge) pctBadge.innerText = cand.persentase + '%';
            if (barEl) {
                barEl.style.width = cand.persentase + '%';
                barEl.setAttribute('aria-valuenow', cand.persentase);
            }

            // Leading Paslon Highlight
            const isLeading = (maxVotes > 0 && cand.total_suara === maxVotes);
            if (leadBadge) {
                if (isLeading) {
                    leadBadge.classList.remove('d-none');
                } else {
                    leadBadge.classList.add('d-none');
                }
            }
            if (cardEl) {
                if (isLeading) {
                    cardEl.classList.add('is-leader');
                } else {
                    cardEl.classList.remove('is-leader');
                }
            }
        });

        // Update Chart.js
        updateChartJs(data.candidates);
    }

    // Update turnout stats if present
    if (data.turnout) {
        const turnoutBadge = document.getElementById('turnout-pct-badge');
        if (turnoutBadge) turnoutBadge.innerText = data.turnout.turnout_pct + '%';

        const elEligible = document.getElementById('stat-total-eligible');
        const elVoted = document.getElementById('stat-total-voted');
        const elUnvoted = document.getElementById('stat-total-unvoted');
        const elTurnoutPct = document.getElementById('stat-turnout-pct');

        if (elEligible) elEligible.innerText = data.turnout.total_eligible;
        if (elVoted) elVoted.innerText = data.turnout.total_voted;
        if (elUnvoted) elUnvoted.innerText = data.turnout.total_unvoted;
        if (elTurnoutPct) elTurnoutPct.innerText = data.turnout.turnout_pct + '%';
    }
}

function updateChartJs(candidates) {
    const canvas = document.getElementById('liveResultsChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = candidates.map(c => `No. ${c.nomor_urut} - ${c.nama_ketua.split(' ')[0]} & ${c.nama_wakil.split(' ')[0]}`);
    const votesData = candidates.map(c => c.total_suara);

    const colorPalette = [
        '#2563eb', // Blue
        '#f59e0b', // Gold
        '#10b981', // Emerald
        '#8b5cf6', // Purple
        '#ec4899'  // Pink
    ];

    if (!resultsChartInstance) {
        // Create chart for the first time
        const ctx = canvas.getContext('2d');
        resultsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Perolehan Suara',
                    data: votesData,
                    backgroundColor: colorPalette.slice(0, candidates.length),
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 600
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ` ${context.raw} Suara`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    } else {
        // Reuse existing chart instance without destroying canvas
        resultsChartInstance.data.labels = labels;
        resultsChartInstance.data.datasets[0].data = votesData;
        resultsChartInstance.update('none'); // Update smoothly without jarring animation
    }
}
