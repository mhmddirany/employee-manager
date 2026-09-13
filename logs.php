<?php
require_once __DIR__ . '/includes/auth.php';
require_role(['admin']);

$db = get_db();

$action = trim($_GET['action'] ?? '');
$entity = trim($_GET['entity'] ?? '');

$where = [];
$params = [];
if ($action !== '') { $where[] = 'action = ?'; $params[] = $action; }
if ($entity !== '') { $where[] = 'entity = ?'; $params[] = $entity; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("SELECT * FROM activity_logs $whereSql ORDER BY id DESC LIMIT 200");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Activity Logs';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Activity Logs' => ''];
require __DIR__ . '/includes/layout_top.php';
?>

<h3 class="mb-3">Activity Logs</h3>
<p class="text-muted">Every login, create, update, and delete is recorded here — visible to admins only.</p>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All actions</option>
            <?php foreach (['login', 'logout', 'create', 'update', 'delete'] as $a): ?>
                <option value="<?= $a ?>" <?= $action === $a ? 'selected' : '' ?>><?= ucfirst($a) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="entity" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All entities</option>
            <?php foreach (['employee', 'user'] as $e): ?>
                <option value="<?= $e ?>" <?= $entity === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($action || $entity): ?>
        <div class="col-auto"><a href="logs.php" class="btn btn-sm btn-outline-secondary">Clear</a></div>
    <?php endif; ?>
</form>

<div class="card">
    <table class="table table-sm table-striped mb-0">
        <thead class="table-light">
            <tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th></tr>
        </thead>
        <tbody>
            <?php if (!$logs): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No matching activity.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                    <td><?= htmlspecialchars($log['user_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td><?= htmlspecialchars($log['entity']) ?><?= $log['entity_id'] ? ' #' . (int)$log['entity_id'] : '' ?></td>
                    <td class="text-muted"><?= htmlspecialchars($log['details']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
