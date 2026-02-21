import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import WorkScheduleService from "/js/client/WorkScheduleService.js";

const requestClient = new RequestClient();
const workScheduleService = new WorkScheduleService(requestClient);

function openModal() {
    const modalEl = document.getElementById("addWorkScheduleModal");
    if (!modalEl) {
        Swal.fire("Error!", "Work schedule modal missing on page.", "error");
        return null;
    }

    if (window.bootstrap?.Modal) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        return modal;
    }

    if (window.$ && typeof $(modalEl).modal === "function") {
        $(modalEl).modal("show");
        return true;
    }

    Swal.fire("Error!", "Bootstrap modal JS is not loaded.", "error");
    return null;
}

window.getWorkSchedules = async function () {
    try {
        const employeeId = document.getElementById("filterEmployee")?.value || "";
        const shiftId = document.getElementById("filterShift")?.value || "";

        const schedulesTable = await workScheduleService.fetch({
            employee_id: employeeId || null,
            shift_id: shiftId || null,
        });

        $("#workSchedulesContainer").html(schedulesTable);

        if (window.jQuery && $.fn.DataTable) {
            const table = $("#workSchedulesTable");
            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy();
            }
            table.DataTable();
        }
    } catch (error) {
        console.error("Error loading work schedules:", error);
        Swal.fire("Error!", error.response?.data?.message || "Failed to load work schedules.", "error");
    }
};

window.loadShiftTab = function (shiftId, btn) {
    // highlight active tab
    document.querySelectorAll("#shiftTabs .nav-link").forEach(b => b.classList.remove("active"));
    if (btn) btn.classList.add("active");

    // set the shift filter dropdown too (optional syncing)
    const shiftSelect = document.getElementById("filterShift");
    if (shiftSelect) {
        shiftSelect.value = shiftId === "all" ? "" : shiftId;
    }

    // fetch table
    window.getWorkSchedules();
};


window.addWorkSchedule = async function () {
    $("#workScheduleFormContainer").html('<div class="text-center p-4">Loading...</div>');
    openModal();

    try {
        const formHtml = await workScheduleService.createForm();
        $("#workScheduleFormContainer").html(formHtml);
    } catch (error) {
        console.error("Error loading create form:", error);
        Swal.fire("Error!", error.response?.data?.message || "Failed to load form.", "error");
    }
};


window.saveWorkSchedule = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const formEl = document.getElementById("workScheduleForm");
    const formData = new FormData(formEl);

    try {
        if (formData.has("schedule_id") && formData.get("schedule_id")) {
            await workScheduleService.update(formData);
        } else {
            await workScheduleService.save(formData);
        }

        // Hide modal
        const modalEl = document.getElementById("addWorkScheduleModal");
        if (modalEl && window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        } else {
            $("#addWorkScheduleModal").modal("hide");
        }

        await window.getWorkSchedules();
    } catch (error) {
        console.error("Error saving work schedule:", error);
        Swal.fire("Error!", error.response?.data?.message || "Failed to save work schedule.", "error");
    } finally {
        btn_loader(btn, false);
    }
};

window.editWorkSchedule = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const scheduleId = btn.data("schedule");

    try {
        const formHtml = await workScheduleService.edit({ schedule: scheduleId });
        $("#workScheduleFormContainer").html(formHtml);
        openModal();
    } catch (error) {
        console.error("Error editing work schedule:", error);
        Swal.fire("Error!", error.response?.data?.message || "Failed to load work schedule form.", "error");
    } finally {
        btn_loader(btn, false);
    }
};

window.deleteWorkSchedule = async function (btn) {
    btn = $(btn);

    const scheduleId = btn.data("schedule");

    Swal.fire({
        title: "Are you sure?",
        text: "This will remove the work schedule assignment!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        btn_loader(btn, true);
        try {
            await workScheduleService.delete({ schedule: scheduleId });
            await window.getWorkSchedules();
        } catch (error) {
            console.error("Error deleting work schedule:", error);
            Swal.fire("Error!", error.response?.data?.message || "Failed to delete work schedule.", "error");
        } finally {
            btn_loader(btn, false);
        }
    });
};

// Helper function to check employee schedule for a specific date
window.checkEmployeeSchedule = async function (employeeId, date) {
    try {
        const scheduleInfo = await workScheduleService.getScheduleInfo({ employee_id: employeeId, date: date });
        return scheduleInfo;
    } catch (error) {
        console.error("Error checking employee schedule:", error);
        return null;
    }
};

window.activateWorkSchedule = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const scheduleId = btn.data("schedule");

    try {
        await workScheduleService.activate({ schedule: scheduleId });
        toastr.success("Schedule activated");
        getWorkSchedules(); // reload table
    } catch (error) {
        console.error(error);
        Swal.fire("Error!", error.response?.data?.message || "Failed to activate schedule.", "error");
    } finally {
        btn_loader(btn, false);
    }
};

window.openBulkAssignModal = function () {
    // render pickers fresh each time
    $("#shiftPicker").html(renderMultiPicker({
        id: "shiftMulti",
        title: "Select shifts",
        items: window.allShifts || [],
        itemLabel: (x) => x.name,
        itemValue: (x) => x.id,
        showFilters: false
    }));

    $("#employeePicker").html(renderMultiPicker({
        id: "employeeMulti",
        title: "Select employees",
        items: window.allEmployees || [],
        itemLabel: (x) => `${x.name} (${x.department || 'N/A'})`,
        itemValue: (x) => x.id,
        showFilters: true,
        filters: [
            {
                key: "department_id",
                label: "Department",
                options: [{id:"", name:"All Departments"}].concat(window.allDepartments || []),
            }
        ]
    }));

    // show modal
    const modalEl = document.getElementById("bulkAssignModal");
    if (window.bootstrap?.Modal) bootstrap.Modal.getOrCreateInstance(modalEl).show();
    else $("#bulkAssignModal").modal("show");
};


window.addBulkBlock = function () {
    const idx = document.querySelectorAll(".bulk-block").length;

    const shiftsOptions = (window.allShifts || [])
        .map(s => `<option value="${s.id}">${s.name}</option>`)
        .join("");

    const employeesOptions = (window.allEmployees || [])
        .map(e => `<option value="${e.id}">${e.name}</option>`)
        .join("");

    const days = [
        {v:0,l:"Sun"},{v:1,l:"Mon"},{v:2,l:"Tue"},{v:3,l:"Wed"},
        {v:4,l:"Thu"},{v:5,l:"Fri"},{v:6,l:"Sat"}
    ];

    const daysHtml = days.map(d => `
        <label class="me-2">
            <input type="checkbox" class="bulk-days" data-block="${idx}" value="${d.v}" ${[1,2,3,4,5].includes(d.v) ? 'checked' : ''}>
            ${d.l}
        </label>
    `).join("");

    $("#bulkAssignBlocks").append(`
      <div class="bulk-block border rounded p-3 mb-3" data-block="${idx}">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Shift Block #${idx + 1}</strong>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBulkBlock(${idx})">Remove</button>
        </div>

        <div class="mb-2">
          <label class="form-label">Shift</label>
          <select multiple class="form-control bulk-shift" data-block="${idx}" style="height:120px">
            <option value="">Select Shift</option>
            ${shiftsOptions}
          </select>
          <small class="text-muted">This shift will be assigned to all selected employees.</small>
        </div>

        <div class="mb-2">
          <div class="d-flex justify-content-between align-items-center">
            <label class="form-label mb-0">Employees</label>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllEmployees(${idx})">Select All</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearEmployees(${idx})">Clear</button>
            </div>
          </div>
          <select multiple class="form-control bulk-employees" data-block="${idx}" style="height:160px">
            ${employeesOptions}
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label">Working Days</label><br/>
          ${daysHtml}
        </div>

        <div class="row">
          <div class="col-md-6 mb-2">
            <label class="form-label">Effective From</label>
            <input type="date" class="form-control bulk-from" data-block="${idx}">
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Effective To (optional)</label>
            <input type="date" class="form-control bulk-to" data-block="${idx}">
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label">Notes</label>
          <textarea class="form-control bulk-notes" data-block="${idx}" rows="2"></textarea>
        </div>
      </div>
    `);
};

window.removeBulkBlock = function (idx) {
    $(`.bulk-block[data-block="${idx}"]`).remove();
};

window.selectAllEmployees = function (idx) {
    const select = document.querySelector(`.bulk-employees[data-block="${idx}"]`);
    if (!select) return;
    Array.from(select.options).forEach(o => o.selected = true);
};

window.clearEmployees = function (idx) {
    const select = document.querySelector(`.bulk-employees[data-block="${idx}"]`);
    if (!select) return;
    Array.from(select.options).forEach(o => o.selected = false);
};

window.submitBulkAssign = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    try {
        const shift_ids = Array.from(document.querySelectorAll(".shiftMulti_chk:checked"))
            .map(x => parseInt(x.value, 10));

        const employee_ids = Array.from(document.querySelectorAll(".employeeMulti_chk:checked"))
            .map(x => parseInt(x.value, 10));

        const working_days = Array.from(document.querySelectorAll(".bulk-days:checked"))
            .map(x => parseInt(x.value, 10));

        const effective_from = document.getElementById("bulkEffectiveFrom")?.value;
        const effective_to = document.getElementById("bulkEffectiveTo")?.value || null;
        const notes = document.getElementById("bulkNotes")?.value || null;

        if (!shift_ids.length) throw new Error("Please select at least 1 shift.");
        if (!employee_ids.length) throw new Error("Please select at least 1 employee.");
        if (!working_days.length) throw new Error("Please select at least 1 working day.");
        if (!effective_from) throw new Error("Effective From is required.");

        await workScheduleService.bulkStore({
            shift_ids,
            employee_ids,
            working_days,
            effective_from,
            effective_to,
            notes,
        });

        const modalEl = document.getElementById("bulkAssignModal");
        if (window.bootstrap?.Modal) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        else $("#bulkAssignModal").modal("hide");

        await window.getWorkSchedules();

    } catch (error) {
        console.error(error);
        Swal.fire("Error!", error.response?.data?.message || error.message || "Bulk assign failed.", "error");
    } finally {
        btn_loader(btn, false);
    }
};

window.applyWorkScheduleFilters = function () {
    window.getWorkSchedules();
};

window.resetWorkScheduleFilters = function () {
    const emp = document.getElementById("filterEmployee");
    const shift = document.getElementById("filterShift");
    if (emp) emp.value = "";
    if (shift) shift.value = "";
    window.getWorkSchedules();
};

document.addEventListener("change", function (e) {
    if (e.target && (e.target.id === "filterEmployee" || e.target.id === "filterShift")) {
        window.getWorkSchedules();
    }
});

function renderMultiPicker({ id, title, items, itemLabel, itemValue, showFilters = false, filters = [] }) {
    return `
    <div class="multi-picker" data-picker="${id}">
      <button type="button" class="btn btn-outline-primary w-100 d-flex justify-content-between align-items-center"
              onclick="togglePicker('${id}')">
        <span id="${id}_label">${title}</span>
        <i class="bi bi-chevron-down"></i>
      </button>

      <div class="border rounded p-2 mt-2 d-none" id="${id}_panel" style="background:#fff">
        <div class="d-flex gap-2 mb-2">
          <input type="text" class="form-control" placeholder="Search..."
                 oninput="filterPickerList('${id}', this.value)">
          <button class="btn btn-sm btn-outline-secondary" type="button" onclick="pickerSelectAll('${id}')">Select All</button>
          <button class="btn btn-sm btn-outline-secondary" type="button" onclick="pickerClear('${id}')">Clear</button>
        </div>

        ${showFilters ? renderPickerFilters(id, filters) : ''}

        <div class="picker-list" id="${id}_list" style="max-height:260px; overflow:auto;">
          ${items.map(i => `
            <label class="d-flex align-items-center gap-2 py-1 picker-item"
                   data-text="${escapeHtml(itemLabel(i)).toLowerCase()}"
                   ${filters.map(f => `data-${f.key}="${i[f.key] ?? ''}"`).join(' ')}>
              <input type="checkbox" class="${id}_chk" value="${itemValue(i)}" onchange="updatePickerLabel('${id}', '${title}')">
              <span>${escapeHtml(itemLabel(i))}</span>
            </label>
          `).join("")}
        </div>
      </div>
    </div>
    `;
}

function renderPickerFilters(id, filters) {
    return `
      <div class="row g-2 mb-2">
        ${filters.map(f => `
          <div class="col-md-6">
            <label class="form-label mb-1">${f.label}</label>
            <select class="form-control" onchange="applyPickerFilters('${id}')"
                    data-filter-key="${f.key}">
              ${(f.options || []).map(opt => `<option value="${opt.id}">${escapeHtml(opt.name)}</option>`).join("")}
            </select>
          </div>
        `).join("")}
      </div>
    `;
}

window.togglePicker = function (id) {
    const panel = document.getElementById(`${id}_panel`);
    if (!panel) return;
    panel.classList.toggle("d-none");
};

window.filterPickerList = function (id, q) {
    q = (q || "").toLowerCase().trim();
    document.querySelectorAll(`#${id}_list .picker-item`).forEach(el => {
        const text = el.getAttribute("data-text") || "";
        el.style.display = text.includes(q) ? "" : "none";
    });
};

window.applyPickerFilters = function (id) {
    // read selected filter values
    const panel = document.getElementById(`${id}_panel`);
    const selects = panel.querySelectorAll("select[data-filter-key]");
    const rules = {};
    selects.forEach(s => rules[s.getAttribute("data-filter-key")] = s.value);

    document.querySelectorAll(`#${id}_list .picker-item`).forEach(el => {
        let ok = true;
        Object.keys(rules).forEach(k => {
            const want = rules[k];
            if (want === "") return; // all
            const have = el.getAttribute(`data-${k}`) ?? "";
            if (String(have) !== String(want)) ok = false;
        });
        el.style.display = ok ? "" : "none";
    });
};

window.pickerSelectAll = function (id) {
    document.querySelectorAll(`.${id}_chk`).forEach(chk => {
        // only select visible ones
        const row = chk.closest(".picker-item");
        if (row && row.style.display !== "none") chk.checked = true;
    });
    updatePickerLabel(id);
};

window.pickerClear = function (id) {
    document.querySelectorAll(`.${id}_chk`).forEach(chk => chk.checked = false);
    updatePickerLabel(id);
};

window.updatePickerLabel = function (id, fallback = "Selected") {
    const checked = Array.from(document.querySelectorAll(`.${id}_chk:checked`));
    const label = document.getElementById(`${id}_label`);
    if (!label) return;

    if (checked.length === 0) label.textContent = fallback;
    else label.textContent = `${checked.length} selected`;
};

function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
}

console.log(" work-schedule.js loaded");
