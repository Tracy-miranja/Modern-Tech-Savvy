import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import EmployeesService from "/js/client/EmployeesService.js";

const requestClient = new RequestClient();
const employeesService = new EmployeesService(requestClient);


function getCurrentBusinessSlug() {
    const pathParts = window.location.pathname.split('/');
    const businessIndex = pathParts.indexOf('business');
    return businessIndex !== -1 ? pathParts[businessIndex + 1] : null;
}


window.getAllEmployeesList = async function (filters = {}) {
    try {
        const businessSlug = getCurrentBusinessSlug();
        const url = `/business/${businessSlug}/leave-entitlements/employees/filter`;

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(filters)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorData.message || 'Unknown error'}`);
        }

        return await response.json();
    } catch (error) {
        console.error("Error loading employees:", error);
        throw error;
    }
};


window.getLeaveEntitlementsByPeriod = async function (leavePeriodId) {
    try {
        const businessSlug = getCurrentBusinessSlug();
        const url = `/business/${businessSlug}/leave-entitlements/by-period`;

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ leave_period_id: leavePeriodId })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorData.message || 'Unknown error'}`);
        }

        return await response.json();
    } catch (error) {
        console.error("Error loading leave entitlements:", error);
        throw error;
    }
};

window.filterEmployees = async function (filters) {
    try {
        return await employeesService.filter(filters);
    } catch (error) {
        console.error("Error filtering employees:", error);
        throw error;
    }
};
