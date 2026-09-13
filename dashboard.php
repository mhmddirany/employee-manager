<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$db = get_db();
$totalEmployees = $db->query('SELECT COUNT(*) c FROM employees')->fetch()['c'];
$activeEmployees = $db->query("SELECT COUNT(*) c FROM employees WHERE status = 'active'")->fetch()['c'];
$departments = $db->query('SELECT COUNT(DISTINCT department) c FROM employees')->fetch()['c'];
$totalUsers = $db->query('SELECT COUNT(*) c FROM users')->fetch()['c'];

$recentLogs = [];
if (can('logs.view')) {
    $recentLogs = $db->query('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 6')->fetchAll();
}

$pageTitle = 'Dashboard';
$breadcrumbs = ['Dashboard' => ''];
require __DIR__ . '/includes/layout_top.php';
?>

<h3 class="mb-4">Welcome, <?= htmlspecialchars(current_user()['name']) ?></h3>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-value"><?= (int)$totalEmployees ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-value"><?= (int)$activeEmployees ?></div>
                <div class="stat-label">Active</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-value"><?= (int)$departments ?></div>
                <div class="stat-label">Departments</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-value"><?= (int)$totalUsers ?></div>
                <div class="stat-label">System Users</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Quick Links</div>
            <div class="card-body">
                <a href="employees.php" class="btn btn-outline-primary btn-sm me-2 mb-2">View Employees</a>
                <?php if (can('employees.create')): ?>
                    <a href="employees.php#new" class="btn btn-outline-success btn-sm me-2 mb-2">Add Employee</a>
                <?php endif; ?>
                <?php if (can('users.manage')): ?>
                    <a href="users.php" class="btn btn-outline-secondary btn-sm me-2 mb-2">Manage Users</a>
                <?php endif; ?>
                <?php if (can('logs.view')): ?>
                    <a href="logs.php" class="btn btn-outline-dark btn-sm mb-2">View Activity Logs</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (can('logs.view')): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Recent Activity</div>
            <ul class="list-group list-group-flush">
                <?php if (!$recentLogs): ?>
                    <li class="list-group-item text-muted">No activity yet.</li>
                <?php endif; ?>
                <?php foreach ($recentLogs as $log): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>
                            <strong><?= htmlspecialchars($log['user_name']) ?></strong>
                            <?= htmlspecialchars($log['action']) ?> <?= htmlspecialchars($log['entity']) ?>
                            <?= $log['entity_id'] ? '#' . (int)$log['entity_id'] : '' ?>
                        </span>
                        <small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
