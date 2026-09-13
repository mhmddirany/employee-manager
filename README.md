# Employee Management System — PHP + jQuery

A practice project: plain PHP (no framework) on the backend, jQuery-driven AJAX on the frontend. Built to demonstrate: AJAX CRUD, live search/sort/filter, role-based privileges, activity logging, and required-field validation that blocks submission.

## Requirements

- PHP 8.1+ with the `pdo_sqlite` extension (already the default in most PHP installs)
- Nothing else — no Composer, no database server. Data is stored in a SQLite file that's created automatically on first run.

## Run it

```bash
cd project1-php-jquery
php -S localhost:8000
```

Then open **http://localhost:8000** in your browser. The database (`data/app.sqlite`) and demo data are created automatically the first time any page runs `get_db()`.

## Demo accounts

| Email | Password | Role | Can do |
|---|---|---|---|
| admin@example.com | admin123 | admin | Everything: CRUD employees, manage users, view logs |
| editor@example.com | editor123 | editor | Create/edit employees (no delete, no user/log access) |
| viewer@example.com | viewer123 | viewer | Read-only employee list |

## What's where

```
index.php            → redirects to login or dashboard
login.php            → session-based auth, required-field validation
dashboard.php        → stat cards + recent activity
employees.php        → the main jQuery showcase: search/sort/filter/paginate + add/edit modal
users.php            → admin-only: create users, assign roles (privileges)
logs.php             → admin-only: activity log viewer with filters
api/employees.php    → JSON API consumed by assets/js/app.js (GET/POST/PUT/DELETE)
includes/db.php      → PDO bootstrap; auto-creates schema + seed data
includes/auth.php    → session helpers, require_login(), require_role(), can(), log_activity()
assets/js/app.js     → all the jQuery: debounced search, delegated events, AJAX CRUD, client-side validation
database/schema_mysql.sql → equivalent schema if you want to switch to a real MySQL server
```

## Things worth pointing out 

- **Required fields / can't submit if missing:** enforced twice — once client-side in jQuery (`assets/js/app.js`'s `validateFormClientSide()`, blocks the AJAX call entirely) and again server-side in `api/employees.php`'s `validate_employee()` (returns HTTP 422 + a per-field error map that the JS then renders under each input). The server check is the one that actually matters; the client check is just a fast first pass.
- **Privileges:** `includes/auth.php`'s `can()` function is a single source of truth for what each role may do, checked both in the UI (hiding buttons/nav links) and again in every API endpoint (so a viewer can't just call the API directly to bypass the UI).
- **Activity logs:** every login, create, update, and delete calls `log_activity()`, visible to admins on `logs.php`.
- **Delegated jQuery events:** `#employees-body` and `#pagination` use `.on('click', 'selector', ...)` instead of binding directly to buttons/links, because those elements are destroyed and re-rendered on every AJAX load — a very common real-world jQuery gotcha.
