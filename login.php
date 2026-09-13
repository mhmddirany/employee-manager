<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side required-field check — never trust the client alone,
    // even though the form also has `required` + jQuery validation.
    if ($email === '') $errors['email'] = 'Email is required.';
    if ($password === '') $errors['password'] = 'Password is required.';

    if (!$errors) {
        $stmt = get_db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
            log_activity('login', 'user', $user['id'], 'User logged in');
            header('Location: dashboard.php');
            exit;
        }
        $errors['form'] = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Employee Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<div class="login-wrap">
    <div class="card shadow-sm" style="max-width: 400px; width: 100%;">
        <div class="card-body p-4">
            <h4 class="mb-3 text-center">Employee Manager</h4>
            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($errors['form']) ?></div>
            <?php endif; ?>
            <form id="login-form" method="post" novalidate>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email'] ?? 'Email is required.') ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['password'] ?? 'Password is required.') ?></div>
                </div>
                <button class="btn btn-primary w-100" type="submit">Log In</button>
            </form>
            <hr>
            <p class="small text-muted mb-1">Demo accounts (password shown = password):</p>
            <ul class="small text-muted mb-0">
                <li>admin@example.com / admin123 (admin — full access)</li>
                <li>editor@example.com / editor123 (editor — create/edit only)</li>
                <li>viewer@example.com / viewer123 (viewer — read-only)</li>
            </ul>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// Client-side required-field guard (server re-validates regardless — see login.php).
$('#login-form').on('submit', function (e) {
    let valid = true;
    $(this).find('[required]').each(function () {
        const $f = $(this);
        if (!$f.val().trim()) {
            $f.addClass('is-invalid');
            valid = false;
        } else {
            $f.removeClass('is-invalid');
        }
    });
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
