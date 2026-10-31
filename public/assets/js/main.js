// public/assets/js/main.js
// Logika Interaktif E-Voting OSIS SMK Muthia Harapan Cicalengka

document.addEventListener('DOMContentLoaded', function () {
    initMobileNavbar();
    initCountdown();
    initVoteConfirmation();
    initCandidateModal();
    initRealtimeResults();
    initSweetAlertBridges();
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

/**
 * 5. SweetAlert2 Global Helpers & Confirmation Bridges
 */
window.showToast = function (icon, message, duration = 3500) {
    if (typeof Swal === 'undefined') {
        alert(message);
        return Promise.resolve();
    }
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: duration,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    return Toast.fire({
        icon: icon || 'info',
        title: message
    });
};

window.showSuccessAlert = function (message, title = 'Berhasil!') {
    if (typeof Swal === 'undefined') {
        alert(message);
        return Promise.resolve();
    }
    return Swal.fire({
        icon: 'success',
        title: title,
        html: `<div class="swal-message-content">${message}</div>`,
        confirmButtonColor: '#2563eb',
        confirmButtonText: '<i class="bi bi-check2-circle me-1"></i> Oke, Siap',
        timer: 4500,
        timerProgressBar: true,
        showCloseButton: true,
        customClass: {
            popup: 'swal2-app-popup shadow-xl',
            title: 'swal2-app-title',
            htmlContainer: 'swal2-app-html',
            confirmButton: 'btn btn-primary-custom px-4 py-2 rounded-3 fw-bold'
        }
    });
};

window.showErrorAlert = function (message, title = 'Terjadi Kesalahan!') {
    if (typeof Swal === 'undefined') {
        alert(message);
        return Promise.resolve();
    }
    return Swal.fire({
        icon: 'error',
        title: title,
        html: `<div class="swal-message-content">${message}</div>`,
        confirmButtonColor: '#ef4444',
        confirmButtonText: '<i class="bi bi-x-circle me-1"></i> Tutup &amp; Periksa',
        showCloseButton: true,
        customClass: {
            popup: 'swal2-app-popup shadow-xl',
            title: 'swal2-app-title',
            htmlContainer: 'swal2-app-html',
            confirmButton: 'btn btn-danger px-4 py-2 rounded-3 fw-bold'
        }
    });
};

window.showWarningAlert = function (message, title = 'Perhatian!') {
    if (typeof Swal === 'undefined') {
        alert(message);
        return Promise.resolve();
    }
    return Swal.fire({
        icon: 'warning',
        title: title,
        html: `<div class="swal-message-content">${message}</div>`,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: '<i class="bi bi-exclamation-circle me-1"></i> Saya Mengerti',
        showCloseButton: true,
        customClass: {
            popup: 'swal2-app-popup shadow-xl',
            title: 'swal2-app-title',
            htmlContainer: 'swal2-app-html',
            confirmButton: 'btn btn-warning text-dark px-4 py-2 rounded-3 fw-bold'
        }
    });
};

window.showInfoAlert = function (message, title = 'Informasi Sistem') {
    if (typeof Swal === 'undefined') {
        alert(message);
        return Promise.resolve();
    }
    return Swal.fire({
        icon: 'info',
        title: title,
        html: `<div class="swal-message-content">${message}</div>`,
        confirmButtonColor: '#0284c7',
        confirmButtonText: '<i class="bi bi-info-circle me-1"></i> Baiklah',
        timer: 5000,
        timerProgressBar: true,
        showCloseButton: true,
        customClass: {
            popup: 'swal2-app-popup shadow-xl',
            title: 'swal2-app-title',
            htmlContainer: 'swal2-app-html',
            confirmButton: 'btn btn-info text-white px-4 py-2 rounded-3 fw-bold'
        }
    });
};

window.showConfirmDialog = function (options) {
    if (typeof Swal === 'undefined') {
        const confirmed = confirm(options.text || options.title || 'Konfirmasi tindakan?');
        if (confirmed && typeof options.onConfirm === 'function') options.onConfirm();
        return Promise.resolve({ isConfirmed: confirmed });
    }

    const isDanger = options.isDanger || false;
    return Swal.fire({
        title: options.title || 'Konfirmasi Tindakan',
        html: `<div class="text-dark small">${options.text || options.html || 'Apakah Anda yakin ingin melanjutkan tindakan ini?'}</div>`,
        icon: options.icon || (isDanger ? 'warning' : 'question'),
        showCancelButton: true,
        confirmButtonColor: isDanger ? '#ef4444' : '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: options.confirmText || (isDanger ? '<i class="bi bi-trash-fill me-1"></i> Ya, Lanjutkan' : '<i class="bi bi-check-lg me-1"></i> Ya, Lanjutkan'),
        cancelButtonText: options.cancelText || '<i class="bi bi-x-lg me-1"></i> Batal',
        reverseButtons: true,
        focusCancel: isDanger,
        showCloseButton: true,
        customClass: {
            popup: 'swal2-app-popup shadow-xl',
            title: 'swal2-app-title',
            confirmButton: `btn ${isDanger ? 'btn-danger' : 'btn-primary-custom'} px-4 py-2 rounded-3 me-2 fw-semibold`,
            cancelButton: 'btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed && typeof options.onConfirm === 'function') {
            options.onConfirm();
        }
        return result;
    });
};

function initSweetAlertBridges() {
    if (typeof Swal === 'undefined') return;

    // 1. Intercept any forms with onsubmit containing confirm(...)
    document.querySelectorAll('form[onsubmit*="confirm("]').forEach(form => {
        const onsubmitAttr = form.getAttribute('onsubmit');
        const match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
        if (match) {
            const rawMsg = match[1];
            form.removeAttribute('onsubmit');
            form.setAttribute('data-swal-bound', 'true');
            const isDanger = /hapus|delete|reset|bersihkan|peringatan|nonaktifkan/i.test(rawMsg);

            form.addEventListener('submit', function (e) {
                if (form.getAttribute('data-confirmed') === 'true') {
                    return true;
                }
                e.preventDefault();

                Swal.fire({
                    title: isDanger ? 'Konfirmasi Tindakan Penting' : 'Konfirmasi Tindakan',
                    html: `<div class="text-dark small">${rawMsg}</div>`,
                    icon: isDanger ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isDanger ? '#ef4444' : '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: isDanger ? '<i class="bi bi-exclamation-triangle-fill me-1"></i> Ya, Lanjutkan' : '<i class="bi bi-check-lg me-1"></i> Ya, Lanjutkan',
                    cancelButtonText: '<i class="bi bi-x-lg me-1"></i> Batal',
                    reverseButtons: true,
                    focusCancel: isDanger,
                    showCloseButton: true,
                    customClass: {
                        popup: 'swal2-app-popup shadow-xl',
                        title: 'swal2-app-title',
                        confirmButton: `btn ${isDanger ? 'btn-danger' : 'btn-primary-custom'} px-4 py-2 rounded-3 me-2 fw-semibold`,
                        cancelButton: 'btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold'
                    },
                    buttonsStyling: false
                }).then((res) => {
                    if (res.isConfirmed) {
                        form.setAttribute('data-confirmed', 'true');
                        form.submit();
                    }
                });
            });
        }
    });

    // 2. Intercept any links or buttons with onclick containing confirm(...)
    document.querySelectorAll('a[onclick*="confirm("], button[onclick*="confirm("]').forEach(el => {
        const onclickAttr = el.getAttribute('onclick');
        const match = onclickAttr.match(/confirm\(['"](.*?)['"]\)/);
        if (match) {
            const rawMsg = match[1];
            el.removeAttribute('onclick');
            el.setAttribute('data-swal-bound', 'true');
            const isDanger = /keluar|hapus|delete|reset/i.test(rawMsg);

            el.addEventListener('click', function (e) {
                e.preventDefault();
                const targetHref = el.getAttribute('href');

                Swal.fire({
                    title: 'Konfirmasi',
                    html: `<div class="text-dark small">${rawMsg}</div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isDanger ? '#f59e0b' : '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Ya, Lanjutkan',
                    cancelButtonText: '<i class="bi bi-x-lg me-1"></i> Tetap di Sini',
                    reverseButtons: true,
                    showCloseButton: true,
                    customClass: {
                        popup: 'swal2-app-popup shadow-xl',
                        title: 'swal2-app-title',
                        confirmButton: 'btn btn-primary-custom px-4 py-2 rounded-3 me-2 fw-semibold',
                        cancelButton: 'btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold'
                    },
                    buttonsStyling: false
                }).then((res) => {
                    if (res.isConfirmed && targetHref && targetHref !== '#') {
                        window.location.href = targetHref;
                    }
                });
            });
        }
    });

    // 3. Elements with explicit data-confirm attribute
    document.querySelectorAll('[data-confirm]:not([data-swal-bound])').forEach(el => {
        el.setAttribute('data-swal-bound', 'true');
        el.addEventListener('click', function (e) {
            if (el.getAttribute('data-confirmed') === 'true') return;
            e.preventDefault();
            const msg = el.getAttribute('data-confirm');
            const title = el.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
            const isDanger = el.getAttribute('data-confirm-danger') === 'true' || el.classList.contains('btn-danger') || /hapus|delete/i.test(msg);

            Swal.fire({
                title: title,
                html: `<div class="text-dark small">${msg}</div>`,
                icon: isDanger ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: isDanger ? '#ef4444' : '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: isDanger ? '<i class="bi bi-trash-fill me-1"></i> Ya, Hapus' : '<i class="bi bi-check-lg me-1"></i> Ya, Lanjutkan',
                cancelButtonText: '<i class="bi bi-x-lg me-1"></i> Batal',
                reverseButtons: true,
                focusCancel: isDanger,
                showCloseButton: true,
                customClass: {
                    popup: 'swal2-app-popup shadow-xl',
                    title: 'swal2-app-title',
                    confirmButton: `btn ${isDanger ? 'btn-danger' : 'btn-primary-custom'} px-4 py-2 rounded-3 me-2 fw-semibold`,
                    cancelButton: 'btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold'
                },
                buttonsStyling: false
            }).then((res) => {
                if (res.isConfirmed) {
                    if (el.tagName === 'A' && el.href) {
                        window.location.href = el.href;
                    } else if (el.form) {
                        el.form.setAttribute('data-confirmed', 'true');
                        el.form.submit();
                    } else if (el.closest('form')) {
                        const parent = el.closest('form');
                        parent.setAttribute('data-confirmed', 'true');
                        parent.submit();
                    }
                }
            });
        });
    });
}

