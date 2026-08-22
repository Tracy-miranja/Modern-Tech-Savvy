import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import KPIsService from "/js/client/KPIsService.js";

const requestClient = new RequestClient();
const kpisService = new KPIsService(requestClient);

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing KPIs');
    getKPIs();
    setupTabs();
    setupKpiFormSubmission();
});

function setupTabs() {
    // Rely on Bootstrap 5 for tab functionality
}

function setupKpiFormSubmission() {
    const form = document.getElementById('kpiForm');
    const submitButton = document.getElementById('kpiSubmitBtn');
    const cancelButton = document.getElementById('cancelKpiBtn');

    if (form && submitButton) {
        // Prevent duplicate event listeners
        form.removeEventListener('submit', handleKpiFormSubmit);
        form.addEventListener('submit', handleKpiFormSubmit);

        // Check form validity on load
        checkKpiFormValidity(form, submitButton);

        // Listen for input and select changes
        form.querySelectorAll('input, select, textarea').forEach(input => {
            input.removeEventListener('input', updateKpiFormValidity);
            input.addEventListener('input', updateKpiFormValidity);
            input.removeEventListener('change', updateKpiFormValidity);
            input.addEventListener('change', updateKpiFormValidity);
        });

        // Handle model_type change for calculation fields
        const modelTypeSelect = form.querySelector('#model_type');
        if (modelTypeSelect) {
            modelTypeSelect.removeEventListener('change', toggleCalculationFields);
            modelTypeSelect.addEventListener('change', toggleCalculationFields);
            toggleCalculationFields(modelTypeSelect);
        }

        // Handle assignment restriction
        const assignmentFields = ['business_id', 'location_id', 'department_id', 'job_category_id', 'employee_id'];
        assignmentFields.forEach(field => {
            const select = form.querySelector(`#${field}`);
            if (select) {
                select.removeEventListener('change', restrictAssignment);
                select.addEventListener('change', restrictAssignment);
            }
        });

        function handleKpiFormSubmit(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('KPI form submission intercepted');
            submitButton.disabled = true;
            window.saveKPI(submitButton);
        }

        function updateKpiFormValidity() {
            checkKpiFormValidity(form, submitButton);
        }

        function checkKpiFormValidity(form, button) {
            const isValid = form.checkValidity();
            button.disabled = !isValid;
            console.log('KPI form validity:', isValid);
        }

        function toggleCalculationFields(select) {
            const calculationFields = document.getElementById('calculationFields');
            if (calculationFields) {
                if (select.value === 'manual') {
                    calculationFields.style.display = 'none';
                    document.getElementById('calculation_method').value = '';
                    document.getElementById('target_value').value = '';
                    document.getElementById('comparison_operator').value = '';
                } else {
                    calculationFields.style.display = 'block';
                }
            } else {
                console.error('Calculation fields not found');
            }
        }

        function restrictAssignment(event) {
            const selectedField = event.target.id;
            assignmentFields.forEach(field => {
                const select = document.getElementById(field);
                if (field !== selectedField) {
                    if (event.target.value) {
                        select.value = '';
                        select.disabled = true;
                    } else {
                        select.disabled = false;
                    }
                }
            });
        }
    } else {
        console.error('KPI form or submit button not found', { form, submitButton });
    }

    if (cancelButton) {
        cancelButton.removeEventListener('click', handleCancelClick);
        cancelButton.addEventListener('click', handleCancelClick);

        async function handleCancelClick() {
            try {
                console.log('Cancel button clicked, resetting form');
                const response = await kpisService.edit({});
                if (response.success && response.data) {
                    $('#kpiFormContainer').html(response.data);
                    setupKpiFormSubmission();
                } else {
                    toastr.error('Failed to reset form: ' + (response.message || 'Unknown error'));
                }
            } catch (error) {
                toastr.error('Error resetting form: ' + error.message);
                console.error('Cancel form error:', error);
            }
        }
    }
}

function setupReviewFormSubmission() {
    // Try multiple selectors for SweetAlert2 modal content
    let modalContent = document.querySelector('.swal2-container .swal2-html-container') ||
        document.querySelector('.swal2-container .swal2-content') ||
        document.querySelector('.swal2-container');

    if (!modalContent) {
        console.warn('Modal content not found, retrying in 100ms...');
        setTimeout(setupReviewFormSubmission, 100);
        return;
    }

    console.log('Modal content found:', modalContent);

    const form = modalContent.querySelector('#kpiReviewForm');
    const submitButton = modalContent.querySelector('#submitReviewBtn');

    if (form && submitButton) {
        form.removeEventListener('submit', handleFormSubmit);
        form.addEventListener('submit', handleFormSubmit);

        checkFormValidity(form, submitButton);

        form.querySelectorAll('input, textarea').forEach(input => {
            input.removeEventListener('input', updateFormValidity);
            input.addEventListener('input', updateFormValidity);
        });

        function handleFormSubmit(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('Review form submission intercepted');
            submitButton.disabled = true;
            const submitLoading = modalContent.querySelector('#submitLoading');
            if (submitLoading) submitLoading.style.display = 'inline';
            submitReview(submitButton);
        }

        function updateFormValidity() {
            checkFormValidity(form, submitButton);
        }

        function checkFormValidity(form, button) {
            const isValid = form.checkValidity();
            button.disabled = !isValid;
        }
    } else {
        console.error('KPI review form or submit button not found in modal', { form, submitButton });
    }

    const deleteButtons = modalContent.querySelectorAll('.delete-review-btn');
    deleteButtons.forEach(button => {
        button.removeEventListener('click', handleDeleteClick);
        button.addEventListener('click', handleDeleteClick);
    });

    async function handleDeleteClick(event) {
        const reviewId = event.target.dataset.reviewId;
        const kpiId = event.target.dataset.kpiId;
        const { isConfirmed } = await Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to delete this review?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes',
        });
        if (isConfirmed) {
            window.submitReviewDelete(reviewId, kpiId);
        }
    }
}

window.getKPIs = async function () {
    try {
        console.log('Fetching KPIs...');
        showLoading('#businessKpisContainer');
        showLoading('#locationKpisContainer');
        showLoading('#departmentKpisContainer');
        showLoading('#jobCategoryKpisContainer');
        showLoading('#employeeKpisContainer');

        const response = await kpisService.fetch({});
        console.log('Fetch KPIs response:', response);

        if (response.success && response.data) {
            const containers = {
                business: $('#businessKpisContainer'),
                location: $('#locationKpisContainer'),
                department: $('#departmentKpisContainer'),
                jobCategory: $('#jobCategoryKpisContainer'),
                employee: $('#employeeKpisContainer')
            };

            Object.keys(containers).forEach(key => {
                if (!containers[key].length) {
                    console.error(`Container #${key}KpisContainer not found in DOM`);
                }
            });

            containers.business.html(response.data.business_html || '<p class="text-center text-muted">No Business KPIs available.</p>');
            containers.location.html(response.data.location_html || '<p class="text-center text-muted">No Location KPIs available.</p>');
            containers.department.html(response.data.department_html || '<p class="text-center text-muted">No Department KPIs available.</p>');
            containers.jobCategory.html(response.data.job_category_html || '<p class="text-center text-muted">No Job Category KPIs available.</p>');
            containers.employee.html(response.data.employee_html || '<p class="text-center text-muted">No Employee KPIs available.</p>');
            $("#kpiCount").text(response.data.count || 0);
            console.log('KPI cards updated, count:', response.data.count);
        } else {
            console.error('Fetch KPIs failed:', response.message);
            toastr.error('Failed to load KPIs: ' + (response.message || 'Unknown error'));
            throw new Error('Fetch failed');
        }
    } catch (error) {
        console.error('Error loading KPIs:', error);
        toastr.error('An error occurred while loading KPIs: ' + (error.message || 'Unknown error'));
        $('#businessKpisContainer').html('<p class="text-danger text-center">Error loading KPIs.</p>');
        $('#locationKpisContainer').html('<p class="text-danger text-center">Error loading KPIs.</p>');
        $('#departmentKpisContainer').html('<p class="text-danger text-center">Error loading KPIs.</p>');
        $('#jobCategoryKpisContainer').html('<p class="text-danger text-center">Error loading KPIs.</p>');
        $('#employeeKpisContainer').html('<p class="text-danger text-center">Error loading KPIs.</p>');
    }
};

async function refreshKpiCards() {
    try {
        console.log('Refreshing KPI cards...');
        const response = await kpisService.fetchCards({}); // Ensure data is an empty object
        console.log('Fetch KPI cards response:', response);

        if (response.success && response.data) {
            const kpiCardsContainer = document.getElementById('kpiCardsContainer');
            if (kpiCardsContainer) {
                kpiCardsContainer.innerHTML = response.data.cards_html || '<p class="text-center text-muted">No KPIs available.</p>';
                console.log('KPI cards updated');
            } else {
                console.error('KPI cards container not found');
                toastr.error('Failed to update KPI cards: Container not found');
            }
        } else {
            console.error('Fetch KPI cards failed:', response.message);
            toastr.error('Failed to load KPI cards: ' + (response.message || 'Unknown error'));
            throw new Error('Fetch cards failed');
        }
    } catch (error) {
        console.error('Error loading KPI cards:', error);
        toastr.error('An error occurred while loading KPI cards: ' + (error.message || 'Unknown error'));
        const kpiCardsContainer = document.getElementById('kpiCardsContainer');
        if (kpiCardsContainer) {
            kpiCardsContainer.innerHTML = '<p class="text-danger text-center">Error loading KPIs.</p>';
        }
    }
}

window.saveKPI = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const form = document.getElementById("kpiForm");
    const formData = new FormData(form);

    try {
        console.log('Saving KPI, form data:', Object.fromEntries(formData));
        if (formData.has("kpi_id")) {
            const response = await kpisService.update(formData);
            if (!response.success) throw new Error(response.message || 'Update failed');
            toastr.success('KPI updated successfully!');
        } else {
            const response = await kpisService.save(formData);
            if (!response.success) throw new Error(response.message || 'Creation failed');
            toastr.success('KPI created successfully!');
        }

        form.reset();
        const editResponse = await kpisService.edit({});
        if (editResponse.success && editResponse.data) {
            console.log('Resetting form with new content');
            const kpiFormContainer = $('#kpiFormContainer');
            kpiFormContainer.html(editResponse.data);
            kpiFormContainer.show(); // Ensure visibility
            setupKpiFormSubmission();
        } else {
            console.error('Failed to load reset form:', editResponse.message);
            toastr.error('Failed to reset form: ' + (editResponse.message || 'Unknown error'));
        }

        await refreshKpiCards(); // Refresh KPI cards
    } catch (error) {
        toastr.error('Failed to save KPI: ' + error.message);
        console.error('Save KPI error:', error);
    } finally {
        btn_loader(btn, false);
    }
};

window.editKPI = async function (btn) {
    btn = $(btn);
    const kpiId = btn.data("kpi");
    const data = { kpi_id: kpiId };

    try {
        console.log('Editing KPI:', kpiId);
        const response = await kpisService.edit(data);
        if (response.success && response.data) {
            $('#kpiFormContainer').html(response.data);
            setupKpiFormSubmission();
        } else {
            toastr.error('Failed to load edit form: ' + (response.message || 'Unknown error'));
        }
    } catch (error) {
        toastr.error('Error loading edit form: ' + error.message);
        console.error('Edit KPI error:', error);
    }
};

async function submitReview(btn) {
    const modalContent = document.querySelector('.swal2-container .swal2-html-container') ||
        document.querySelector('.swal2-container .swal2-content') ||
        document.querySelector('.swal2-container');
    if (!modalContent) {
        toastr.error('Modal content not found');
        return;
    }

    try {
        const form = modalContent.querySelector("#kpiReviewForm");
        if (!form) {
            toastr.error('Review form not found');
            return;
        }

        const formData = new FormData(form);
        const hasReviewId = formData.has("review_id") && formData.get("review_id") !== "";
        const kpiId = formData.get("kpi_id");

        console.log('Submitting review to:', hasReviewId ? '/kpis/update-review' : '/kpis/review', 'Form data:', Object.fromEntries(formData));

        const response = hasReviewId
            ? await kpisService.updateReview(formData)
            : await kpisService.review(formData);

        if (response.success) {
            toastr.success(`Review ${hasReviewId ? 'updated' : 'submitted'} successfully!`);
            form.reset();

            const refreshResponse = await kpisService.results({ kpi_id: kpiId });
            if (refreshResponse.success) {
                Swal.update({
                    html: refreshResponse.data
                });
                setupReviewFormSubmission();
            } else {
                toastr.error('Failed to refresh results: ' + refreshResponse.message);
            }
        } else {
            toastr.error(`Failed to ${hasReviewId ? 'update' : 'submit'} review: ${response.message || 'Unknown error'}`);
            throw new Error(response.message);
        }
    } catch (error) {
        toastr.error('Error during review submission: ' + error.message);
        console.error('Submit review error:', error);
    } finally {
        btn.disabled = false;
        const submitLoading = modalContent.querySelector('#submitLoading');
        if (submitLoading) submitLoading.style.display = 'none';
    }
}

window.submitReviewDelete = async function (reviewId, kpiId) {
    try {
        console.log('Deleting review:', reviewId, 'for KPI:', kpiId);
        const response = await kpisService.deleteReview({ review_id: reviewId });
        if (response.success) {
            toastr.success('Review deleted successfully!');
            const refreshResponse = await kpisService.results({ kpi_id: kpiId });
            if (refreshResponse.success) {
                Swal.update({
                    html: refreshResponse.data
                });
                setupReviewFormSubmission();
            } else {
                toastr.error('Failed to refresh results: ' + refreshResponse.message);
            }
        } else {
            toastr.error('Failed to delete review: ' + (response.message || 'Unknown error'));
            throw new Error(response.message);
        }
    } catch (error) {
        toastr.error('Error deleting review: ' + error.message);
        console.error('Delete review error:', error);
    }
};

window.deleteKPI = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const kpiId = btn.data("kpi");
    const data = { kpi_id: kpiId };

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await kpisService.delete(data);
                toastr.info('KPI deleted successfully!');
                await getKPIs();
                await refreshKpiCards();
            } catch (error) {
                toastr.error('Failed to delete KPI: ' + error.message);
                console.error('Delete KPI error:', error);
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

window.viewResults = async function (btn) {
    const kpiId = btn.dataset.kpi;
    const data = { kpi_id: kpiId };

    try {
        const response = await kpisService.results(data);
        if (response.success) {
            Swal.fire({
                title: 'KPI Results',
                html: response.data,
                icon: 'info',
                showCloseButton: true,
                showConfirmButton: false,
                width: '800px',
                didOpen: () => {
                    console.log('Modal opened, setting up review form submission');
                    setupReviewFormSubmission();
                }
            });
        } else {
            toastr.error('Failed to load results: ' + response.message);
        }
    } catch (error) {
        toastr.error('Error loading results: ' + error.message);
        console.error('View results error:', error);
    }
};

function showLoading(selector) {
    $(selector).html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
}

function hideLoading(selector) {
    // Removed to prevent clearing content
}