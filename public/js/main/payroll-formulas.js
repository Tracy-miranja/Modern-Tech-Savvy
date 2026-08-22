import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import PayrollFormulasService from "/js/client/PayrollFormulas.js";

const requestClient = new RequestClient();
const payrollFormulasService = new PayrollFormulasService(requestClient);

window.getFormulas = async function (page = 1) {
    try {
        let data = { page: page };
        const response = await payrollFormulasService.fetch(data);
        $("#formulasContainer").html(response.html);
        $("#formulaCount").text(response.count);
    } catch (error) {
        console.error("Error loading formulas:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load formulas.', 'error');
    }
};

window.saveFormula = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    let formData = new FormData(document.getElementById("formulaForm"));

    try {
        if (formData.has("formula_id")) {
            await payrollFormulasService.update(formData);
            Swal.fire('Success!', 'Formula updated successfully.', 'success');
        } else {
            await payrollFormulasService.save(formData);
            Swal.fire('Success!', 'Formula created successfully.', 'success');
        }
        $('#formulaForm')[0].reset();
        $('#formulaFormContainer').html(await payrollFormulasService.edit({}));
        getFormulas();
    } catch (error) {
        console.error("Error saving formula:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to save formula.', 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.editFormula = async function (btn) {
    btn = $(btn);
    const formula = btn.data("formula");
    const data = { formula_id: formula };

    try {
        const form = await payrollFormulasService.edit(data);
        $('#formulaFormContainer').html(form);
        $('#formulaFormTitle').text('Edit Payroll Formula');
    } catch (error) {
        console.error("Error editing formula:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load edit form.', 'error');
    }
};

window.deleteFormula = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const formula = btn.data("formula");
    const data = { formula_id: formula };

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
                await payrollFormulasService.delete(data);
                Swal.fire('Deleted!', 'Formula deleted successfully.', 'success');
                getFormulas();
            } catch (error) {
                console.error("Error deleting formula:", error);
                Swal.fire('Error!', error.response?.data?.message || 'Failed to delete formula.', 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    getFormulas();
});