<?php
// app/Views/layouts/alerts.php
$flash = get_flash();
if ($flash):
?>
<div class="container my-3">
    <?php foreach ($flash as $type => $message): 
        $alertClass = match($type) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            default   => 'alert-info'
        };
        $iconClass = match($type) {
            'success' => 'bi-check-circle-fill',
            'error'   => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default   => 'bi-info-circle-fill'
        };
    ?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="bi <?= $iconClass ?> fs-4 me-3"></i>
        <div class="flex-grow-1">
            <?= $message ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
