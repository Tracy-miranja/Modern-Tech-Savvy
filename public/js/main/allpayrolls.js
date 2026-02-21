import RequestClient from "/js/client/RequestClient.js";

const requestClient = new RequestClient();
let selectedPayrolls = [];

const toggleSelectAll = function () {
    const selectAll = document.getElementById('selectAllPayrolls');
    const checkboxes = document.querySelectorAll('.payrollCheckbox');
    checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
    updateSelectedPayrolls();
};

const updateSelectedPayrolls = function () {
    selectedPayrolls = Array.from(document.querySelectorAll('.payrollCheckbox:checked')).map(checkbox => checkbox.value);
};

const filterPayrolls = async function () {
    // Build plain object instead of FormData
    const data = {};

    // Get elements with null checks
    const monthEl = document.getElementById('month');
    const yearEl = document.getElementById('payroll-year');
    const locationEl = document.getElementById('location');
    const departmentEl = document.getElementById('department');
    const jobCategoryEl = document.getElementById('job_category');

    // Only add non-empty values if elements exist
    if (monthEl && monthEl.value) data.month = monthEl.value;
    if (yearEl && yearEl.value) data.year = yearEl.value;
    if (locationEl && locationEl.value) data.location = locationEl.value;
    if (departmentEl && departmentEl.value) data.department = departmentEl.value;
    if (jobCategoryEl && jobCategoryEl.value) data.job_category = jobCategoryEl.value;

    console.log('Filter data being sent:', data);

    try {
        const response = await requestClient.post('/payroll/filter', data);
        console.log('Filter response:', response);

        if (response.data && response.data.html) {
            document.getElementById("pastPayrollsContainer").innerHTML = response.data.html;
        } else {
            console.error('No HTML in response:', response);
        }
    } catch (error) {
        console.error('Filter error:', error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to filter payrolls.', 'error');
    }
};

const clearFilters = function () {
    const form = document.getElementById("payrollFilterForm");
    if (form) {
        form.reset();

        // Reset to current year after clearing
        const yearEl = document.getElementById('payroll-year');
        if (yearEl) {
            const currentYear = new Date().getFullYear();
            yearEl.value = currentYear;
        }
    }
    filterPayrolls();
};

const processPayroll = async function (id = null) {
    if (!id && selectedPayrolls.length !== 1) {
        Swal.fire('Error!', 'Please select exactly one payroll to process.', 'error');
        return;
    }
    const payrollId = id || selectedPayrolls[0];
    try {
        const response = await requestClient.post(`/payroll/${payrollId}/process`, {});
        Swal.fire('Success!', response.message, 'success');
        filterPayrolls();
    } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to process payroll.', 'error');
    }
};

const deletePayroll = async function (id = null) {
    if (!id && selectedPayrolls.length === 0) {
        Swal.fire('Error!', 'Please select at least one payroll to delete.', 'error');
        return;
    }

    const payrollIds = id ? [id] : selectedPayrolls;
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
                for (const payrollId of payrollIds) {
                    const response = await requestClient.post(`/payroll/${payrollId}/delete`, {});
                    if (response.data) {
                        // Update summary
                        const dangerEl = document.querySelector('.text-danger');
                        const mutedEl = document.querySelector('h5.text-muted');

                        if (dangerEl) {
                            dangerEl.textContent = `${response.data.payroll_count} payroll(s) found`;
                        }

                        if (mutedEl) {
                            mutedEl.innerHTML = `
                                <span class="text-danger">${response.data.payroll_count} payroll(s) found</span> |
                                Total Payroll: ${response.data.total_payroll} |
                                Total Net Pay: ${response.data.total_net_pay}
                            `;
                        }
                    }
                }
                Swal.fire('Deleted!', 'Payroll(s) deleted successfully.', 'success');
                filterPayrolls();
            } catch (error) {
                Swal.fire('Error!', error.response?.data?.message || 'Failed to delete payroll.', 'error');
            }
        }
    });
};

const closeMonth = async function (id = null, month = null, year = null) {
    if (!id && selectedPayrolls.length === 0) {
        Swal.fire('Error!', 'Please select at least one payroll to close/open.', 'error');
        return;
    }

    const payrollIds = id ? [id] : selectedPayrolls;
    try {
        for (const payrollId of payrollIds) {
            const response = await requestClient.post(`/payroll/${payrollId}/close`, {
                month: month ? parseInt(month) : undefined,
                year: year ? parseInt(year) : undefined,
            });

            const row = document.querySelector(`.payrollCheckbox[value="${payrollId}"]`)?.closest('tr');
            if (row) {
                const statusCell = row.querySelector('td:nth-child(4)');
                const status = response?.data?.status;

                if (statusCell && status) {
                    statusCell.textContent = status === 'closed' ? 'closed' : 'open';
                } else {
                    console.warn("Status not found in response:", response);
                }
            }
        }

        Swal.fire(
            'Success!',
            payrollIds.length > 1
                ? 'Payroll months updated successfully.'
                : 'Payroll month updated successfully.',
            'success'
        );

        filterPayrolls();
    } catch (error) {
        console.log('Full error object:', error);
        console.log('Error response:', error.response);
        console.log('Error response data:', error.response?.data);

        Swal.fire(
            'Error!',
            error.response?.data?.message || 'Failed to update payroll status.',
            'error'
        );
    }
};

const emailPayslips = async function (id = null) {
    if (!id && selectedPayrolls.length !== 1) {
        Swal.fire('Error!', 'Please select exactly one payroll to send payslips for.', 'error');
        return;
    }

    const payrollId = id || selectedPayrolls[0];
    const payrollRow = document.querySelector(`.payrollCheckbox[value="${payrollId}"]`)?.closest('tr');

    if (!payrollRow) {
        Swal.fire('Error!', 'Could not find payroll row.', 'error');
        return;
    }

    const payrollMonth = payrollRow.querySelector('td:nth-child(2)')?.textContent.trim();
    const businessSlug = window.businessSlug;

    if (!businessSlug) {
        Swal.fire('Error!', 'Business slug not found. Please reload the page.', 'error');
        return;
    }

    const result = await Swal.fire({
        title: "Are you sure?",
        text: `You are about to send payslips for ${payrollMonth}. This action cannot be undone.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, send payslips!",
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`/business/${businessSlug}/payroll/send-payslips`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                payroll_id: payrollId
            })
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server Error (${response.status}): ${errorText}`);
        }

        const data = await response.json();
        Swal.fire('Success!', data.message || `Payslips for ${payrollMonth} have been queued for sending.`, 'success');

        const emailedCell = payrollRow.querySelector('td:nth-child(5)');
        if (emailedCell) {
            emailedCell.textContent = '✔';
        }
    } catch (error) {
        console.error('Error sending payslips:', error);
        Swal.fire('Error!', error.message || 'Failed to send payslips.', 'error');
    }
};

const emailP9 = async function (id = null) {
    if (!id && selectedPayrolls.length === 0) {
        Swal.fire('Error!', 'Please select at least one payroll to email P9 forms.', 'error');
        return;
    }

    const payrollIds = id ? [id] : selectedPayrolls;

    // Show loading spinner
    const spinner = document.getElementById('p9-loading-spinner');
    if (spinner) {
        spinner.style.display = 'block';
    }
    document.body.style.cursor = 'wait';

    try {
        for (const payrollId of payrollIds) {
            await requestClient.post(`/payroll/${payrollId}/email-p9`, {});
        }

        Swal.fire('Success!', 'P9 forms emailed successfully.', 'success');
        filterPayrolls();
    } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to email P9 forms.', 'error');
    } finally {
        // Hide loading spinner
        if (spinner) {
            spinner.style.display = 'none';
        }
        document.body.style.cursor = 'default';
    }
};

const downloadPayroll = function (id = null) {
    if (!id && selectedPayrolls.length !== 1) {
        Swal.fire('Error!', 'Please select exactly one payroll to download.', 'error');
        return;
    }

    const businessSlug = window.businessSlug;
    const payrollId = id || selectedPayrolls[0];

    if (!businessSlug) {
        Swal.fire('Error!', 'Business slug not found. Please reload the page.', 'error');
        console.error('Business slug is undefined');
        return;
    }

    const format = 'xlsx';
    window.location.href = `/business/${businessSlug}/payroll/${payrollId}/download/${format}`;
};
const viewPayroll = function (id) {
    const pathSegments = window.location.pathname.split('/').filter(segment => segment);
    const businessSlug = pathSegments[1];
    if (!businessSlug) return console.error('Could not determine business slug');

    // Grab current filter values
    const month = document.getElementById('month')?.value || '';
    const year = document.getElementById('year')?.value || '';
    const location = document.getElementById('location')?.value || '';
    const department = document.getElementById('department')?.value || '';
    const jobCategory = document.getElementById('job_category')?.value || '';

    // Build query string
    const params = new URLSearchParams({
        month, year, location, department, job_category: jobCategory
    }).toString();

    window.location.href = `/business/${businessSlug}/payroll/${id}?${params}`;
};


// const viewPayroll = function (id) {
//     const currentPath = window.location.pathname;
//     const pathSegments = currentPath.split('/').filter(segment => segment);
//     const businessSlug = pathSegments[1];
//     if (!businessSlug) {
//         console.error('Could not determine business slug from URL');
//         return;
//     }
//     window.location.href = `/business/${businessSlug}/payroll/${id}`;
// };

window.toggleSelectAll = toggleSelectAll;
window.updateSelectedPayrolls = updateSelectedPayrolls;
window.filterPayrolls = filterPayrolls;
window.clearFilters = clearFilters;
window.processPayroll = processPayroll;
window.deletePayroll = deletePayroll;
window.closeMonth = closeMonth;
window.emailPayslips = emailPayslips;
window.emailP9 = emailP9;
window.downloadPayroll = downloadPayroll;
window.viewPayroll = viewPayroll;

// Wait for DOM to be fully loaded before calling filterPayrolls
document.addEventListener('DOMContentLoaded', () => {
    // Check if form exists before filtering
    const form = document.getElementById('payrollFilterForm');
    if (form) {
        filterPayrolls();
    } else {
        console.warn('Payroll filter form not found on page');
    }
});
