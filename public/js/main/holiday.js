import { btn_loader } from "../client/config.js";
import RequestClient from "../client/RequestClient.js";
import HolidayService from "../client/HolidayService.js";

const requestClient = new RequestClient();
const holidayService = new HolidayService(requestClient);

let currentYear = new Date().getFullYear();

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function openModal() {
  const modalEl = document.getElementById("addHolidayModal");
  if (!modalEl) {
    console.error("addHolidayModal not found in DOM.");
    Swal?.fire?.("Error", "Holiday modal missing on page.", "error");
    return null;
  }
  if (!window.bootstrap?.Modal) {
    console.error("Bootstrap Modal JS not available.");
    Swal?.fire?.("Error", "Bootstrap modal JS is not loaded.", "error");
    return null;
  }
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
  return modal;
}

function renderHolidayForm({ mode = "create", holiday = null } = {}) {
  const isEdit = mode === "edit" && holiday;
  const locations = window.businessLocations || [];

  return `
    <form id="holidayForm" method="post">
      <input type="hidden" name="_token" value="${csrfToken()}">
      ${isEdit ? `<input type="hidden" name="holiday_slug" value="${holiday.slug}">` : ""}

      <div class="form-group mb-3">
        <label for="name">Holiday Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" required
               placeholder="e.g., New Year's Day, Labor Day"
               value="${isEdit ? (holiday.name ?? "") : ""}">
      </div>

      <div class="form-group mb-3">
        <label for="date">Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="date" name="date" required
               value="${isEdit ? (holiday.date ?? "") : ""}">
      </div>

      ${locations.length ? `
      <div class="form-group mb-3">
        <label for="location_id">Location</label>
        <select class="form-select" id="location_id" name="location_id">
          <option value="">Business-wide (applies to everyone)</option>
          ${locations.map((l) => `<option value="${l.id}" ${isEdit && String(holiday.location_id) === String(l.id) ? "selected" : ""}>${l.name}</option>`).join("")}
        </select>
        <div class="form-text">Leave as business-wide unless this holiday only applies to one branch (e.g. a country-specific public holiday).</div>
      </div>` : ""}

      <div class="row mb-3">
        <div class="col-md-6">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring" value="1"
                   ${isEdit && holiday.is_recurring ? "checked" : ""}>
            <label class="form-check-label" for="is_recurring"><strong>Recurring Annually</strong></label>
            <div class="form-text">Check if this holiday repeats every year</div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_working_day" id="is_working_day" value="1"
                   ${isEdit && holiday.is_working_day ? "checked" : ""}>
            <label class="form-check-label" for="is_working_day"><strong>Counted as Working Day</strong></label>
            <div class="form-text">If checked, employees can work but get special overtime rates</div>
          </div>
        </div>
      </div>

      <div class="form-group mb-3">
        <label for="description">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3"
                  placeholder="Additional notes about this holiday">${isEdit ? (holiday.description ?? "") : ""}</textarea>
      </div>

      <div class="alert alert-info">
        <strong>Note:</strong>
        <ul class="mb-0">
          <li>If "Counted as Working Day" is checked, employees working on this day will get special overtime rates</li>
          <li>If unchecked, all hours worked will be counted as holiday overtime</li>
        </ul>
      </div>

      <div>
        <button type="button" onclick="saveHoliday(this)" class="btn btn-primary w-100">
          <i class="bi bi-check-circle"></i> ${isEdit ? "Update Holiday" : "Add Holiday"}
        </button>
      </div>
    </form>
  `;
}

window.getHolidays = async function (year = null) {
  try {
    if (year) currentYear = year;

    const locationId = document.getElementById("holidayLocationFilter")?.value || null;
    const holidaysTable = await holidayService.fetch({ year: currentYear, location_id: locationId });
    $("#holidaysContainer").html(holidaysTable);

    if (window.jQuery && $.fn.DataTable) {
      const table = $("#holidaysTable");
      if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy();
      }
      table.DataTable({ order: [[1, "asc"]] });
    }
  } catch (error) {
    console.error("Error loading holidays:", error);
    Swal.fire("Error!", error.response?.data?.message || "Failed to load holidays.", "error");
  }
};

window.changeYear = function (delta) {
  currentYear += delta;
  window.getHolidays(currentYear);
};

// ✅ FIXED: was "wwindow.addHoliday"
window.addHoliday = function () {
  const container = document.getElementById("holidayFormContainer");
  if (!container) {
    console.error("holidayFormContainer not found in DOM.");
    Swal?.fire?.("Error", "Holiday form container missing on page.", "error");
    return;
  }

  container.innerHTML = renderHolidayForm({ mode: "create" });

  // update modal title
  const title = document.getElementById("addHolidayModalLabel");
  if (title) title.textContent = "Add Holiday";

  openModal();
};

window.saveHoliday = async function (btn) {
  const $btn = window.$ ? $(btn) : null;
  if ($btn) btn_loader($btn, true);

  try {
    const formEl = document.getElementById("holidayForm");
    if (!formEl) throw new Error("holidayForm not found.");

    const formData = new FormData(formEl);

    const isEdit = formData.has("holiday_slug") && formData.get("holiday_slug");
    if (isEdit) {
      await holidayService.update(formData);
    } else {
      await holidayService.save(formData);
    }

    // hide modal
    const modalEl = document.getElementById("addHolidayModal");
    if (modalEl && window.bootstrap?.Modal) {
      bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    }

    await window.getHolidays();
  } catch (error) {
    console.error("Error saving holiday:", error);
    Swal.fire("Error!", error.response?.data?.message || error.message || "Failed to save holiday.", "error");
  } finally {
    if ($btn) btn_loader($btn, false);
  }
};

window.editHoliday = async function (btn) {
  const $btn = window.$ ? $(btn) : null;
  if ($btn) btn_loader($btn, true);

  try {
    const holidaySlug = $btn ? $btn.data("holiday") : btn.getAttribute("data-holiday");
    const htmlForm = await holidayService.edit({ holiday: holidaySlug });

    // Your controller returns a blade-rendered form; we inject it directly
    const container = document.getElementById("holidayFormContainer");
    if (!container) throw new Error("holidayFormContainer not found.");

    container.innerHTML = htmlForm;

    // update modal title
    const title = document.getElementById("addHolidayModalLabel");
    if (title) title.textContent = "Edit Holiday";

    openModal();
  } catch (error) {
    console.error("Error editing holiday:", error);
    Swal.fire("Error!", error.response?.data?.message || "Failed to load holiday form.", "error");
  } finally {
    if ($btn) btn_loader($btn, false);
  }
};

window.deleteHoliday = async function (btn) {
  const $btn = window.$ ? $(btn) : null;
  const holidaySlug = $btn ? $btn.data("holiday") : btn.getAttribute("data-holiday");

  Swal.fire({
    title: "Are you sure?",
    text: "This will remove the holiday from your calendar!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#068f6d",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (!result.isConfirmed) return;

    if ($btn) btn_loader($btn, true);
    try {
      await holidayService.delete({ holiday: holidaySlug });
      await window.getHolidays();
    } catch (error) {
      console.error("Error deleting holiday:", error);
      Swal.fire("Error!", error.response?.data?.message || "Failed to delete holiday.", "error");
    } finally {
      if ($btn) btn_loader($btn, false);
    }
  });
};

// Cached per location (including "no location"/business-wide) since the
// guessed country differs depending on which location is selected.
const countriesCache = {};

async function loadCountriesIntoSelect() {
  const select = document.getElementById("importCountrySelect");
  if (!select) return;

  const locationId = document.getElementById("importLocationSelect")?.value || "";
  const cacheKey = locationId || "__business__";

  if (countriesCache[cacheKey]) {
    renderCountryOptions(select, countriesCache[cacheKey]);
    return;
  }

  try {
    const data = await holidayService.availableCountries(locationId || null);
    countriesCache[cacheKey] = data;
    renderCountryOptions(select, data);
  } catch (error) {
    console.error("Error loading countries:", error);
    select.innerHTML = '<option value="">Could not load countries</option>';
  }
}

function renderCountryOptions(select, data) {
  const countries = data.countries || [];
  const guessed = data.guessed_country_code || null;

  select.innerHTML = '<option value="">Select a country</option>'
    + countries
        .map((c) => `<option value="${c.countryCode}" ${c.countryCode === guessed ? "selected" : ""}>${c.name}</option>`)
        .join("");
}

document.addEventListener("DOMContentLoaded", function () {
  const modalEl = document.getElementById("importHolidaysModal");
  modalEl?.addEventListener("show.bs.modal", loadCountriesIntoSelect);

  document.getElementById("importLocationSelect")?.addEventListener("change", loadCountriesIntoSelect);

  document.getElementById("holidayLocationFilter")?.addEventListener("change", function () {
    window.getHolidays();
  });

  document.getElementById("submitImportHolidaysBtn")?.addEventListener("click", async function () {
    const form = document.getElementById("importHolidaysForm");
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const $btn = window.$ ? $(this) : null;
    if ($btn) btn_loader($btn, true);

    try {
      const formData = new FormData(form);
      await holidayService.importFromApi({
        country_code: formData.get("country_code"),
        year: formData.get("year"),
        location_id: formData.get("location_id") || null,
      });

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.hide();
      await window.getHolidays();
    } catch (error) {
      console.error("Error importing holidays:", error);
      Swal.fire("Error!", error.response?.data?.message || error.message || "Failed to load holidays.", "error");
    } finally {
      if ($btn) btn_loader($btn, false);
    }
  });
});

console.log("✅ holiday.js loaded");
