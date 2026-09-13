<?php
/**
 * Session / auth / role helpers + activity logging.
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Roles form a simple hierarchy for this demo: admin > editor > viewer.
 * require_role(['admin']) only allows admins through;
 * require_role(['admin', 'editor']) allows either.
 */
function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

function can(string $ability): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $rules = [
        'employees.view'   => ['admin', 'editor', 'viewer'],
        'employees.create' => ['admin', 'editor'],
        'employees.update' => ['admin', 'editor'],
        'employees.delete' => ['admin'],
        'users.manage'     => ['admin'],
        'logs.view'        => ['admin'],
    ];
    return in_array($user['role'], $rules[$ability] ?? [], true);
}

function log_activity(string $action, string $entity, ?int $entityId = null, string $details = ''): void
{
    $user = current_user();
    $stmt = get_db()->prepare(
        'INSERT INTO activity_logs (user_id, user_name, action, entity, entity_id, details, created_at)
         VALUES (?, ?, ?, ?, ?, ?, datetime("now"))'
    );
    $stmt->execute([
        $user['id'] ?? null,
        $user['name'] ?? 'guest',
        $action,
        $entity,
        $entityId,
        $details,
    ]);
}
