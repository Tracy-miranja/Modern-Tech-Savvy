import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import ReliefsService from "/js/client/Reliefs.js";

const requestClient = new RequestClient();
const reliefsService = new ReliefsService(requestClient);

window.getReliefs = async function (page = 1) {
    try {
        let data = { page: page };
        const response = await reliefsService.fetch(data);
        $("#reliefsContainer").html(response.html);
        $("#reliefCount").text(response.count);
    } catch (error) {
        console.error("Error loading reliefs:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load reliefs.', 'error');
    }
};

window.saveRelief = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    let formData = new FormData(document.getElementById("reliefForm"));

    try {
        const slug = formData.get("relief_slug");

        if (slug) {
            await reliefsService.update(formData, slug);
            Swal.fire('Success!', 'Relief updated successfully.', 'success');
        } else {
            await reliefsService.save(formData);
            Swal.fire('Success!', 'Relief created successfully.', 'success');
        }

        $("#reliefForm")[0].reset();

        // ❌ REMOVE THIS (causing the error)
        // $('#reliefFormContainer').html(await reliefsService.edit({}));

        getReliefs();

    } catch (error) {
        console.error("Error saving relief:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to save relief.', 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.editRelief = async function (btn) {
    btn = $(btn);
    const slug = btn.data("slug");

    try {
        const form = await reliefsService.edit({ slug });
        $('#reliefFormContainer').html(form);
    } catch (error) {
        console.error("Error editing relief:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load edit form.', 'error');
    }
};

window.viewRelief = async function (btn) {
    btn = $(btn);
    const slug = btn.data("slug");

    try {
        const modal = await reliefsService.show({ slug });
        $('#reliefModalContainer').html(modal);
        $('#reliefModal').modal('show');
    } catch (error) {
        console.error("Error viewing relief:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load relief details.', 'error');
    }
};

window.deleteRelief = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    const slug = btn.data("slug");

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
                await reliefsService.delete({ slug });
                Swal.fire('Deleted!', 'Relief deleted successfully.', 'success');
                getReliefs();
            } catch (error) {
                console.error("Error deleting relief:", error);
                Swal.fire('Error!', error.response?.data?.message || 'Failed to delete relief.', 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    getReliefs();
});
