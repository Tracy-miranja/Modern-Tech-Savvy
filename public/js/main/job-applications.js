import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import JobApplicationService from "/js/client/JobApplicationService.js";

const requestClient = new RequestClient();
const jobApplicationService = new JobApplicationService(requestClient);

document.addEventListener('DOMContentLoaded', () => {
    const filterInput = document.getElementById('applicationFilter');
    const jobFilter = document.getElementById('jobFilter');
    const locationFilter = document.getElementById('locationFilter');
    const jobApplicationsContainer = document.getElementById('jobApplicationsContainer');

    if (jobApplicationsContainer) {
        getJobApplications();
    }

    if (filterInput) {
        filterInput.addEventListener('input', debounce(async () => {
            await filterJobApplications(1, filterInput.value, jobFilter?.value, locationFilter?.value);
        }, 300));
    }

    if (jobFilter) {
        jobFilter.addEventListener('change', debounce(async () => {
            await filterJobApplications(1, filterInput?.value, jobFilter.value, locationFilter?.value);
        }, 300));
    }

    if (locationFilter) {
        locationFilter.addEventListener('input', debounce(async () => {
            await filterJobApplications(1, filterInput?.value, jobFilter?.value, locationFilter.value);
        }, 300));
    }
});

function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

window.getJobApplications = async function (page = 1) {
    const container = $("#jobApplicationsContainer");
    try {
        container.html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading applications...</div>');
        const data = { page, _token: csrfToken };
        const response = await jobApplicationService.fetch(data);
        container.html(response);
        initializeDataTable('#jobApplicationsTable');
    } catch (error) {
        console.error("Error loading applications:", error);
        container.html(`<div class="alert alert-danger">Error: ${error.message}</div>`);
    }
};

window.filterJobApplications = async function (page = 1, filter = '', job_post_id = '', location = '') {
    const container = $("#jobApplicationsContainer");
    try {
        container.html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Filtering applications...</div>');
        const data = { page, filter, job_post_id, location, _token: csrfToken };
        if (!filter && !job_post_id && !location) {
            const response = await jobApplicationService.fetch({ page, _token: csrfToken });
            container.html(response);
        } else {
            const response = await jobApplicationService.fetch(data);
            container.html(response);
        }
        initializeDataTable('#jobApplicationsTable');
    } catch (error) {
        console.error("Error filtering applications:", error);
        container.html(`<div class="alert alert-danger">Error: ${error.message}</div>`);
    }
};

window.saveApplication = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    tinymce.triggerSave();
    const formData = new FormData(document.getElementById("jobApplicationForm"));
    formData.append('_token', csrfToken);
    try {
        if (formData.has('application_id')) {
            await jobApplicationService.update(formData);
            Swal.fire('Success', 'Application updated successfully!', 'success').then(() => {
                window.location.reload();
            });
        } else {
            await jobApplicationService.save(formData);
            Swal.fire('Success', 'Application saved successfully!', 'success').then(() => {
                window.location.href = `/business/${window.businessSlug}/applications`;
            });
        }
    } catch (error) {
        Swal.fire('Error', 'Failed to save application: ' + error.message, 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.editJobApplication = async function (application_id) {
    try {
        const form = await jobApplicationService.edit({ application_id, _token: csrfToken });
        $('#edit-application-form').html(form);
        $('#editApplicationModal').modal('show');
    } catch (error) {
        console.error('Error loading edit form:', error);
        toastr.error('Failed to load edit form: ' + error.message, "Error");
    }
};

window.deleteJobApplication = async function (application_id) {
    Swal.fire({
        title: "Are you sure?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await jobApplicationService.delete({ application_ids: [application_id], _token: csrfToken });
                getJobApplications();
                Swal.fire('Deleted!', 'Application deleted successfully.', 'success');
            } catch (error) {
                Swal.fire('Error', 'Failed to delete application: ' + error.message, 'error');
            }
        }
    });
};

window.deleteJobApplications = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    const selectedIds = Array.from(document.querySelectorAll('input[name="application_ids[]"]:checked')).map(input => input.value);

    if (selectedIds.length === 0) {
        toastr.warning('Please select at least one application to delete.', "Warning");
        btn_loader(btn, false);
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete them!"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await jobApplicationService.delete({ application_ids: selectedIds, _token: csrfToken });
                getJobApplications();
                Swal.fire('Deleted!', 'Selected applications deleted successfully.', 'success');
            } catch (error) {
                Swal.fire('Error', 'Failed to delete applications: ' + error.message, 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

window.shortlistApplications = async function (btn, applicationIds = null) {
    btn = $(btn);
    btn_loader(btn, true);
    const selectedIds = applicationIds || Array.from(document.querySelectorAll('input[name="application_ids[]"]:checked')).map(input => input.value);

    if (selectedIds.length === 0) {
        toastr.warning('Please select at least one application to shortlist.', "Warning");
        btn_loader(btn, false);
        return;
    }

    try {
        await jobApplicationService.shortlist({ application_ids: selectedIds, _token: csrfToken });
        getJobApplications();
        Swal.fire('Success', 'Selected applications shortlisted successfully!', 'success');
    } catch (error) {
        Swal.fire('Error', 'Failed to shortlist applications: ' + error.message, 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.updateApplicationStage = async function (btn, stage, applicationIds = null) {
    btn = $(btn);
    btn_loader(btn, true);
    const selectedIds = applicationIds || Array.from(document.querySelectorAll('input[name="application_ids[]"]:checked')).map(input => input.value);

    if (selectedIds.length === 0) {
        toastr.warning('Please select at least one application to update.', "Warning");
        btn_loader(btn, false);
        return;
    }

    try {
        await jobApplicationService.updateStage({ application_ids: selectedIds, stage, _token: csrfToken });
        getJobApplications();
        Swal.fire('Success', `Stage updated to ${stage} for selected applications!`, 'success');
    } catch (error) {
        Swal.fire('Error', `Failed to update stage: ${error.message}`, 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.scheduleInterview = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    const formData = new FormData(document.getElementById("scheduleInterviewForm"));
    formData.append('_token', csrfToken);
    try {
        await jobApplicationService.scheduleInterview(formData);
        $('#scheduleInterviewModal').modal('hide');
        getJobApplications();
        // Swal.fire('Success', 'Interview scheduled successfully!', 'success');
    } catch (error) {
        // Swal.fire('Error', 'Failed to schedule interview: ' + error.message, 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.exportApplications = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    try {
        const response = await jobApplicationService.export({ _token: csrfToken });
        const url = window.URL.createObjectURL(new Blob([response]));
        const a = document.createElement('a');
        a.href = url;
        a.download = `applications_${new Date().toISOString()}.xlsx`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        toastr.success('Applications exported successfully!', "Success");
    } catch (error) {
        const message = error.message || 'Failed to export applications';
        toastr.error(message, "Error");
    } finally {
        btn_loader(btn, false);
    }
};

window.downloadDocument = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    const applicant_id = btn.data("applicant-id");
    const media_id = btn.data("media-id");
    try {
        const blob = await jobApplicationService.downloadDocument({
            applicant_id,
            media_id,
            _token: window.csrfToken
        });

        const mimeToExt = {
            'application/pdf': 'pdf',
            'application/msword': 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'docx'
        };

        let extension = mimeToExt[blob.type] || 'bin';
        let filename = btn.text().trim().split(' ').slice(1).join(' ') ||
            `applicant_${applicant_id}_document_${media_id}.${extension}`;

        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        toastr.success('Document downloaded successfully!', 'Success');
    } catch (error) {
        console.error('Download error:', error);
        const errorMessage = error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : error.message || 'Unknown error';
        toastr.error('Failed to download document: ' + errorMessage, 'Error');
    } finally {
        btn_loader(btn, false);
    }
};

function initializeDataTable(selector) {
  const $table = $(selector);
  if (!$table.length) return;

  // If DataTable already exists, destroy it cleanly
  if ($.fn.DataTable.isDataTable($table)) {
    $table.DataTable().clear().destroy();
  }

  // Remove leftover DT classes that can remain after destroy
  $table.find('thead th').removeClass('sorting sorting_asc sorting_desc');

  // IMPORTANT: initialize after DOM is settled (prevents _DT_CellIndex issues)
  setTimeout(() => {
    $table.DataTable({
      responsive: true,
      autoWidth: false,
      destroy: true,
      retrieve: true,
      order: [[1, 'asc']],
      columnDefs: [
        { targets: 0, orderable: false, searchable: false },
      ],
      language: {
        emptyTable: "No applications available",
        loadingRecords: "Loading..."
      }
    });
  }, 0);
}
