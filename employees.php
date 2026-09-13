<?php
require_once __DIR__ . '/includes/auth.php';
require_role(['admin', 'editor', 'viewer']);

$pageTitle = 'Employees';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Employees' => ''];
$extraScripts = ['assets/js/app.js'];
require __DIR__ . '/includes/layout_top.php';
?>
<script>
    // Read by assets/js/app.js to decide whether to render Edit/Delete buttons.
    // The API still enforces these server-side on every request — this only controls the UI.
    window.CAN_UPDATE = <?= can('employees.update') ? 'true' : 'false' ?>;
    window.CAN_DELETE = <?= can('employees.delete') ? 'true' : 'false' ?>;
</script>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Employees</h3>
    <?php if (can('employees.create')): ?>
        <button class="btn btn-success" id="btn-new-employee">+ Add Employee</button>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" id="search" class="form-control" placeholder="Search by name or email...">
            </div>
            <div class="col-md-3">
                <select id="filter-department" class="form-select">
                    <option value="">All departments</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filter-status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" id="btn-clear-filters">Clear</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="employees-table">
            <thead class="table-light">
                <tr>
                    <th data-sort="name" class="sortable">Name</th>
                    <th data-sort="email" class="sortable">Email</th>
                    <th data-sort="department" class="sortable">Department</th>
                    <th data-sort="position" class="sortable">Position</th>
                    <th data-sort="hire_date" class="sortable">Hire Date</th>
                    <th data-sort="status" class="sortable">Status</th>
                    <?php if (can('employees.update') || can('employees.delete')): ?>
                        <th style="width:130px;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="employees-body">
                <tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small id="results-summary" class="text-muted"></small>
        <nav>
            <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
        </nav>
    </div>
</div>

<!-- Add / Edit modal -->
<div class="modal fade" id="employee-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="employee-form" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="employee-modal-title">Add Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="employee-id" name="id">

          <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="employee-name" name="name" required>
            <div class="invalid-feedback" data-for="name">Name is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="employee-email" name="email" required>
            <div class="invalid-feedback" data-for="email">A valid email is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select class="form-select" id="employee-department" name="department" required>
              <option value="">-- Select --</option>
              <option>Engineering</option>
              <option>Sales</option>
              <option>Support</option>
              <option>HR</option>
              <option>Finance</option>
            </select>
            <div class="invalid-feedback" data-for="department">Department is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Position</label>
            <input type="text" class="form-control" id="employee-position" name="position">
          </div>

          <div class="mb-3">
            <label class="form-label">Hire Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="employee-hire-date" name="hire_date" required>
            <div class="invalid-feedback" data-for="hire_date">Hire date is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" id="employee-status" name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div class="alert alert-danger py-2 d-none" id="form-general-error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
