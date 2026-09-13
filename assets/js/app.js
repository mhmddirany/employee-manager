/**
 * Employees table: search, filter, sort, pagination, and a create/edit modal —
 * all driven by jQuery talking to api/employees.php.
 *
 * This file is the main "jQuery skills" showcase for the project:
 *   - debounced live search
 *   - delegated event handlers (rows are re-rendered on every load)
 *   - AJAX CRUD with client + server validation
 *   - sortable column headers
 */
$(function () {
    const state = {
        search: '',
        department: '',
        status: '',
        sort: 'name',
        dir: 'asc',
        page: 1,
    };

    const canUpdate = window.CAN_UPDATE === true;
    const canDelete = window.CAN_DELETE === true;

    function loadEmployees() {
        $('#employees-body').html('<tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>');

        $.ajax({
            url: 'api/employees.php',
            method: 'GET',
            data: {
                search: state.search,
                department: state.department,
                status: state.status,
                sort: state.sort,
                dir: state.dir,
                page: state.page,
            },
        }).done(function (res) {
            renderDepartmentOptions(res.departments);
            renderRows(res.data);
            renderPagination(res.total, res.page, res.per_page);
            updateSortIndicators();
            $('#results-summary').text(`${res.total} employee(s) found`);
        }).fail(function () {
            $('#employees-body').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load employees.</td></tr>');
        });
    }

    function renderDepartmentOptions(departments) {
        const $select = $('#filter-department');
        if ($select.data('loaded')) return; // only populate once
        departments.forEach(function (d) {
            $select.append($('<option>').val(d).text(d));
        });
        $select.data('loaded', true);
    }

    function statusBadge(status) {
        const cls = status === 'active' ? 'bg-success' : 'bg-secondary';
        return `<span class="badge ${cls}">${status}</span>`;
    }

    function renderRows(rows) {
        if (!rows.length) {
            $('#employees-body').html('<tr><td colspan="7" class="text-center text-muted py-4">No employees match your filters.</td></tr>');
            return;
        }

        const html = rows.map(function (r) {
            let actions = '';
            if (canUpdate) {
                actions += `<button class="btn btn-sm btn-outline-primary btn-edit" data-id="${r.id}">Edit</button> `;
            }
            if (canDelete) {
                actions += `<button class="btn btn-sm btn-outline-danger btn-delete" data-id="${r.id}" data-name="${escapeHtml(r.name)}">Delete</button>`;
            }

            return `<tr>
                <td>${escapeHtml(r.name)}</td>
                <td>${escapeHtml(r.email)}</td>
                <td>${escapeHtml(r.department)}</td>
                <td>${escapeHtml(r.position || '—')}</td>
                <td>${escapeHtml(r.hire_date)}</td>
                <td>${statusBadge(r.status)}</td>
                ${(canUpdate || canDelete) ? `<td>${actions}</td>` : ''}
            </tr>`;
        }).join('');

        $('#employees-body').html(html);
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }

    function renderPagination(total, page, perPage) {
        const pages = Math.max(1, Math.ceil(total / perPage));
        let html = '';
        for (let p = 1; p <= pages; p++) {
            html += `<li class="page-item ${p === page ? 'active' : ''}">
                        <a href="#" class="page-link" data-page="${p}">${p}</a>
                     </li>`;
        }
        $('#pagination').html(html);
    }

    function updateSortIndicators() {
        $('th.sortable').removeClass('sort-asc sort-desc');
        $(`th[data-sort="${state.sort}"]`).addClass(state.dir === 'asc' ? 'sort-asc' : 'sort-desc');
    }

    // ---- Search (debounced) ----
    let debounceTimer;
    $('#search').on('input', function () {
        clearTimeout(debounceTimer);
        const val = $(this).val();
        debounceTimer = setTimeout(function () {
            state.search = val;
            state.page = 1;
            loadEmployees();
        }, 300);
    });

    // ---- Filters ----
    $('#filter-department').on('change', function () {
        state.department = $(this).val();
        state.page = 1;
        loadEmployees();
    });
    $('#filter-status').on('change', function () {
        state.status = $(this).val();
        state.page = 1;
        loadEmployees();
    });
    $('#btn-clear-filters').on('click', function () {
        state.search = '';
        state.department = '';
        state.status = '';
        state.page = 1;
        $('#search').val('');
        $('#filter-department').val('');
        $('#filter-status').val('');
        loadEmployees();
    });

    // ---- Sorting ----
    $('#employees-table thead').on('click', 'th.sortable', function () {
        const col = $(this).data('sort');
        if (state.sort === col) {
            state.dir = state.dir === 'asc' ? 'desc' : 'asc';
        } else {
            state.sort = col;
            state.dir = 'asc';
        }
        loadEmployees();
    });

    // ---- Pagination (delegated — links are re-rendered every load) ----
    $('#pagination').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        state.page = parseInt($(this).data('page'), 10);
        loadEmployees();
    });

    // ---- Add / Edit modal ----
    const $modal = $('#employee-modal');
    const modalInstance = new bootstrap.Modal($modal[0]);

    function resetForm() {
        $('#employee-form')[0].reset();
        $('#employee-id').val('');
        $('#employee-form .is-invalid').removeClass('is-invalid');
        $('#form-general-error').addClass('d-none').text('');
    }

    $('#btn-new-employee').on('click', function () {
        resetForm();
        $('#employee-modal-title').text('Add Employee');
        modalInstance.show();
    });

    $('#employees-body').on('click', '.btn-edit', function () {
        const id = $(this).data('id');
        resetForm();
        $('#employee-modal-title').text('Edit Employee');

        $.get('api/employees.php', { id: id }).done(function (res) {
            const emp = res.data;
            $('#employee-id').val(emp.id);
            $('#employee-name').val(emp.name);
            $('#employee-email').val(emp.email);
            $('#employee-department').val(emp.department);
            $('#employee-position').val(emp.position);
            $('#employee-hire-date').val(emp.hire_date);
            $('#employee-status').val(emp.status);
            modalInstance.show();
        });
    });

    $('#employees-body').on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        if (!confirm(`Delete employee "${name}"? This cannot be undone.`)) return;

        $.ajax({ url: `api/employees.php?id=${id}`, method: 'DELETE' })
            .done(function () { loadEmployees(); })
            .fail(function (xhr) {
                alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Delete failed.');
            });
    });

    // Client-side required-field validation — blocks the AJAX call entirely if anything's missing.
    function validateFormClientSide() {
        let valid = true;
        $('#employee-form [required]').each(function () {
            const $f = $(this);
            const empty = !$f.val() || !$f.val().toString().trim();
            $f.toggleClass('is-invalid', empty);
            if (empty) valid = false;
        });

        const email = $('#employee-email').val();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#employee-email').addClass('is-invalid');
            valid = false;
        }

        return valid;
    }

    $('#employee-form').on('submit', function (e) {
        e.preventDefault();
        $('#form-general-error').addClass('d-none').text('');

        if (!validateFormClientSide()) {
            return; // stop here — nothing is sent to the server
        }

        const id = $('#employee-id').val();
        const payload = {
            name: $('#employee-name').val().trim(),
            email: $('#employee-email').val().trim(),
            department: $('#employee-department').val(),
            position: $('#employee-position').val().trim(),
            hire_date: $('#employee-hire-date').val(),
            status: $('#employee-status').val(),
        };

        $.ajax({
            url: id ? `api/employees.php?id=${id}` : 'api/employees.php',
            method: id ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
        }).done(function () {
            modalInstance.hide();
            loadEmployees();
        }).fail(function (xhr) {
            // Server-side validation is the real guard — client-side is just a fast first pass.
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(function (field) {
                    $(`[name="${field}"]`).addClass('is-invalid');
                    $(`.invalid-feedback[data-for="${field}"]`).text(errors[field]);
                });
            } else {
                $('#form-general-error').removeClass('d-none').text('Something went wrong. Please try again.');
            }
        });
    });

    loadEmployees();
});
