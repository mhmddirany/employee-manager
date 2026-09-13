<?php
require_once __DIR__ . '/includes/auth.php';
require_role(['admin']);

$db = get_db();
$errors = [];
$success = null;

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'viewer';

    if ($name === '') $errors['name'] = 'Name is required.';
    if ($email === '') $errors['email'] = 'Email is required.';
    if ($password === '') $errors['password'] = 'Password is required.';
    if (!in_array($role, ['admin', 'editor', 'viewer'], true)) $errors['role'] = 'Invalid role.';

    if (!$errors) {
        $existing = $db->prepare('SELECT id FROM users WHERE email = ?');
        $existing->execute([$email]);
        if ($existing->fetch()) {
            $errors['email'] = 'That email is already in use.';
        }
    }

    if (!$errors) {
        $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        $newId = (int)$db->lastInsertId();
        log_activity('create', 'user', $newId, "Created user $name ($role)");
        $success = "User \"$name\" created.";
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === current_user()['id']) {
        $errors['form'] = "You can't delete your own account.";
    } else {
        $stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if ($target) {
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            log_activity('delete', 'user', $id, 'Deleted user ' . $target['name']);
            $success = "User \"{$target['name']}\" deleted.";
        }
    }
}

$users = $db->query('SELECT * FROM users ORDER BY id')->fetchAll();

$pageTitle = 'Manage Users';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Users' => ''];
$extraScripts = ['assets/js/users.js'];
require __DIR__ . '/includes/layout_top.php';
?>

<h3 class="mb-3">Users &amp; Privileges</h3>

<?php if ($success): ?><div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($errors['form']) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Existing Users</div>
            <table class="table mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge bg-info text-dark text-uppercase"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td>
                            <?php if ($u['id'] != current_user()['id']): ?>
                                <a href="users.php?delete=<?= (int)$u['id'] ?>"
                                   onclick="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>?');"
                                   class="btn btn-sm btn-outline-danger">Delete</a>
                            <?php else: ?>
                                <span class="text-muted small">(you)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Add User</div>
            <div class="card-body">
                <form method="post" id="user-form" novalidate>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name'] ?? 'Name is required.') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email'] ?? 'Email is required.') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['password'] ?? 'Password is required.') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role (privilege level) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select">
                            <option value="viewer">Viewer — read-only</option>
                            <option value="editor">Editor — create/edit employees</option>
                            <option value="admin">Admin — full access</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Create User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
