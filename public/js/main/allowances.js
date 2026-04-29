import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import AllowancesService from "/js/client/Allowances.js";

const requestClient = new RequestClient();
const allowancesService = new AllowancesService(requestClient);

window.getAllowances = async function (page = 1) {
    try {
        let data = { page: page };
        const response = await allowancesService.fetch(data);
        $("#allowancesContainer").html(response.html);
        $("#allowanceCount").text(response.count);
    } catch (error) {
        console.error("Error loading allowances:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load allowances.', 'error');
    }
};

window.saveAllowance = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    let formData = new FormData(document.getElementById("allowanceForm"));
     console.log("Form data entries:");
    for (let [key, value] of formData.entries()) {
        console.log(key, ':', value);
    }

    try {
        if (formData.get("allowance_id")) {
            await allowancesService.update(formData);
            Swal.fire('Success!', 'Allowance updated successfully.', 'success');
        } else {
            await allowancesService.save(formData);
            Swal.fire('Success!', 'Allowance created successfully.', 'success');
        }
        $('#allowanceForm')[0].reset();
        $('#allowanceFormContainer').html(await allowancesService.edit({}));
        $('#allowanceForm').removeClass('was-validated');
        setTimeout(() => getAllowances(), 100); // Add 100ms delay
    } catch (error) {
        console.error("Error saving allowance:", error);
        const errors = error.response?.data?.errors || [error.response?.data?.message || 'Failed to save allowance.'];
        Swal.fire('Error!', errors.join('<br>'), 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.editAllowance = async function (btn) {
    btn = $(btn);
    const allowance = btn.data("allowance");
    const data = { allowance_id: allowance };

    try {
        const form = await allowancesService.edit(data);
        $('#allowanceFormContainer').html(form);
        $('#allowanceForm').removeClass('was-validated'); // Reset validation state
    } catch (error) {
        console.error("Error editing allowance:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load edit form.', 'error');
    }
};

window.deleteAllowance = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const allowance = btn.data("allowance");
    const data = { allowance_id: allowance };

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
                await allowancesService.delete(data);
                Swal.fire('Deleted!', 'Allowance deleted successfully.', 'success');
                getAllowances();
            } catch (error) {
                console.error("Error deleting allowance:", error);
                Swal.fire('Error!', error.response?.data?.message || 'Failed to delete allowance.', 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    getAllowances();
});
