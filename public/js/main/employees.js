
import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import EmployeesService from "/js/client/EmployeesService.js";

const requestClient = new RequestClient();
const employeesService = new EmployeesService(requestClient);
let dataTable;

document.addEventListener('DOMContentLoaded', () => {
    initializeDataTable();
    setupFilters();
});

function initializeDataTable() {
    dataTable = $('#employeesTable').DataTable({
        responsive: true,
        pageLength: 10,
        searching: false,
        serverSide: true,
        processing: true,
        ajax: {
            url: '/employees/fetch',
            type: 'POST',
            data: function (d) {
                  d.search = {
                    value: $('#search').val()
                };
                d.department = $('#filterDepartment').val();
                d.location = $('#filterLocation').val();
                d.job_category = $('#filterJobCategory').val();
                // Add CSRF token
                d._token = $('meta[name="csrf-token"]').attr('content');
            },
            beforeSend: function () {
                $('#loadingRow').show();
            },
            complete: function () {
                $('#loadingRow').hide();
            },
            error: function (xhr, error, thrown) {
                $('#loadingRow').hide();
                console.error('DataTables error:', xhr.responseText);
                Swal.fire('Error!', 'Failed to load table data.', 'error');
            }
        },
        columns: [
            { data: 'name' },
            { data: 'employee_code' },
            { data: 'department' },
            { data: 'job_category' },
            { data: 'location' },
            // { data: 'basic_salary' },
            { data: 'monthly_salary' },   // ← must match backend key
    { data: 'hourly_rate' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
}

// function initializeDataTable() {
//     dataTable = $('#employeesTable').DataTable({
//         responsive: true,
//         pageLength: 10,
//         searching: false,
//         serverSide: true,
//         processing: true,
//         ajax: {
//             url: '/employees/fetch',
//             type: 'POST',
//             data: function (d) {
//                 d.search = $('#search').val();
//                 d.department = $('#filterDepartment').val();
//                 d.location = $('#filterLocation').val();
//                 d.job_category = $('#filterJobCategory').val();
//             },
//             beforeSend: function () {
//                 $('#loadingRow').show();
//             },
//             complete: function () {
//                 $('#loadingRow').hide();
//             },
//             error: function (xhr, error, thrown) {
//                 $('#loadingRow').hide();
//                 console.error('DataTables error:', xhr.responseText);
//                 Swal.fire('Error!', 'Failed to load table data.', 'error');
//             }
//         },
//         columns: [
//             { data: 'name' },
//             { data: 'employee_code' },
//             { data: 'department' },
//             { data: 'job_category' },
//             { data: 'location' },
//             { data: 'basic_salary' },
//             { data: 'actions', orderable: false, searchable: false }
//         ]
//     });
// }

function setupFilters() {
    // Use separate event listeners for better control
    $('#search').on('keyup', function() {
        clearTimeout($(this).data('timeout'));
        $(this).data('timeout', setTimeout(function() {
            dataTable.ajax.reload();
        }, 300));
    });

    $('#filterDepartment, #filterLocation, #filterJobCategory').on('change', function() {
        dataTable.ajax.reload();
    });
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
}};

function setupDocumentHandlers() {
    let addDocumentClicked = false;
    $('#employeeModal').off('click', '#addDocument').on('click', '#addDocument', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (addDocumentClicked) return;
        addDocumentClicked = true;
        setTimeout(() => { addDocumentClicked = false; }, 500);

        const container = document.getElementById('documentEntries');
        if (!container) return;

        const entry = document.createElement('div');
        entry.className = 'document-entry card mb-3 shadow-sm';
        entry.innerHTML = `
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <input type="text" name="document_types[]" class="form-control"
                            placeholder="Document Type (e.g., ID, Certificate)" required>
                    </div>
                    <div class="col-md-5">
                        <input type="file" name="documents[]" class="form-control document-input"
                            accept=".pdf,.doc,.docx,.jpg,.png" required>
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-document">
                            <i class="fa fa-trash"></i> Remove
                        </button>
                    </div>
                    <div class="col-md-12 mt-2 document-preview" style="display: none;">
                        <div class="preview-container d-flex align-items-center p-2 border rounded bg-light">
                            <span class="file-name me-2"></span>
                            <a href="#" class="view-file btn btn-sm btn-primary me-2" target="_blank">View</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(entry);
    });

    $('#employeeModal').off('click', '.remove-document').on('click', '.remove-document', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const entry = this.closest('.document-entry');
        if (document.querySelectorAll('.document-entry').length > 1) {
            entry.remove();
        }
    });

    $('#employeeModal').off('change', '.document-input').on('change', '.document-input', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const input = e.target;
        const entry = input.closest('.document-entry');
        const previewContainer = entry.querySelector('.document-preview');
        const fileNameSpan = previewContainer.querySelector('.file-name');
        const viewLink = previewContainer.querySelector('.view-file');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            fileNameSpan.textContent = file.name;
            previewContainer.style.display = 'block';

            const url = URL.createObjectURL(file);
            viewLink.href = url;

            previewContainer.dataset.url = url;
        } else {
            previewContainer.style.display = 'none';
            fileNameSpan.textContent = '';
            viewLink.href = '#';
            if (previewContainer.dataset.url) {
                URL.revokeObjectURL(previewContainer.dataset.url);
                delete previewContainer.dataset.url;
            }
        }
    });

    $('#employeeModal').off('click', '.delete-document').on('click', '.delete-document', async function (e) {
        e.preventDefault();
        e.stopPropagation();
        const button = $(this);
        const row = button.closest('tr');
        const documentId = button.data('document-id');
        const employeeId = button.data('employee-id');

        if (!employeeId || !documentId) {
            toastr.error('Employee ID or Document ID not found.');
            return;
        }

        // Hide the modal
        $('#employeeModal').modal('hide');

        Swal.fire({
            title: "Are you sure?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then(async (result) => {
            // Always show the modal again after Swal closes
            $('#employeeModal').modal('show');

            if (result.isConfirmed) {
                try {
                    await employeesService.deleteDocument(employeeId, documentId);
                    row.remove();
                    toastr.success('Document deleted successfully.');
                } catch (error) {
                    console.error('Document Delete Error:', error.response || error);
                    toastr.error(error.response?.data?.message || 'Failed to delete document.');
                }
            }
        });
    });
}

window.createEmployee = async function () {
    try {
        const response = await employeesService.edit({});
        if (response) {
            $('#employeeFormContainer').html(response);
            $('#employeeModalLabel').text('Add Employee');
            $('#employeeModal').modal('show');
            setupDocumentHandlers();
        } else {
            throw new Error('No data returned from server');
        }
    } catch (error) {
        console.error('Create Employee Error:', error.response || error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load create form.', 'error');
    }
};

window.saveEmployee = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);
    const formData = new FormData(document.getElementById('employeeForm'));

    const documentTypes = formData.getAll('document_types[]').filter(type => type.trim() !== '');
    const documents = formData.getAll('documents[]').filter(doc => doc.size > 0);
    formData.delete('document_types[]');
    formData.delete('documents[]');

    try {
        let employeeId;
        if (formData.has('employee_id')) {
            employeeId = formData.get('employee_id');
            await employeesService.update(formData);
        } else {
            const response = await employeesService.save(formData);
            employeeId = response.data;
        }

        if (documentTypes.length > 0 && documents.length > 0 && documentTypes.length === documents.length) {
            const documentFormData = new FormData();
            documentTypes.forEach((type, index) => {
                documentFormData.append(`document_types[${index}]`, type);
                documentFormData.append(`documents[${index}]`, documents[index]);
            });
            await employeesService.uploadDocument(employeeId, documentFormData);
        }

        $('#employeeModal').modal('hide');
        dataTable.ajax.reload();
        Swal.fire('Success!', formData.has('employee_id') ? 'Employee updated successfully.' : 'Employee created successfully.', 'success');
    } catch (error) {
        console.error('Save Employee Error:', error.response || error);
        const errorMessage = error.response?.data?.message || 'Failed to save employee.';
        if (error.response?.headers['x-toastr-message']) {
            toastr.error(error.response.headers['x-toastr-message']);
        }
    } finally {
        btn_loader(btn, false);
    }
};

window.editEmployee = async function (id) {
    try {
        $('#employeeFormContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        const response = await employeesService.edit({ employee_id: id });
        if (response) {
            $('#employeeFormContainer').html(response);
            $('#employeeModalLabel').text('Edit Employee');
            $('#employeeModal').modal('show');
            // Setup document handlers after modal content is loaded
            setupDocumentHandlers();
        } else {
            throw new Error('No data returned from server');
        }
    } catch (error) {
        console.error('Edit Employee Error:', error.response || error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load edit form.', 'error');
    }
};

window.deleteEmployee = async function (id) {
    Swal.fire({
        title: "Are you sure?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await employeesService.delete({ employee_id: id });
                dataTable.ajax.reload();
                Swal.fire('Success!', 'Employee deleted successfully.', 'success');
            } catch (error) {
                console.error('Delete Employee Error:', error.response || error);
                Swal.fire('Error!', error.response?.data?.message || 'Failed to delete employee.', 'error');
            }
        }
    });
};

window.viewEmployee = async function (id) {
    try {
        $('#viewLoading').show();
        $('#viewEmployeeContainer').html('');
        const response = await employeesService.view({ employee_id: id });
        if (response) {
            $('#viewEmployeeContainer').html(response);
            $('#viewLoading').hide();
            $('#viewEmployeeModal').modal('show');
            $('#viewEmployeeModal').trigger('shown.bs.modal');
        } else {
            throw new Error('No data returned from server');
        }
    } catch (error) {
        $('#viewLoading').hide();
        console.error('View Employee Error:', error.response || error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load employee details.', 'error');
    }
};

// Ensure jQuery and Swal are available globally
const $ = window.jQuery || window.$;
const Swal = window.Swal || window.sweetAlert;

window.importEmployees = function (btn) {
    btn = $(btn);
    if (typeof btn_loader === 'undefined') {
        console.warn('btn_loader is not defined. Define it in app.js or pass as a parameter.');
        btn.prop('disabled', true); // Fallback disable
    } else {
        btn_loader(btn, true);
    }

    const form = document.getElementById('importEmployeesForm');
    const formData = new FormData(form);
    const loadingDiv = $('#loading');
    const resultDiv = $('#import-result');
    const progressText = $('#progress');
    const successCount = $('#success-count');
    const errorCount = $('#error-count');
    const errorList = $('#error-list');

    loadingDiv.show();
    resultDiv.hide();
    progressText.text('');
    successCount.text('');
    errorCount.text('');
    errorList.empty();

    $.ajax({
        url: form.action,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        success: function (result) {
            console.log('Raw AJAX response:', result); // Log raw response
            console.log('Response type:', typeof result); // Check type
            console.log('Response stringified:', JSON.stringify(result)); // Stringify for inspection

            loadingDiv.hide();
            resultDiv.show();

            // Enhanced parsing logic
            let successful;
            if (result && typeof result === 'object' && result.hasOwnProperty('successful')) {
                successful = parseInt(result.successful, 10);
            } else if (typeof result === 'string') {
                try {
                    const parsedResult = JSON.parse(result);
                    successful = parsedResult.hasOwnProperty('successful') ? parseInt(parsedResult.successful, 10) : 0;
                } catch (e) {
                    console.error('Failed to parse response as JSON:', e);
                    successful = 0;
                }
            } else {
                console.warn('Unexpected response format:', result);
                successful = 0;
            }
            console.log('Parsed successful value:', successful);

            const errors = result.errors || [];
            const message = result.message || 'An unknown error occurred.';
            const hasErrors = errors.length > 0;

            // Update DOM elements
            successCount.text(`Successfully added ${successful} employees.`);
            errorCount.text(`Errors: ${errors.length}`);

            if (hasErrors) {
                errors.forEach(error => {
                    errorList.append(`<li>${error}</li>`);
                });
            }

            if (successful > 1 && !hasErrors) {
                console.log('Triggering success toast:', { title: 'Success!', text: message, icon: 'success' });
                Swal.fire({
                    title: 'Success!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            } else if (successful > 1 && hasErrors) {
                console.log('Triggering warning toast:', { title: 'Warning!', text: message, icon: 'warning' });
                Swal.fire({
                    title: 'Warning!',
                    text: message,
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            } else if (successful === 0 && hasErrors) {
                console.log('Triggering error toast:', { title: 'Error!', text: message, icon: 'error' });
                Swal.fire({
                    title: 'Error!',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else {
                console.log('Triggering neutral toast:', { title: 'Notice!', text: message, icon: 'info' });
                Swal.fire({
                    title: 'Notice!',
                    text: message,
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function (xhr, status, error) {
            loadingDiv.hide();
            resultDiv.show();
            errorCount.text('An unexpected error occurred.');
            errorList.append(`<li>${error}</li>`);
            Swal.fire({
                title: 'Error!',
                text: 'Something went wrong during import: ' + error,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        },
        complete: function () {
            if (typeof btn_loader === 'undefined') {
                btn.prop('disabled', false);
            } else {
                btn_loader(btn, false);
            }
        }
    });
};

window.previewImage = function (event) {
    const input = event.target;
    const preview = document.getElementById('profile_preview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
};

window.exportEmployees = async function () {
    try {
        Swal.fire({
            title: 'Exporting...',
            text: 'Preparing your employee data for download.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const filters = {
            search: $('#search').val(),
            department: $('#filterDepartment').val(),
            location: $('#filterLocation').val(),
            job_category: $('#filterJobCategory').val()
        };

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/employees/export';
        form.style.display = 'none';

        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        Object.keys(filters).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filters[key] || '';
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();

        setTimeout(() => {
            document.body.removeChild(form);
            Swal.close();
            toastr.success('Export started successfully. Check your downloads.');
        }, 100);
    } catch (error) {
        console.error('Export Employees Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Export Failed',
            text: error.message || 'Failed to export employees. Please try again.',
        });
    }
};
