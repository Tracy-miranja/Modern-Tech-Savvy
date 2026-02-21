import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import OvertimeService from "/js/client/OvertimeService.js";

const requestClient = new RequestClient();
const overtimeService = new OvertimeService(requestClient);

function getBusinessSlug() {
    // Prefer DOM attribute (works even after AJAX renders)
    const wrap = document.getElementById("overtimeTableWrap");
    const slug = wrap?.dataset?.businessSlug;

    // Fallback to meta if you later add it in layout
    const metaSlug = document.querySelector('meta[name="active-business-slug"]')?.content;

    return slug || metaSlug || "";
}

function assertBusinessSlug() {
    const slug = getBusinessSlug();
    if (!slug) {
        throw new Error("Business slug missing. Ensure overtime table wrapper has data-business-slug.");
    }
    return slug;
}

function initOvertimeTable() {
    if (typeof DataTable !== "undefined" && document.querySelector("#overtimeTable")) {
        try {
            new DataTable("#overtimeTable", { order: [[2, "desc"]] });
        } catch (e) {
            console.warn("DataTable init failed:", e);
        }
    }
}

window.getOvertime = async function (filters = {}) {
    try {
        const overtimeHtml = await overtimeService.fetch(filters);
        $("#overtimeContainer").html(overtimeHtml);
        initOvertimeTable();
    } catch (error) {
        console.error("Error loading overtime data:", error);
        Swal.fire("Error!", error?.response?.data?.message || "Failed to load overtime data.", "error");
    }
};

window.saveOvertime = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const formEl = document.getElementById("overtimeForm");
    const formData = new FormData(formEl);

    try {
        // ✅ if overtime_id exists => update
        const overtimeId = formData.get("overtime_id");
        if (overtimeId) {
            await overtimeService.update(formData);
        } else {
            await overtimeService.save(formData);
        }

        $("#addOvertimeModal").modal("hide");
        await getOvertime();
    } catch (error) {
        console.error("Error saving overtime:", error);
        Swal.fire("Error!", error?.response?.data?.message || "Failed to save overtime.", "error");
    } finally {
        btn_loader(btn, false);
    }
};

window.editOvertime = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const overtimeId = btn.data("overtime");
    const data = { overtime: overtimeId };

    try {
        const formHtml = await overtimeService.edit(data);
        $("#overtimeFormContainer").html(formHtml);
        $("#addOvertimeModal").modal("show");
    } catch (error) {
        console.error("Error editing overtime:", error);
        Swal.fire("Error!", error?.response?.data?.message || "Failed to load overtime form.", "error");
    } finally {
        btn_loader(btn, false);
    }
};

window.deleteOvertime = async function (btn) {
    btn = $(btn);

    const overtimeId = btn.data("overtime");
    const data = { overtime: overtimeId };

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        btn_loader(btn, true);
        try {
            await overtimeService.delete(data);
            await getOvertime();
        } catch (error) {
            console.error("Error deleting overtime:", error);
            Swal.fire("Error!", error?.response?.data?.message || "Failed to delete overtime.", "error");
        } finally {
            btn_loader(btn, false);
        }
    });
};

window.approveOvertime = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const overtimeId = btn.data("overtime");

    try {
        const slug = assertBusinessSlug();
        const url = `/business/${slug}/overtime/approve`;

        const response = await requestClient.post(url, { overtime_id: overtimeId });
        toastr.success(response.message || "Approved", "Success");
        await getOvertime();
    } catch (error) {
        console.error("Error approving overtime:", error);
        Swal.fire("Error!", error?.response?.data?.message || "Failed to approve overtime.", "error");
    } finally {
        btn_loader(btn, false);
    }
};

window.rejectOvertime = async function (btn) {
    btn = $(btn);
    const overtimeId = btn.data("overtime");

    Swal.fire({
        title: "Reject Overtime",
        input: "textarea",
        inputLabel: "Rejection Reason",
        inputPlaceholder: "Please provide a reason for rejection...",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#068f6d",
        confirmButtonText: "Reject",
        inputValidator: (value) => (!value ? "You need to provide a reason!" : undefined),
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        btn_loader(btn, true);
        try {
            const slug = assertBusinessSlug();
            const url = `/business/${slug}/overtime/reject`;

            const response = await requestClient.post(url, {
                overtime_id: overtimeId,
                rejection_reason: result.value,
            });

            toastr.info(response.message || "Rejected", "Rejected");
            await getOvertime();
        } catch (error) {
            console.error("Error rejecting overtime:", error);
            Swal.fire("Error!", error?.response?.data?.message || "Failed to reject overtime.", "error");
        } finally {
            btn_loader(btn, false);
        }
    });
};

window.bulkApproveSelected = async function () {
    const selectedCheckboxes = document.querySelectorAll(".overtime-checkbox:checked");
    const overtimeIds = Array.from(selectedCheckboxes).map((cb) => cb.value);

    if (overtimeIds.length === 0) {
        Swal.fire("No Selection", "Please select overtime records to approve.", "warning");
        return;
    }

    Swal.fire({
        title: "Approve Selected Overtime?",
        text: `You are about to approve ${overtimeIds.length} overtime record(s).`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, approve them!",
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        try {
            const slug = assertBusinessSlug();
            const url = `/business/${slug}/overtime/bulk-approve`;

            const response = await requestClient.post(url, { overtime_ids: overtimeIds });
            Swal.fire("Approved!", response.message || "Bulk approved.", "success");
            await getOvertime();
        } catch (error) {
            console.error("Error bulk approving:", error);
            Swal.fire("Error!", error?.response?.data?.message || "Failed to approve overtime records.", "error");
        }
    });
};

// Filters
window.filterOvertimeByStatus = async function (status) {
    await getOvertime({ status });
};

window.filterOvertimeByType = async function (type) {
    await getOvertime({ overtime_type: type });
};
