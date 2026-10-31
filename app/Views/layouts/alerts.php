<?php
// app/Views/layouts/alerts.php
// Sistem Notifikasi & Pesan Flash E-Voting OSIS berbasis SweetAlert2
// Desain Modern, Responsif, Interaktif & Elegan

$flash = get_flash();
if (!empty($flash)):
    $flashJson = json_encode($flash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<div id="swal-flash-noscript-container">
    <noscript>
        <div class="container my-3">
            <?php foreach ($flash as $type => $message): 
                $alertClass = match($type) {
                    'success' => 'alert-success',
                    'error', 'danger' => 'alert-danger',
                    'warning' => 'alert-warning',
                    default   => 'alert-info'
                };
                $iconClass = match($type) {
                    'success' => 'bi-check-circle-fill',
                    'error', 'danger' => 'bi-exclamation-triangle-fill',
                    'warning' => 'bi-exclamation-circle-fill',
                    default   => 'bi-info-circle-fill'
                };
            ?>
            <div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3" role="alert">
                <i class="bi <?= $iconClass ?> fs-4 me-3"></i>
                <div class="flex-grow-1"><?= $message ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </noscript>
</div>

<script>
(function() {
    const flashData = <?= $flashJson ?>;
    if (!flashData || typeof flashData !== 'object') return;

    function renderSwalAlerts() {
        if (typeof Swal === 'undefined') {
            // Fallback jika SweetAlert2 belum selesai termuat
            const container = document.getElementById('swal-flash-noscript-container');
            if (container) {
                let fallbackHtml = '<div class="container my-3">';
                for (const [type, msg] of Object.entries(flashData)) {
                    const bsType = (type === 'error' || type === 'danger') ? 'danger' : (type === 'warning' ? 'warning' : (type === 'success' ? 'success' : 'info'));
                    fallbackHtml += `<div class="alert alert-${bsType} alert-dismissible fade show shadow-sm rounded-3 d-flex align-items-center mb-2" role="alert">
                        <div class="flex-grow-1">${msg}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
                }
                fallbackHtml += '</div>';
                container.innerHTML = fallbackHtml;
            }
            return;
        }

        const entries = Object.entries(flashData);
        if (entries.length === 0) return;

        // Tampilkan pesan satu per satu secara berurutan jika ada lebih dari satu
        async function displayQueue() {
            for (let i = 0; i < entries.length; i++) {
                const [type, message] = entries[i];
                let swalIcon = 'info';
                let swalTitle = 'Informasi Sistem';
                let btnColor = '#0284c7';
                let btnText = '<i class="bi bi-check-lg me-1"></i> Oke, Mengerti';
                let timerDuration = null;

                if (type === 'success') {
                    swalIcon = 'success';
                    swalTitle = 'Berhasil!';
                    btnColor = '#2563eb';
                    btnText = '<i class="bi bi-check2-circle me-1"></i> Mantap, Lanjutkan';
                    timerDuration = 4500;
                } else if (type === 'error' || type === 'danger') {
                    swalIcon = 'error';
                    swalTitle = 'Terjadi Kesalahan!';
                    btnColor = '#ef4444';
                    btnText = '<i class="bi bi-x-circle me-1"></i> Tutup &amp; Periksa';
                    timerDuration = null; // Biarkan pemilih/admin membaca kesalahan
                } else if (type === 'warning') {
                    swalIcon = 'warning';
                    swalTitle = 'Peringatan Sistem';
                    btnColor = '#f59e0b';
                    btnText = '<i class="bi bi-exclamation-circle me-1"></i> Saya Mengerti';
                    timerDuration = null;
                }

                await Swal.fire({
                    icon: swalIcon,
                    title: swalTitle,
                    html: `<div class="swal-message-content">${message}</div>`,
                    confirmButtonText: btnText,
                    confirmButtonColor: btnColor,
                    timer: timerDuration,
                    timerProgressBar: Boolean(timerDuration),
                    showCloseButton: true,
                    allowOutsideClick: (swalIcon === 'success' || swalIcon === 'info'),
                    customClass: {
                        popup: 'swal2-app-popup shadow-xl',
                        title: 'swal2-app-title',
                        htmlContainer: 'swal2-app-html',
                        confirmButton: 'btn px-4 py-2 rounded-3 fw-bold shadow-sm'
                    }
                });
            }
        }

        displayQueue();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderSwalAlerts);
    } else {
        renderSwalAlerts();
    }
})();
</script>
<?php endif; ?>
