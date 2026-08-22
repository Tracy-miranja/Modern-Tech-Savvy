import { btn_loader } from "../client/config.js";
import RequestClient from "../client/RequestClient.js";
import MandatoryLeavePeriodService from "../client/MandatoryLeavePeriodService.js";

const requestClient = new RequestClient();
const mandatoryLeaveService = new MandatoryLeavePeriodService(requestClient);

function openModal() {
    const modalEl = document.getElementById("mandatoryLeavePeriodModal");
    if (!modalEl || !window.bootstrap?.Modal) return null;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    return modal;
}

// Toggles the department/location picker to match the selected scope_type
// radio. `root` must be the actual container the form was injected into.
window.initMandatoryLeaveScopeUI = function (root) {
    root = root || document;
    const radios = root.querySelectorAll('input[name="scope_type"]');
    const deptPicker = root.querySelector('#scope_department_picker');
    const locPicker = root.querySelector('#scope_location_picker');
    if (!radios.length || !deptPicker || !locPicker) return;

    function sync() {
        const type = root.querySelector('input[name="scope_type"]:checked')?.value;
        deptPicker.classList.toggle('d-none', type !== 'department');
        locPicker.classList.toggle('d-none', type !== 'location');
    }

    radios.forEach(r => r.addEventListener('change', sync));
    sync();
};

window.getMandatoryLeavePeriods = async function () {
    try {
        const table = await mandatoryLeaveService.fetch();
        document.getElementById('mandatoryLeavePeriodsContainer').innerHTML = table;
    } catch (error) {
        console.error('Error loading company-mandated leave days:', error);
    }
};

window.addMandatoryLeavePeriod = async function () {
    try {
        const form = await mandatoryLeaveService.create();
        const body = document.getElementById('mandatoryLeavePeriodModalBody');
        body.innerHTML = form;
        document.getElementById('mandatoryLeavePeriodModalLabel').textContent = 'Add Company-Mandated Leave Days';
        window.initMandatoryLeaveScopeUI(body);
        openModal();
    } catch (error) {
        console.error('Error loading form:', error);
        Swal.fire('Error', 'Failed to load form.', 'error');
    }
};

window.editMandatoryLeavePeriod = async function (btn) {
    const $btn = window.$ ? $(btn) : null;
    const slug = $btn ? $btn.data('period') : btn.getAttribute('data-period');

    try {
        const form = await mandatoryLeaveService.edit({ mandatory_leave_period: slug });
        const body = document.getElementById('mandatoryLeavePeriodModalBody');
        body.innerHTML = form;
        document.getElementById('mandatoryLeavePeriodModalLabel').textContent = 'Edit Company-Mandated Leave Days';
        window.initMandatoryLeaveScopeUI(body);
        openModal();
    } catch (error) {
        console.error('Error loading form:', error);
        Swal.fire('Error', 'Failed to load form.', 'error');
    }
};

window.saveMandatoryLeavePeriod = async function (btn) {
    const $btn = window.$ ? $(btn) : null;
    if ($btn) btn_loader($btn, true);

    try {
        const form = document.getElementById('mandatoryLeavePeriodForm');
        if (!form) throw new Error('mandatoryLeavePeriodForm not found.');

        const formData = new FormData(form);
        const scopeType = formData.get('scope_type');

        // Both pickers exist in the DOM (one hidden) - rebuild scope_ids[]
        // fresh from whichever one is actually active, and drop the raw
        // per-picker keys so the server only ever sees one clean scope_ids[].
        const deptIds = formData.getAll('scope_ids_department[]');
        const locIds = formData.getAll('scope_ids_location[]');
        formData.delete('scope_ids_department[]');
        formData.delete('scope_ids_location[]');

        if (scopeType === 'department') {
            deptIds.forEach(id => formData.append('scope_ids[]', id));
        } else if (scopeType === 'location') {
            locIds.forEach(id => formData.append('scope_ids[]', id));
        }

        const isUpdate = formData.has('mandatory_leave_period_slug') && String(formData.get('mandatory_leave_period_slug')).trim().length > 0;
        const response = isUpdate ? await mandatoryLeaveService.update(formData) : await mandatoryLeaveService.store(formData);

        await window.getMandatoryLeavePeriods();

        const modalEl = document.getElementById('mandatoryLeavePeriodModal');
        if (modalEl && window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }

        Swal.fire('Success', response?.message || 'Saved successfully.', 'success');
    } catch (error) {
        console.error('Error saving company-mandated leave days:', error);
        Swal.fire('Error', error?.message || 'Failed to save.', 'error');
    } finally {
        if ($btn) btn_loader($btn, false);
    }
};

window.deleteMandatoryLeavePeriod = function (btn) {
    const $btn = window.$ ? $(btn) : null;
    const slug = $btn ? $btn.data('period') : btn.getAttribute('data-period');

    Swal.fire({
        title: 'Are you sure?',
        text: 'This will restore the deducted days back to every affected employee\'s balance.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#068f6d',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it!',
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        if ($btn) btn_loader($btn, true);
        try {
            await mandatoryLeaveService.delete({ mandatory_leave_period: slug });
            await window.getMandatoryLeavePeriods();
            Swal.fire('Removed', 'Company-mandated leave days removed and balances restored.', 'success');
        } catch (error) {
            console.error('Error deleting company-mandated leave days:', error);
            Swal.fire('Error', 'Failed to delete.', 'error');
        } finally {
            if ($btn) btn_loader($btn, false);
        }
    });
};

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('mandatoryLeavePeriodsContainer')) {
        window.getMandatoryLeavePeriods();
    }
});
