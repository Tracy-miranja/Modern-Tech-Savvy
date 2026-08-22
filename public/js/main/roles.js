import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import RoleService from "/js/client/RoleService.js";

const requestClient = new RequestClient();
const roleService = new RoleService(requestClient);

document.addEventListener('DOMContentLoaded', () => {
    const filterInput = document.getElementById('roleFilter');
    const rolesContainer = document.getElementById('rolesContainer');
    const assignRoleForm = document.getElementById('assignRoleForm');

    if (rolesContainer) {
        getRoles();
    }

    if (filterInput) {
        filterInput.addEventListener('input', debounce(async () => {
            await getRoles(1, filterInput.value);
        }, 300));
    }

    if (assignRoleForm) {
        assignRoleForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!assignRoleForm.checkValidity()) {
                e.stopPropagation();
                assignRoleForm.classList.add('was-validated');
                return;
            }
            assignRole(document.querySelector('button[onclick^="assignRole"]'));
        });
    }

    if ($('#assignedUsersTable').length) {
        $('#assignedUsersTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0, 3, 4], orderable: false },
                { targets: '_all', searchable: true }
            ],
            language: {
                emptyTable: "No users assigned to this role",
                loadingRecords: "Loading..."
            }
        });
    }
});

function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

window.getRoles = async function (page = 1, filter = '') {
    const container = $("#rolesContainer");
    if (!$.fn.DataTable) {
        container.html('<div class="alert alert-danger">DataTables library is not loaded</div>');
        toastr.error('DataTables library is not loaded', 'Error');
        return;
    }
    try {
        container.html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading roles...</div>');
        const data = { page, filter };
        const response = await roleService.fetch(data);
        console.log('Response from server:', response);
        if (typeof response === 'string') {
            container.html(response);
        } else if (response && response.data) {
            container.html(response.data);
        } else {
            throw new Error('Invalid response format from server');
        }

        if ($('#rolesTable').length) {
            if ($.fn.DataTable.isDataTable('#rolesTable')) {
                $('#rolesTable').DataTable().destroy();
            }
            $('#rolesTable').DataTable({
                responsive: true,
                order: [[2, 'desc']],
                columnDefs: [{ targets: '_all', searchable: true }],
                language: {
                    emptyTable: "No roles available",
                    loadingRecords: "Loading..."
                }
            });
        }
    } catch (error) {
        console.error("Error loading roles:", error);
        container.html(`<div class="alert alert-danger">Error loading roles: ${error.message}</div>`);
        toastr.error('Failed to load roles: ' + error.message, "Error");
    }
};

window.assignRole = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    const form = document.getElementById("assignRoleForm");
    const formData = new FormData(form);

    try {
        const response = await roleService.assign(formData);

        const userId = formData.get('user_id');
        const roleId = formData.get('role_id');
        const userName = $('#user_id option:selected').text().split(' (')[0];
        const userEmail = $('#user_id option:selected').text().match(/\(([^)]+)\)/)[1];

        // Get selected departments
        const departmentSelects = form.querySelectorAll('select[name="departments[]"]');
        let departmentHtml = '<span class="text-muted">No departments assigned</span>';

        if (departmentSelects.length > 0 && departmentSelects[0].value) {
            const selectedDepts = Array.from(departmentSelects[0].selectedOptions).map(opt => opt.text);
            if (selectedDepts.length > 0) {
                departmentHtml = selectedDepts.map(dept => `<span class="badge bg-info">${dept}</span>`).join(' ');
            }
        }

        const table = $('#assignedUsersTable').DataTable();
        const rowCount = table.rows().count() + 1;

        // Add row with 5 columns: #, Name, Email, Departments, Action
        table.row.add([
            rowCount,
            userName,
            userEmail,
            departmentHtml,
            `<button class="btn btn-warning btn-sm me-2" data-user="${userId}" data-role="${roleId}" onclick="editDepartments(this)">
                <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="btn btn-danger btn-sm" data-user="${userId}" data-role="${roleId}" onclick="removeRole(this)">
                <i class="bi bi-trash"></i> Remove
            </button>`
        ]).draw();

        toastr.success('Role assigned successfully.', "Success");
        form.reset();
        form.classList.remove('was-validated');
    } catch (error) {
        toastr.error('Failed to assign role: ' + error.message, "Error");
    } finally {
        btn_loader(btn, false);
    }
};

/* -----------------------------------------------------------
   Custom role builder: create/edit a business-defined role and
   its module.action permission matrix.
----------------------------------------------------------- */
let cachedModules = null;

function actionCellHtml(moduleSlug, action, checked, locked) {
    const permName = `module.${moduleSlug}.${action}`;
    return `<td class="text-center">
        <input type="checkbox" class="form-check-input role-permission-check"
            value="${permName}" ${checked ? 'checked' : ''} ${locked ? 'disabled' : ''}>
    </td>`;
}

async function renderModulesMatrix(grantedPermissions = []) {
    const tbody = document.querySelector('#roleModulesTable tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading modules…</td></tr>';

    try {
        if (!cachedModules) {
            cachedModules = await roleService.modules();
        }
        const { modules, actions } = cachedModules;

        tbody.innerHTML = modules.map(m => {
            const locked = !m.active;
            const cells = actions.map(action => {
                const permName = `module.${m.slug}.${action}`;
                const checked = grantedPermissions.includes(permName);
                return actionCellHtml(m.slug, action, checked, locked);
            }).join('');

            return `<tr class="${locked ? 'text-muted' : ''}">
                <td>${m.label}${locked ? ' <small>(not subscribed)</small>' : ''}</td>
                ${cells}
            </tr>`;
        }).join('');
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load modules.</td></tr>';
        toastr.error('Failed to load modules: ' + error.message, "Error");
    }
}

window.openNewRoleModal = async function () {
    document.getElementById('roleModalTitle').textContent = 'New Custom Role';
    const form = document.getElementById('roleForm');
    form.reset();
    document.getElementById('roleFormId').value = '';
    await renderModulesMatrix([]);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('roleModal')).show();
};

window.editRole = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    try {
        const role = await roleService.edit(btn.data('id'));

        document.getElementById('roleModalTitle').textContent = 'Edit Custom Role';
        document.getElementById('roleFormId').value = role.id;
        document.getElementById('roleFormDisplayName').value = role.display_name;
        await renderModulesMatrix(role.permissions || []);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('roleModal')).show();
    } catch (error) {
        toastr.error('Failed to load role: ' + error.message, "Error");
    } finally {
        btn_loader(btn, false);
    }
};

window.saveRole = async function (btn) {
    btn = $(btn);
    const form = document.getElementById('roleForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    btn_loader(btn, true);

    const roleId = document.getElementById('roleFormId').value;
    const displayName = document.getElementById('roleFormDisplayName').value;
    const permissions = Array.from(document.querySelectorAll('.role-permission-check:checked')).map(c => c.value);

    try {
        const data = { display_name: displayName, permissions };

        if (roleId) {
            data.role_id = roleId;
            await roleService.update(data);
        } else {
            await roleService.store(data);
        }

        bootstrap.Modal.getInstance(document.getElementById('roleModal')).hide();
        getRoles();
    } catch (error) {
        toastr.error('Failed to save role: ' + error.message, "Error");
    } finally {
        btn_loader(btn, false);
    }
};

window.deleteRole = async function (btn) {
    btn = $(btn);
    const roleId = btn.data('id');

    const { isConfirmed } = await Swal.fire({
        title: 'Are you sure?',
        text: "This removes the role from everyone currently holding it. This can't be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
    });
    if (!isConfirmed) return;

    btn_loader(btn, true);
    try {
        await roleService.destroy(roleId);
        getRoles();
    } catch (error) {
        toastr.error('Failed to delete role: ' + error.message, "Error");
    } finally {
        btn_loader(btn, false);
    }
};

window.removeRole = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    const userId = btn.data("user");
    const roleId = btn.data("role");

    Swal.fire({
        title: "Are you sure?",
        text: "This will remove the role from the user!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, remove it!"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await roleService.assign({
                    role_id: roleId,
                    user_id: userId,
                    remove: true
                });

                const table = $('#assignedUsersTable').DataTable();
                const row = btn.closest('tr');
                table.row(row).remove().draw();

                toastr.success('Role removed successfully.', "Success");
            } catch (error) {
                toastr.error('Failed to remove role: ' + error.message, "Error");
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};
