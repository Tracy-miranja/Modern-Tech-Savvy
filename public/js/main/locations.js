import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LocationsService from "/js/client/LocationsService.js";

const requestClient = new RequestClient();
const locationsService = new LocationsService(requestClient);

// Populates the "Country" select from the same Nager.Date country list the
// Holidays import modal uses, so a location's country matches exactly what
// public-holiday scoping (Location::resolvedCountry()) expects. `root` must
// be the actual container the form was injected into.
window.initLocationCountryUI = async function (root) {
    root = root || document;
    const select = root.querySelector('#country');
    if (!select) return;

    const url = select.dataset.countriesUrl;
    const selected = select.dataset.selected || '';
    if (!url) return;

    try {
        const response = await requestClient.get(url);
        const countries = response.data?.countries || [];

        select.innerHTML = '<option value="">Business default</option>' + countries
            .map(c => `<option value="${c.name}" ${c.name === selected ? 'selected' : ''}>${c.name}</option>`)
            .join('');
    } catch (error) {
        console.error('Error loading countries:', error);
        select.innerHTML = `<option value="${selected}">${selected || 'Could not load countries'}</option>`;
    }
};

document.addEventListener('DOMContentLoaded', function () {
    window.initLocationCountryUI(document);
});

window.getLocations = async function (page = 1) {
    try {
        let data = { page: page };
        const locationTable = await locationsService.fetch(data);
        $("#locationsContainer").html(locationTable);

        const exportTitle = "Locations Report";
        const exportButtons = [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ].map(type => ({
            extend: type,
            text: `<i class="fa fa-${type === 'copy' ? 'copy' : type}"></i> ${type.charAt(0).toUpperCase() + type.slice(1)}`,
            className: `btn btn-${type === 'copy' ? 'primary' : type === 'csv' ? 'secondary' : type === 'excel' ? 'success' : type === 'pdf' ? 'danger' : 'info'}`,
            title: exportTitle,
            exportOptions: {
                columns: ':not(:last-child)'
            }
        }));

        exportButtons.push({
            text: '<i class="fa fa-envelope"></i> Email',
            className: 'btn btn-warning',
            action: function () {
                sendEmailReport();
            }
        });

        exportButtons.push({
            text: '<i class="fa fa-trash"></i> Delete Selected',
            className: 'btn btn-danger',
            action: function () {
                deleteSelectedLocations();
            }
        });

        const table = new DataTable('#locationsTable', {
            dom: '<"top"lBf>rt<"bottom"ip>',
            order: [[3, 'desc']],
            lengthMenu: [[5, 10, 20, 50, 100, 500, 1000], [5, 10, 20, 50, 100, 500, 1000]],
            pageLength: 10,
            buttons: exportButtons
        });

        let selectedIds = [];

        $('#locationsTable tbody').on('click', 'tr', function () {
            $(this).toggleClass('selected');

            const id = $(this).find('.row-id').data('id');
            if ($(this).hasClass('selected')) {
                selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(item => item !== id);
            }
        });

        window.getSelectedIds = function () {
            return selectedIds;
        };

    } catch (error) {
        console.error("Error loading user data:", error);
    }
};

async function deleteSelectedLocations() {
    let selectedIds = window.getSelectedIds();

    if (selectedIds.length === 0) {
        Swal.fire({
            title: "No Selection",
            text: "Please select at least one location to delete.",
            icon: "info",
            confirmButtonColor: "#3085d6",
            confirmButtonText: "OK",
        });
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
                await locationsService.delete({ ids: selectedIds });

                Swal.fire({
                    title: "Deleted!",
                    text: "Selected locations have been deleted.",
                    icon: "success",
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: "OK",
                });

                getLocations();
            } catch (error) {
                console.error("Error deleting locations:", error);

                Swal.fire({
                    title: "Error!",
                    text: "Something went wrong while deleting locations.",
                    icon: "error",
                    confirmButtonColor: "#d33",
                    confirmButtonText: "OK",
                });
            }
        }
    });
}

function sendEmailReport() {
    const subject = encodeURIComponent("Locations Report");
    const body = encodeURIComponent("Here is the locations report. Please find the attached file or download it from the system.");

    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}


window.saveLocation = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    let form = document.getElementById("locationsForm");
    let formData = new FormData(form);

    try {
        if (formData.has('location_slug') && formData.get('location_slug')) {
            await locationsService.update(formData);

            form.reset();
            $("#location_slug").val("");

            $("#location_slug").val("");
            $("#name").val("");
            $("#company_size").val("");
            $("#address").val("");
            $("#country").val("");

            $("#card-header").text("Add New Location");
            setTimeout(() => {
                $("#submitButton").html('<i class="bi bi-check-circle"></i> Save Location');
            }, 100);

        } else {
            await locationsService.save(formData);

            form.reset();
            $("#location_slug").val("");

            $("#location_slug").val("");
            $("#name").val("");
            $("#company_size").val("");
            $("#address").val("");
            $("#country").val("");

            $("#card-header").text("Add New Location");
            setTimeout(() => {
                $("#submitButton").html('<i class="bi bi-check-circle"></i> Save Location');
            }, 100);
        }

        getLocations();
    } finally {
        btn_loader(btn, false);
    }
};

window.editLocation = async function (btn) {
    btn = $(btn);

    const location = btn.data("location");
    const data = { location: location };

    try {
        const form = await locationsService.edit(data);
        $('#locationsFormContainer').html(form);
        window.initLocationCountryUI(document.getElementById('locationsFormContainer'));

        setTimeout(() => {
            $("#submitButton").html('<i class="bi bi-check-circle"></i> Update Location');
        }, 100);

        $("#card-header").text("Edit Location");
    } finally {
    }
};

window.deleteLocation = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const location = btn.data("location");
    const data = { location: location };

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
                await locationsService.delete(data);
                getLocations();
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};
