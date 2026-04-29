import RequestClient from "/js/client/RequestClient.js";
const requestClient = new RequestClient();

function showModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  if (!window.bootstrap?.Modal) return;
  bootstrap.Modal.getOrCreateInstance(el).show();
}

window.viewPayrollEmployeeDetails = async function (btn) {
  const employee_id = btn.dataset.employee;
  const start_date = document.getElementById("ph_start")?.value;
  const end_date = document.getElementById("ph_end")?.value;

  $("#payrollEmployeeContainer").html('<div class="text-center p-4">Loading...</div>');
  showModal("payrollEmployeeModal");

  try {
    const slug = window.businessSlug;
    const url = `/business/${slug}/attendances/payroll-details`;
    const res = await requestClient.post(url, { employee_id, start_date, end_date });
    $("#payrollEmployeeContainer").html(res.data);
  } catch (e) {
    console.error(e);
    $("#payrollEmployeeContainer").html('<div class="alert alert-danger">Failed to load breakdown.</div>');
  }
};

// Export to Excel (all or filtered)
window.exportPayrollHoursExcel = function ({ mode = "filtered" } = {}) {
  const slug = window.businessSlug;
  const start_date = document.getElementById("ph_start")?.value;
  const end_date = document.getElementById("ph_end")?.value;

  let employee_id = document.getElementById("ph_employee")?.value || "";
  if (mode === "all") employee_id = ""; // ignore employee filter

  const qs = new URLSearchParams({
    start_date,
    end_date,
    employee_id,
  }).toString();

  // download
  window.location.href = `/business/${slug}/attendances/payroll-summary-export?${qs}`;
};

window.getPayrollHoursSummary = async function () {
    const start_date = document.getElementById("ph_start")?.value;
    const end_date = document.getElementById("ph_end")?.value;
    const employee_id = document.getElementById("ph_employee")?.value;

    try {
        const slug = window.businessSlug;

        const url = `/business/${slug}/attendances/payroll-summary`;

        const response = await requestClient.post(url, {
            start_date,
            end_date,
            employee_id
        });

        document.getElementById("payrollHoursContainer").innerHTML = response.data;

        if (typeof DataTable !== "undefined" && document.querySelector("#payrollHoursTable")) {
            new DataTable("#payrollHoursTable", { order: [[0, "asc"]] });
        }

    } catch (error) {
        console.error("Payroll summary error:", error);
        Swal.fire("Error", error.message || "Failed to load payroll hours summary", "error");
    }



};
