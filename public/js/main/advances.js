import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import AdvancesService from "/js/client/AdvancesService.js";

const requestClient = new RequestClient();
const advancesService = new AdvancesService(requestClient);

window.getAdvances = async function (page = 1) {
    try {
        let data = { page: page };
        const advances = await advancesService.fetch(data);
        $("#advancesContainer").html(advances);

        const exportTitle = `Advances Report - All`;
        const exportButtons = [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ].map(type => ({
            extend: type,
            text: `<i class="fa fa-${type === 'copy' ? 'copy' : type}"></i> ${type.charAt(0).toUpperCase() + type.slice(1)}`,
            className: `btn btn-${type}`,
            title: exportTitle,
            exportOptions: { columns: ':not(:last-child)' }
        }));

        exportButtons.push({
            text: '<i class="fa fa-envelope"></i> Email',
            className: 'btn btn-warning',
            action: function () { sendEmailReport(); }
        });

        exportButtons.push({
            text: '<i class="fa fa-trash"></i> Delete Selected',
            className: 'btn btn-danger',
            action: function () { deleteSelectedAdvances(); }
        });

        const table = new DataTable('#advancesTable', {
            dom: '<"top"lBf>rt<"bottom"ip>',
            order: [[3, 'desc']],
            lengthMenu: [[5, 10, 20, 50, 100, 500, 1000], [5, 10, 20, 50, 100, 500, 1000]],
            pageLength: 10,
            buttons: exportButtons
        });

        let selectedIds = [];

        $('#advancesTable tbody').on('click', 'tr', function () {
            $(this).toggleClass('selected');

            const id = $(this).data('id');
            if ($(this).hasClass('selected')) {
                selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(item => item !== id);
            }
        });

        window.getSelectedAdvances = function () {
            return selectedIds;
        };
    } catch (error) {
        console.error("Error loading advances data:", error);
    }
};
async function deleteSelectedAdvances() {
    let selectedIds = window.getSelectedAdvances();
    if (selectedIds.length === 0) {
        Swal.fire("No Selection", "Please select at least one advance to delete.", "info");
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete them!",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await advancesService.delete({ ids: selectedIds });
                Swal.fire("Deleted!", "Selected advances have been deleted.", "success");
                getAdvances(1, localStorage.getItem('advanceStatus'));
            } catch (error) {
                console.error("Error deleting advances:", error);
                Swal.fire("Error!", "Something went wrong while deleting advances.", "error");
            }
        }
    });
}

function sendEmailReport() {
    const subject = encodeURIComponent("Advances Report");
    const body = encodeURIComponent("Download the report as desired file type and attach here.");
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

window.saveAdvance = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    let formData = new FormData(document.getElementById("advanceForm"));

    try {
        if (formData.has('advance_id')) {
            await advancesService.update(formData);
            $("#employee_id").val("");
            $("#amount").val("");
            $("#date").val("");
            $("#note").val("");

            $("#card-header").text("Create a new Advance");
            setTimeout(() => {
                $("#submitButton").html('<i class="bi bi-check-circle"></i> Save Advance');
            }, 100);
        } else {
            await advancesService.save(formData);
        }
        getAdvances();
    } finally {
        btn_loader(btn, false);
    }
};
window.editAdvance = async function (btn) {
    btn = $(btn);

    const advance = btn.data("advance");
    const data = { advance: advance };

    try {
        const form = await advancesService.edit(data);
        $('#advancesFormContainer').html(form)
        $("#card-header").text("Update advance");
        setTimeout(() => {
            $("#submitButton").html('<i class="bi bi-check-circle"></i> Update advance');
        }, 100);
    } finally {
    }
};
window.deleteAdvance = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const advance = btn.data("advance");
    const data = { advance_id: advance };

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
                await advancesService.delete(data);
                getAdvances();
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};
