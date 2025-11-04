// attendances.js
import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import AttendancesService from "/js/client/AttendancesService.js";

const requestClient = new RequestClient();
const attendancesService = new AttendancesService(requestClient);

// ---- Helpers: feature flags from meta ----
function flag(name, def = 0) {
    const el = document.querySelector(`meta[name="${name}"]`);
    return el ? parseInt(el.content || "0", 10) : def;
}
const ENFORCE_GEOFENCE = flag("enforce_geofence", 0) === 1;
const ENFORCE_MAC      = flag("enforce_mac", 0) === 1;

// Show MAC input fields if company policy enforces MAC
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

// Attach MAC/coords to either FormData (clock-in flow) OR plain object (clock-out flow)
async function attachPunchMeta(payload, { isFormData = true, isClockIn = true } = {}) {
    const setField = (k, v) => {
        if (isFormData) payload.set(k, v);
        else payload[k] = v;
    };

    // MAC
    if (ENFORCE_MAC) {
        const inputId = isClockIn ? "clockin_device_mac_input" : "clockout_device_mac_input";
        const typed = document.getElementById(inputId)?.value || "";
        const mac = normMac(typed || localStorage.getItem("device_mac") || "");
        if (!mac) throw new Error("Device MAC is required by your company policy.");
        localStorage.setItem("device_mac", mac);
        setField("device_mac", mac);
    } else {
        // If user typed a MAC anyway, include it
        const inputId = isClockIn ? "clockin_device_mac_input" : "clockout_device_mac_input";
        const typed = document.getElementById(inputId)?.value || "";
        if (typed) setField("device_mac", normMac(typed));
    }

    // Coordinates
    if (ENFORCE_GEOFENCE) {
        try {
            const { lat, lng } = await getPositionOnce();
            setField("latitude", lat);
            setField("longitude", lng);

            // Mirror to hidden debug fields if present
            const latEl = document.getElementById(isClockIn ? "clockin_latitude"  : "clockout_latitude");
            const lngEl = document.getElementById(isClockIn ? "clockin_longitude" : "clockout_longitude");
            if (latEl) latEl.value = lat;
            if (lngEl) lngEl.value = lng;
        } catch (e) {
            throw new Error("Location permission is required by your company policy.");
        }
    } else {
        // Best-effort optional coords (won’t block)
        try {
            const { lat, lng } = await getPositionOnce();
            setField("latitude", lat);
            setField("longitude", lng);
        } catch (_) { /* ignore if not enforced */ }
    }

    return payload;
}

// ---- RESTORED list-loading functions ----
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

// ---- Punch actions ----
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

    // Keep original service signature (plain object), but attach MAC/coords
    let payload = {};

    if ($("#clockOutForm").length) {
        // Admin page flow
        const form = document.getElementById("clockOutForm");
        const selectedEmployee = form.querySelector('#employee_id')?.value || '';
        if (!selectedEmployee) {
            btn_loader($btn, false);
            return Swal.fire('Error', 'Select an employee.', 'error');
        }
        payload.employee = selectedEmployee;
        payload.remarks  = form.querySelector('#remarks')?.value || '';
    } else {
        // Self-punch button on employee page
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
