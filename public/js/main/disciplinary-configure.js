// disciplinary-configure.js - "Configure" modal-CRUD table for
// business-configurable disciplinary stages (see GUIDE plan Phase 3).
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const fetchUrl = '/disciplinary-stage-types/fetch';
    const storeUrl = '/disciplinary-stage-types/store';
    const updateUrlTemplate = '/disciplinary-stage-types/__ID__/update';
    const destroyUrlTemplate = '/disciplinary-stage-types/__ID__/destroy';
    const reorderUrl = '/disciplinary-stage-types/reorder';

    let cachedStages = [];

    async function postJson(url, data, method = 'POST') {
        const resp = await fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(data || {}),
        });
        const payload = await resp.json();
        if (!resp.ok) throw new Error(payload.message || 'Request failed.');
        return payload;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[ch]));
    }

    function renderRow(stage, index, total) {
        return `
            <tr data-id="${stage.id}">
                <td>
                    <button type="button" class="btn btn-sm btn-link p-0 move-up-btn" ${index === 0 ? 'disabled' : ''} title="Move up"><i class="fa fa-arrow-up"></i></button>
                    <button type="button" class="btn btn-sm btn-link p-0 move-down-btn" ${index === total - 1 ? 'disabled' : ''} title="Move down"><i class="fa fa-arrow-down"></i></button>
                </td>
                <td>${escapeHtml(stage.name)}</td>
                <td class="text-center"><input type="checkbox" class="terminal-check" ${stage.is_terminal ? 'checked' : ''}></td>
                <td class="text-center"><input type="checkbox" class="response-check" ${stage.requires_response ? 'checked' : ''}></td>
                <td class="text-center"><input type="checkbox" class="case-check" ${stage.is_disciplinary_case ? 'checked' : ''}></td>
                <td class="text-center">${stage.warnings_count ?? 0}</td>
                <td class="text-center"><input type="checkbox" class="active-check" ${stage.is_active ? 'checked' : ''}></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-stage-btn" title="Delete (only if no cases on record)">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>`;
    }

    async function loadStages() {
        const tbody = document.getElementById('stageTypesTableBody');
        try {
            const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            cachedStages = payload.data ?? [];

            if (!cachedStages.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No stages configured.</td></tr>';
                return;
            }

            tbody.innerHTML = cachedStages.map((s, i) => renderRow(s, i, cachedStages.length)).join('');
            bindRowHandlers();
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Could not load stages.</td></tr>';
        }
    }

    function bindRowHandlers() {
        document.querySelectorAll('#stageTypesTableBody tr').forEach(row => {
            const id = row.dataset.id;

            row.querySelector('.terminal-check').addEventListener('change', function () {
                updateStage(id, { is_terminal: this.checked }, this);
            });
            row.querySelector('.response-check').addEventListener('change', function () {
                updateStage(id, { requires_response: this.checked }, this);
            });
            row.querySelector('.case-check').addEventListener('change', function () {
                updateStage(id, { is_disciplinary_case: this.checked }, this);
            });
            row.querySelector('.active-check').addEventListener('change', function () {
                updateStage(id, { is_active: this.checked }, this);
            });
            row.querySelector('.delete-stage-btn').addEventListener('click', function () {
                deleteStage(id);
            });
            const upBtn = row.querySelector('.move-up-btn');
            const downBtn = row.querySelector('.move-down-btn');
            if (upBtn) upBtn.addEventListener('click', () => moveStage(id, -1));
            if (downBtn) downBtn.addEventListener('click', () => moveStage(id, 1));
        });
    }

    async function updateStage(id, fields, checkboxEl) {
        try {
            await postJson(updateUrlTemplate.replace('__ID__', id), fields);
            if (window.toastr) toastr.success('Stage updated.');
        } catch (e) {
            if (checkboxEl) checkboxEl.checked = !checkboxEl.checked;
            if (window.toastr) toastr.error(e.message || 'Could not update stage.');
        }
    }

    async function deleteStage(id) {
        const { isConfirmed } = await Swal.fire({
            title: 'Are you sure?',
            text: 'Delete this stage? This only works if no cases reference it - otherwise disable it instead.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes',
        });
        if (!isConfirmed) return;
        try {
            await postJson(destroyUrlTemplate.replace('__ID__', id), {}, 'DELETE');
            if (window.toastr) toastr.success('Stage deleted.');
            loadStages();
        } catch (e) {
            if (window.toastr) toastr.error(e.message || 'Could not delete stage.');
        }
    }

    async function moveStage(id, direction) {
        const ids = cachedStages.map(s => s.id);
        const index = ids.indexOf(parseInt(id, 10));
        const swapWith = index + direction;
        if (swapWith < 0 || swapWith >= ids.length) return;

        [ids[index], ids[swapWith]] = [ids[swapWith], ids[index]];

        try {
            await postJson(reorderUrl, { ordered_ids: ids });
            loadStages();
        } catch (e) {
            if (window.toastr) toastr.error(e.message || 'Could not reorder stages.');
        }
    }

    document.getElementById('addStageForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const name = document.getElementById('newStageName').value.trim();
        if (!name) return;

        try {
            await postJson(storeUrl, {
                name,
                is_terminal: document.getElementById('newStageTerminal').checked,
                requires_response: document.getElementById('newStageRequiresResponse').checked,
                is_disciplinary_case: document.getElementById('newStageIsDisciplinaryCase').checked,
            });
            if (window.toastr) toastr.success('Stage added.');
            this.reset();
            loadStages();
        } catch (e) {
            if (window.toastr) toastr.error(e.message || 'Could not add stage.');
        }
    });

    document.getElementById('tab-configure-btn').addEventListener('shown.bs.tab', function () {
        loadStages();
    });
})();
