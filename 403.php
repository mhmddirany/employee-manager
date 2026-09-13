<?php $pageTitle = 'Access Denied'; $breadcrumbs = []; require __DIR__ . '/includes/layout_top.php'; ?>
<div class="alert alert-danger mt-4">
    <h4>403 — Access Denied</h4>
    <p>Your role does not have permission to view this page.</p>
    <a href="dashboard.php" class="btn btn-primary btn-sm">Back to Dashboard</a>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
