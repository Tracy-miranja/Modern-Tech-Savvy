import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import JobApplicationService from "/js/client/JobApplicationService.js";

const requestClient = new RequestClient();
const jobApplicationService = new JobApplicationService(requestClient);

window.getJobApplications = async function (page = 1) {
    try {
        const JobApplications = await jobApplicationService.fetch({ page });

        $("#jobApplicationsContainer").html(JobApplications);

        if ($.fn.DataTable.isDataTable('#jobApplicationsTable')) {
            $('#jobApplicationsTable').DataTable().destroy();
        }

        new DataTable('#jobApplicationsTable', {
            pageLength: 10,
            language: {
                emptyTable: "No applications found."
            }
        });

    } catch (error) {
        console.error("Error loading applications:", error);
    }
};

// Select all checkbox (safe for AJAX reloads)
$(document).on('click', '#selectAll', function () {
    $('input[name="application_ids[]"]').prop('checked', this.checked);
});


// window.getJobApplications = async function (page = 1) {
//     try {
//         let data = {page:page};
//         const JobApplications = await jobApplicationService.fetch(data);
//         $("#jobApplicationsContainer").html(JobApplications);
//         new DataTable('#jobApplicationsTable');
//     } catch (error) {
//         console.error("Error loading user data:", error);
//     }
// };
window.saveApplication = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    tinymce.triggerSave();

    let formData = new FormData(document.getElementById("jobApplicationForm"));

    try {
        if (formData.has('_period_slug')) {
            await jobApplicationService.update(formData);
        } else {
            await jobApplicationService.save(formData);
        }
    } finally {
        btn_loader(btn, false);
    }
};
window.editJobApplication = async function (btn) {
    btn = $(btn);

    const job_application  = btn.data("job-application");
    const data = { job_application:job_application  };

    try {
        const form = await jobApplicationService.edit(data);
        $('#jobApplicationFormContainer').html(form)
    } finally {
    }
};
window.deleteJobApplication = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const job_application = btn.data("job-application");
    const data = { job_application: job_application };

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
                await jobApplicationService.delete(data);
                getJobApplications();
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};
