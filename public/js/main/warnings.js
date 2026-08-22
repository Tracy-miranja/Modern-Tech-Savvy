// warnings.js
import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import WarningService from "/js/client/WarningService.js";

const requestClient = new RequestClient();
const warningService = new WarningService(requestClient);

// The warning form's stage dropdown and Bootstrap-validation wiring used
// to live in a @push('scripts') block inside _form.blade.php itself, but
// that partial is also rendered standalone (WarningController::edit(),
// see warningService.edit() below) outside of any layout that consumes
// @stack('scripts') - a pushed script never renders in that case, so an
// AJAX-loaded copy of the form got a stage dropdown stuck on "Loading
// stages…" forever. This runs the same logic from a normal script file
// instead, called both on initial page load and after every AJAX swap of
// #warningFormContainer.
window.initWarningForm = function () {
    fetch('/disciplinary-stage-types/fetch', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(payload => {
            const select = document.getElementById('stage_type_id');
            if (!select) return;
            const stages = (payload.data ?? []).filter(s => s.is_active);
            const currentStageId = select.dataset.currentStageId ? parseInt(select.dataset.currentStageId, 10) : null;
            select.innerHTML = '<option value="">— None —</option>' + stages.map(s =>
                `<option value="${s.id}" ${currentStageId === s.id ? 'selected' : ''}>${s.name}${s.is_terminal ? ' (Terminal)' : ''}</option>`
            ).join('');
        })
        .catch(() => {});

    document.querySelectorAll('.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.stopPropagation();
            if (form.checkValidity()) {
                saveWarning(form.querySelector('button[type="button"]'));
            }
            form.classList.add('was-validated');
        }, false);
    });
};

// Cases and Warnings are two independently paginated/viewable filtered
// views of the SAME warnings table (Cases = scope: 'cases', server-side
// filtered to stages flagged is_disciplinary_case; Warnings = every
// record) - each tab keeps its own view mode (grid/list) and page.
const warningListState = {
    cases: { scope: 'cases', view: 'grid', page: 1 },
    warnings: { scope: 'all', view: 'grid', page: 1 },
};

function warningListContainerId(key) {
    return key === 'cases' ? 'casesListContainer' : 'warningsListContainer';
}

function warningListPaginationHtml(key, currentPage, lastPage) {
    if (!lastPage || lastPage <= 1) return '';
    return `
        <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <button type="button" class="page-link" onclick="goToWarningListPage('${key}', ${currentPage - 1})">Prev</button>
        </li>
        <li class="page-item disabled"><span class="page-link">${currentPage} / ${lastPage}</span></li>
        <li class="page-item ${currentPage >= lastPage ? 'disabled' : ''}">
            <button type="button" class="page-link" onclick="goToWarningListPage('${key}', ${currentPage + 1})">Next</button>
        </li>`;
}

window.goToWarningListPage = function (key, page) {
    if (page < 1) return;
    warningListState[key].page = page;
    loadWarningList(key);
};

window.setWarningListView = function (key, view) {
    warningListState[key].view = view;
    warningListState[key].page = 1;
    document.querySelectorAll(`[data-list="${key}"][data-view]`).forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.view === view);
    });
    loadWarningList(key);
};

async function loadWarningList(key) {
    const containerId = warningListContainerId(key);
    const container = document.getElementById(containerId);
    const pagination = document.getElementById(`${key}ListPagination`);
    const state = warningListState[key];

    try {
        const response = await warningService.fetch({ scope: state.scope, view: state.view, page: state.page, per_page: 12 });
        container.className = state.view === 'list' ? 'warnings-list-container' : 'row g-4 warnings-list-container';
        container.innerHTML = response.html;
        if (pagination) pagination.innerHTML = warningListPaginationHtml(key, response.current_page, response.last_page);
        if (key === 'warnings') document.getElementById('warningCount').textContent = response.count + ' Warnings';
    } catch (error) {
        console.error(`Error loading ${key}:`, error);
        container.innerHTML = '<div class="col-12"><div class="text-center text-danger py-4">Could not load records.</div></div>';
    }
}

window.getWarnings = function () {
    loadWarningList('cases');
    loadWarningList('warnings');
};

window.newWarningRecord = async function () {
    try {
        $('#warningFormContainer').html(await warningService.edit({}));
        initWarningForm();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('warningFormModal')).show();
    } catch (error) {
        console.error("Error loading warning form:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load form.', 'error');
    }
};

window.saveWarning = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    let form = $('#warningForm');
    let formData = new FormData(document.getElementById("warningForm"));

    try {
        if (formData.has("warning_id")) {
            await warningService.update(formData);
            Swal.fire('Success!', 'Warning updated successfully.', 'success');
        } else {
            await warningService.save(formData);
            Swal.fire('Success!', 'Warning issued successfully.', 'success');
        }
        form[0].reset();
        $('#warningFormContainer').html(await warningService.edit({}));
        initWarningForm();
        bootstrap.Modal.getInstance(document.getElementById('warningFormModal'))?.hide();
        getWarnings();
    } catch (error) {
        console.error("Error saving warning:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to save warning.', 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.editWarning = async function (btn) {
    btn = $(btn);
    const warning = btn.data("warning");
    const data = { warning_id: warning };

    try {
        const form = await warningService.edit(data);
        $('#warningFormContainer').html(form);
        initWarningForm();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('warningFormModal')).show();
    } catch (error) {
        console.error("Error editing warning:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load edit form.', 'error');
    }
};

window.deleteWarning = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const warning = btn.data("warning");
    const data = { warning_id: warning };

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await warningService.delete(data);
                Swal.fire('Deleted!', 'Warning deleted successfully.', 'success');
                getWarnings();
            } catch (error) {
                console.error("Error deleting warning:", error);
                Swal.fire('Error!', error.response?.data?.message || 'Failed to delete warning.', 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    getWarnings();
    initWarningForm();
});