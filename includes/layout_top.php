<?php
/**
 * Shared page shell. Include after setting:
 *   $pageTitle      (string)
 *   $breadcrumbs    (assoc array: label => url; last item's url may be '')
 * Requires auth.php already loaded and require_login()/require_role() already called.
 */
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Employee Management') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="dashboard.php">Employee Manager</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="employees.php">Employees</a></li>
            <?php if (can('users.manage')): ?>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
            <?php endif; ?>
            <?php if (can('logs.view')): ?>
                <li class="nav-item"><a class="nav-link" href="logs.php">Activity Logs</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php if ($user): ?>
        <span class="text-light me-3">
            <?= htmlspecialchars($user['name']) ?>
            <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($user['role']) ?></span>
        </span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    <?php endif; ?>
</nav>

<div class="container-fluid mt-3 px-4">
    <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php
                $keys = array_keys($breadcrumbs);
                $lastKey = end($keys);
                foreach ($breadcrumbs as $label => $url):
                    if ($label === $lastKey || $url === ''):
                ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($label) ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a></li>
                <?php endif; endforeach; ?>
            </ol>
        </nav>
    <?php endif; ?>
</div>

<div class="container-fluid px-4 pb-5">
