<?php
/**
 * JSON API for the Employees table — consumed entirely by assets/js/app.js via jQuery AJAX.
 *
 * GET    /api/employees.php                → paginated list (search/department/status/sort/page)
 * GET    /api/employees.php?id=5           → single employee
 * POST   /api/employees.php                → create   (JSON body)
 * PUT    /api/employees.php?id=5           → update   (JSON body)
 * DELETE /api/employees.php?id=5           → delete
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function validate_employee(array $d): array
{
    $errors = [];
    if (trim($d['name'] ?? '') === '') {
        $errors['name'] = 'Name is required.';
    }
    if (trim($d['email'] ?? '') === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email must be a valid address.';
    }
    if (trim($d['department'] ?? '') === '') {
        $errors['department'] = 'Department is required.';
    }
    if (trim($d['hire_date'] ?? '') === '') {
        $errors['hire_date'] = 'Hire date is required.';
    }
    return $errors;
}

// ---- GET ----
if ($method === 'GET') {
    if (!can('employees.view')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    if (isset($_GET['id'])) {
        $stmt = $db->prepare('SELECT * FROM employees WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Not found']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    }

    $search = trim($_GET['search'] ?? '');
    $department = trim($_GET['department'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $sort = $_GET['sort'] ?? 'name';
    $dir = (strtolower($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 8;

    $allowedSorts = ['name', 'email', 'department', 'position', 'hire_date', 'status'];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'name';
    }

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(name LIKE ? OR email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($department !== '') {
        $where[] = 'department = ?';
        $params[] = $department;
    }
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countStmt = $db->prepare("SELECT COUNT(*) c FROM employees $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['c'];

    $offset = ($page - 1) * $perPage;
    $sql = "SELECT * FROM employees $whereSql ORDER BY $sort $dir LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $departments = $db->query('SELECT DISTINCT department FROM employees ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'departments' => $departments,
    ]);
    exit;
}

// ---- POST (create) ----
if ($method === 'POST') {
    if (!can('employees.create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $data = json_input();
    $errors = validate_employee($data);
    if ($errors) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $db->prepare(
        'INSERT INTO employees (name, email, department, position, hire_date, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, datetime("now"))'
    );
    $stmt->execute([
        trim($data['name']),
        trim($data['email']),
        trim($data['department']),
        trim($data['position'] ?? ''),
        trim($data['hire_date']),
        $data['status'] ?? 'active',
    ]);
    $id = (int)$db->lastInsertId();
    log_activity('create', 'employee', $id, 'Created employee ' . trim($data['name']));

    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

// ---- PUT (update) ----
if ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    if (!can('employees.update') || !$id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $data = json_input();
    $errors = validate_employee($data);
    if ($errors) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $db->prepare(
        'UPDATE employees SET name=?, email=?, department=?, position=?, hire_date=?, status=?, updated_at=datetime("now")
         WHERE id = ?'
    );
    $stmt->execute([
        trim($data['name']),
        trim($data['email']),
        trim($data['department']),
        trim($data['position'] ?? ''),
        trim($data['hire_date']),
        $data['status'] ?? 'active',
        $id,
    ]);
    log_activity('update', 'employee', $id, 'Updated employee ' . trim($data['name']));

    echo json_encode(['success' => true]);
    exit;
}

// ---- DELETE ----
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!can('employees.delete') || !$id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $stmt = $db->prepare('SELECT name FROM employees WHERE id = ?');
    $stmt->execute([$id]);
    $emp = $stmt->fetch();

    $db->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
    log_activity('delete', 'employee', $id, 'Deleted employee ' . ($emp['name'] ?? "#$id"));

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
