import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import InterviewService from "/js/client/InterviewService.js";

const requestClient = new RequestClient();
const interviewService = new InterviewService(requestClient);

window.getInterviews = async function (page = 1) {
    try {
        let data = {page:page};
        const Interviews = await interviewService.fetch(data);
        $("#interviewsContainer").html(Interviews);
        new DataTable('#interviewsTable');
    } catch (error) {
        console.error("Error loading user data:", error);
    }
};
window.scheduleInterview = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    tinymce.triggerSave();

    let formData = new FormData(document.getElementById("interviewsForm"));

    try {
        if (formData.has('_period_slug')) {
            await interviewService.update(formData);
        } else {
            await interviewService.save(formData);
        }
    } finally {
        btn_loader(btn, false);
    }
};
// Note: the delete button in interviews/_table.blade.php submits its own
// (currently empty-action, pre-existing) form directly rather than going
// through interviewService.delete() like window.deleteInterview() below -
// this only replaces its native confirm() with the same Swal pattern,
// preserving that existing behavior rather than rewiring it.
window.confirmDeleteInterview = async function (btn) {
    const { isConfirmed } = await Swal.fire({
        title: "Are you sure?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes",
    });
    if (!isConfirmed) return;
    btn.closest('form').submit();
};

window.editInterview = async function (btn) {
    btn = $(btn);

    const job_application  = btn.data("job-application");
    const data = { job_application:job_application  };

    try {
        const form = await interviewService.edit(data);
        $('#interviewFormContainer').html(form)
    } finally {
    }
};
window.deleteInterview = async function (btn) {
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
                await interviewService.delete(data);
                getInterviews();
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};
