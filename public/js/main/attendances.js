// attendances.js
import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import AttendancesService from "/js/client/AttendancesService.js";

const requestClient = new RequestClient();
const attendancesService = new AttendancesService(requestClient);


function flag(name, def = 0) {
    const el = document.querySelector(`meta[name="${name}"]`);
    return el ? parseInt(el.content || "0", 10) : def;
}
const ENFORCE_GEOFENCE = flag("enforce_geofence", 0) === 1;
const ENFORCE_MAC      = flag("enforce_mac", 0) === 1;


function showMacInputsIfNeeded() {
    if (!ENFORCE_MAC) return;
    const w1 = document.getElementById("clockin_mac_wrapper");
    const w2 = document.getElementById("clockout_mac_wrapper");
    if (w1) w1.classList.remove("d-none");
    if (w2) w2.classList.remove("d-none");

    // Prefill from localStorage
    const saved = localStorage.getItem("device_mac") || "";
    const i1 = document.getElementById("clockin_device_mac_input");
    const i2 = document.getElementById("clockout_device_mac_input");
    if (i1 && !i1.value) i1.value = saved;
    if (i2 && !i2.value) i2.value = saved;
}
showMacInputsIfNeeded();

function normMac(mac) {
    return (mac || "").trim();
}

async function getPositionOnce() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) return reject(new Error("Geolocation not supported"));
        navigator.geolocation.getCurrentPosition(
            pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            err => reject(err),
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    });
}


async function attachPunchMeta(payload, { isFormData = true, isClockIn = true } = {}) {
    const setField = (k, v) => {
        if (isFormData) payload.set(k, v);
        else payload[k] = v;
    };


    if (ENFORCE_MAC) {
        const inputId = isClockIn ? "clockin_device_mac_input" : "clockout_device_mac_input";
        const typed = document.getElementById(inputId)?.value || "";
        const mac = normMac(typed || localStorage.getItem("device_mac") || "");
        if (!mac) throw new Error("Device MAC is required by your company policy.");
        localStorage.setItem("device_mac", mac);
        setField("device_mac", mac);
    } else {

        const inputId = isClockIn ? "clockin_device_mac_input" : "clockout_device_mac_input";
        const typed = document.getElementById(inputId)?.value || "";
        if (typed) setField("device_mac", normMac(typed));
    }


    if (ENFORCE_GEOFENCE) {
        try {
            const { lat, lng } = await getPositionOnce();
            setField("latitude", lat);
            setField("longitude", lng);


            const latEl = document.getElementById(isClockIn ? "clockin_latitude"  : "clockout_latitude");
            const lngEl = document.getElementById(isClockIn ? "clockin_longitude" : "clockout_longitude");
            if (latEl) latEl.value = lat;
            if (lngEl) lngEl.value = lng;
        } catch (e) {
            throw new Error("Location permission is required by your company policy.");
        }
    } else {

        try {
            const { lat, lng } = await getPositionOnce();
            setField("latitude", lat);
            setField("longitude", lng);
        } catch (_) { /* ignore if not enforced */ }
    }

    return payload;
}

window.getAttendances = async function (date = null) {
    try {
        const data = { date: date };
        const attendances = await attendancesService.fetch(data);
        $("#attendancesContainer").html(attendances);
        if (typeof DataTable !== "undefined") {
            new DataTable('#attendancesTable');
        }
    } catch (error) {
        console.error("Error loading attendances:", error);
        $("#attendancesContainer").html('<div class="alert alert-danger">Failed to load attendances.</div>');
    }
};

window.getMonthly = async function (month = null) {
    try {
        const data = { month: month };
        const attendances = await attendancesService.monthly(data);
        $("#attendancesContainer").html(attendances);
        if (typeof DataTable !== "undefined") {
            new DataTable('#attendancesTable');
        }
    } catch (error) {
        console.error("Error loading monthly attendances:", error);
        $("#attendancesContainer").html('<div class="alert alert-danger">Failed to load monthly attendance.</div>');
    }
};

window.getOvertime = async function (date = null) {
    try {
        const data = { date: date };
        const overtime = await attendancesService.overtime(data);
        $("#overtimeContainer").html(overtime);
        if (typeof DataTable !== "undefined") {
            new DataTable('#overtimeTable');
        }
    } catch (error) {
        console.error("Error loading overtime:", error);
        $("#overtimeContainer").html('<div class="alert alert-danger">Failed to load overtime.</div>');
    }
};

window.getClockins = async function () {
    try {
        const clockins = await attendancesService.clockins({});
        $("#clockinsContainer").html(clockins);
    } catch (error) {
        console.error("Error loading clock-ins:", error);
        $("#clockinsContainer").html('<div class="alert alert-danger">Failed to load clock-ins.</div>');
    }
};


window.clockIn = async function (btn) {
    const $btn = $(btn);
    btn_loader($btn, true);

    let formData;
    if ($("#clockInForm").length) {
        formData = new FormData(document.getElementById("clockInForm"));
        formData.delete('clock_in'); // keep original behavior
    } else {
        // Self-punch on employee page
        const employee = $btn.data('employee');
        formData = new FormData();
        formData.append('employee_id', employee);
    }

    try {
        await attachPunchMeta(formData, { isFormData: true, isClockIn: true });
        await attendancesService.clockIn(formData);
        Swal.fire('Success', 'Clock-in successful.', 'success');
    } catch (error) {
        console.error("Clock-in error:", error);
        Swal.fire('Error', error.message || 'Clock-in failed.', 'error');
    } finally {
        getClockins();
        btn_loader($btn, false);
    }
};

window.clockOut = async function (btn) {
    const $btn = $(btn);
    btn_loader($btn, true);


    let payload = {};

    if ($("#clockOutForm").length) {
        const form = document.getElementById("clockOutForm");
        const selectedEmployee = form.querySelector('#employee_id')?.value || '';
        if (!selectedEmployee) {
            btn_loader($btn, false);
            return Swal.fire('Error', 'Select an employee.', 'error');
        }
        payload.employee = selectedEmployee;
        payload.remarks  = form.querySelector('#remarks')?.value || '';
    } else {
        payload.employee = $btn.data('employee');
    }

    try {
        await attachPunchMeta(payload, { isFormData: false, isClockIn: false });
        await attendancesService.clockOut(payload); // stays plain object
        Swal.fire('Success', 'Clock-out recorded.', 'success');
    } catch (error) {
        console.error("Clock-out error:", error);
        Swal.fire('Error', error.message || 'Clock-out failed.', 'error');
    } finally {
        getClockins();
        btn_loader($btn, false);
    }
};

function showModal(id) {
  const modalEl = document.getElementById(id);
  if (!modalEl) {
    Swal.fire("Error!", `Modal #${id} is missing on page.`, "error");
    return null;
  }

  // Bootstrap 5 modal
  if (window.bootstrap?.Modal) {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    return modal;
  }

  // Bootstrap 4 / jQuery modal fallback
  if (window.$ && typeof $(modalEl).modal === "function") {
    $(modalEl).modal("show");
    return true;
  }

  Swal.fire("Error!", "Bootstrap modal JS is not loaded.", "error");
  return null;
}

function hideModal(id) {
  const modalEl = document.getElementById(id);
  if (!modalEl) return;

  if (window.bootstrap?.Modal) {
    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    return;
  }

  if (window.$ && typeof $(modalEl).modal === "function") {
    $(modalEl).modal("hide");
  }
}

window.viewAttendanceDetails = async function (btn) {
  const id = $(btn).data("attendance");

  $("#viewAttendanceContainer").html('<div class="text-center p-4">Loading...</div>');
  showModal("viewAttendanceModal");

  try {
    const html = await attendancesService.view({ attendance: id });
    $("#viewAttendanceContainer").html(html);
  } catch (e) {
    console.error(e);
    $("#viewAttendanceContainer").html('<div class="alert alert-danger">Failed to load details.</div>');
  }
};

window.editAttendance = async function (btn) {
  const id = $(btn).data("attendance");

  $("#editAttendanceContainer").html('<div class="text-center p-4">Loading...</div>');
  showModal("editAttendanceModal");

  try {
    const html = await attendancesService.edit({ attendance: id });
    $("#editAttendanceContainer").html(html);
  } catch (e) {
    console.error(e);
    $("#editAttendanceContainer").html('<div class="alert alert-danger">Failed to load edit form.</div>');
  }
};

window.submitAttendanceUpdate = async function (btn) {
  const $btn = $(btn);
  btn_loader($btn, true);

  try {
    const form = document.getElementById("attendanceEditForm");
    const fd = new FormData(form);

    // Checkbox fix: if unchecked it won't exist, keep it explicit for backend
    if (!form.querySelector('input[name="is_absent"]').checked) {
      fd.set("is_absent", "0");
    }

    await attendancesService.update(fd);
    hideModal("editAttendanceModal");

    // refresh current selected date
    const date = $("#date").val();
    await window.getAttendances(date);
  } catch (e) {
    console.error(e);
    Swal.fire("Error", e.response?.data?.message || e.message || "Update failed", "error");
  } finally {
    btn_loader($btn, false);
  }
};

window.deleteAttendance = function (btn) {
  const id = $(btn).data("attendance");

  Swal.fire({
    title: "Delete attendance?",
    text: "This will also delete any overtime generated from it.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    cancelButtonText: "Cancel",
  }).then(async (r) => {
    if (!r.isConfirmed) return;

    try {
      await attendancesService.delete({ attendance: id });
      const date = $("#date").val();
      await window.getAttendances(date);
    } catch (e) {
      console.error(e);
      Swal.fire("Error", e.response?.data?.message || e.message || "Delete failed", "error");
    }
  });
};


