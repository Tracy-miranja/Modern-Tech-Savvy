// payroll-process.js

(function ($) {
    let availableItems = {
        allowances: [],
        deductions: [],
        reliefs: [],
        loans: [],
        advances: [],
    };

    const taxableAllowances = [
        "House Allowance", "Transport/Commuter Allowance", "Medical Allowance", "Overtime Allowance",
        "Acting Allowance", "Responsibility Allowance", "Hardship Allowance", "Risk Allowance", "Sitting Allowance",
        "Bonuses and Commissions", "Overtime Pay"
    ];
    const nonTaxableAllowances = ["Per Diem", "Mileage Reimbursement", "Meal Allowance", "Entertainment Allowance"];

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    function formatNumber(number) {
        return Number(number).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fetchAvailableItems(callback) {
        $.ajax({
            url: '/payroll/available-items',
            method: 'GET',
            success: function (response) {
                if (response.message === 'success') {
                    availableItems.allowances = response.data.allowances || [];
                    availableItems.deductions = response.data.deductions || [];
                    availableItems.reliefs = response.data.reliefs || [];
                    availableItems.loans = response.data.loans || [];
                    availableItems.advances = response.data.advances || [];
                    if (callback) callback();
                } else {
                    Swal.fire('Error!', 'Failed to fetch available items.', 'error');
                }
            },
            error: function (xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to fetch available items.', 'error');
            }
        });
    }

    function fetchDefaultAmount(type, itemId, callback) {
        $.ajax({
            url: `/payroll/default-amount/${type}/${itemId}`,
            method: 'GET',
            success: function (response) {
                if (response.message === 'success') {
                    callback(response.data.amount || 0, response.data.rate || 0);
                } else {
                    callback(0, 0);
                }
            },
            error: function () {
                callback(0, 0);
            }
        });
    }

    window.configurePayrollSettings = function () {
        const $form = $('#payrollForm');
        const formData = new FormData($form[0]);
        const exemptedEmployeesJson = formData.get('exempted_employees');
        const exemptedEmployees = exemptedEmployeesJson ? JSON.parse(exemptedEmployeesJson) : {};

        $('#allowancesTableBody, #deductionsTableBody, #reliefsTableBody, #absenteeismTableBody, #overtimeTableBody, #loansTableBody, #advancesTableBody')
            .html('<tr><td colspan="3" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');

        fetchAvailableItems(function () {
            $.ajax({
                url: '/payroll/fetch-employees-for-settings',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.message === 'success') {
                        const employees = response.data.employees || [];
                        const settingsId = response.data.settings_id || null;
                        $form.find('input[name="settings_id"]').remove();
                        $form.append(`<input type="hidden" name="settings_id" value="${settingsId || ''}">`);
                        const filteredEmployees = employees.filter(employee =>
                            exemptedEmployees[employee.id] === 0 || !(employee.id in exemptedEmployees)
                        );

                        const populateTable = (tableBodyId, type, generateSubscribed, generateAvailable) => {
                            const $tableBody = $(tableBodyId);
                            let html = '';
                            filteredEmployees.forEach(employee => {
                                html += `
                                    <tr data-employee-id="${employee.id}">
                                        <td>${employee.name}</td>
                                        <td>
                                            <div class="subscribed-items" id="subscribed-${type}-${employee.id}">
                                                ${generateSubscribed(employee)}
                                            </div>
                                        </td>
                                        ${type !== 'overtime' && type !== 'absenteeism' ? `
                                            <td>
                                                <div class="available-items" id="available-${type}-${employee.id}">
                                                    ${generateAvailable(employee)}
                                                </div>
                                            </td>
                                        ` : type === 'absenteeism' ? '' : '<td></td>'}
                                    </tr>
                                `;
                            });
                            $tableBody.html(html || `<tr><td colspan="${type === 'absenteeism' || type === 'overtime' ? 2 : 3}" class="text-center">No employees selected</td></tr>`);
                        };

                        populateTable('#allowancesTableBody', 'allowances', loadAllowanceInputs, loadAvailableAllowances);
                        populateTable('#deductionsTableBody', 'deductions', loadDeductionInputs, loadAvailableDeductions);
                        populateTable('#reliefsTableBody', 'reliefs', loadReliefInputs, loadAvailableReliefs);
                        populateTable('#loansTableBody', 'loans', loadLoanInputs, loadAvailableLoans);
                        populateTable('#advancesTableBody', 'advances', loadAdvanceInputs, loadAvailableAdvances);
                        populateTable('#absenteeismTableBody', 'absenteeism', employee => `
                            <input type="number" class="form-control form-control-sm absenteeism-charge" name="absenteeism_charge[${employee.id}]" value="${employee.absenteeism_charge.amount || 0}" min="0" step="0.01">
                        `, () => '');
                        populateTable('#overtimeTableBody', 'overtime', loadOvertimeInputs, () => '');

                        attachEventListeners();
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to fetch employees.', 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to load employees for settings.', 'error');
                }
            });
        });
    };

    function loadAllowanceInputs(employee) {
        const allowances = Object.values(employee.allowances || {}).reduce((unique, allowance) => {
            if (!unique[allowance.item_id] && allowance.is_active !== false) {
                unique[allowance.item_id] = allowance;
            }
            return unique;
        }, {});
        let html = '';
        if (Object.keys(allowances).length === 0) {
            return '<p>No subscribed allowances.</p>';
        }
        Object.values(allowances).forEach(allowance => {
            const amount = parseFloat(allowance.amount) || 0;
            const rate = parseFloat(allowance.rate) || 0;
            const basis = allowance.calculation_basis || 'gross_pay';
            const isTaxable = taxableAllowances.includes(allowance.item_name) || (!nonTaxableAllowances.includes(allowance.item_name) && allowance.is_taxable !== false);
            const displayName = rate > 0 && basis
                ? `${allowance.item_name} (${rate.toFixed(2)}% of ${basis})`
                : `${allowance.item_name} (${formatNumber(amount)})`;
            html += `
            <div class="form-group mb-2" id="allowances-subscribed-${employee.id}-${allowance.item_id}">
                <div class="form-check">
                    <input class="form-check-input allowance-checkbox" type="checkbox"
                           name="allowances[${employee.id}][${allowance.item_id}]" value="1" checked
                           data-allowance-id="${allowance.item_id}">
                    <label class="form-check-label">${displayName} ${isTaxable ? '[Taxable]' : '[Non-Taxable]'}</label>
                </div>
                ${rate > 0 ? `
                    <input type="number" class="form-control form-control-sm allowance-rate mt-1"
                           name="allowance_rates[${employee.id}][${allowance.item_id}]" value="${rate}"
                           min="0" step="0.01" placeholder="Rate (%)" onchange="updateAllowanceDisplay(this, ${employee.id}, ${allowance.item_id}, '${basis}', ${isTaxable})">
                ` : `
                    <input type="number" class="form-control form-control-sm allowance-amount amount-input mt-1"
                           name="allowance_amounts[${employee.id}][${allowance.item_id}]" value="${amount || 0}"
                           min="0" step="0.01" placeholder="Amount" onchange="updateAllowanceDisplay(this, ${employee.id}, ${allowance.item_id}, '${basis}', ${isTaxable})">
                `}
            </div>`;
        });
        return html;
    }

    function loadAvailableAllowances(employee) {
        const subscribedIds = Object.values(employee.allowances || {}).filter(a => a.is_active !== false).map(a => a.item_id);
        const available = availableItems.allowances.filter(item => !subscribedIds.includes(item.id));
        let html = '';
        available.forEach(item => {
            const amount = parseFloat(item.amount) || 0;
            const rate = parseFloat(item.rate) || 0;
            const basis = item.calculation_basis || 'gross_pay';
            const isTaxable = taxableAllowances.includes(item.name) || (!nonTaxableAllowances.includes(item.name) && item.is_taxable !== false);
            const displayValue = rate > 0 && basis
                ? `${parseFloat(rate).toFixed(2)}% of ${basis}`
                : formatNumber(amount);
            html += `
            <div class="form-check mb-2" id="allowances-available-${employee.id}-${item.id}">
                <input class="form-check-input allowance-checkbox" type="checkbox"
                       name="allowances[${employee.id}][${item.id}]" value="1"
                       data-allowance-id="${item.id}" onchange="handleAllowanceToggle(this, ${employee.id}, ${item.id})">
                <label class="form-check-label">${item.name} (${displayValue}) ${isTaxable ? '[Taxable]' : '[Non-Taxable]'}</label>
            </div>`;
        });
        return html || '<p>No available allowances.</p>';
    }

    function handleAllowanceToggle(checkbox, employeeId, itemId) {
        const $checkbox = $(checkbox);
        const $subscribedContainer = $(`#subscribed-allowances-${employeeId}`);
        const $availableContainer = $(`#available-allowances-${employeeId}`);
        const isChecked = $checkbox.is(':checked');
        const item = availableItems.allowances.find(i => i.id === itemId) || {};
        const defaultAmount = parseFloat(item.amount) || 0;
        const defaultRate = parseFloat(item.rate) || 0;
        const basis = item.calculation_basis || 'gross_pay';
        const isTaxable = taxableAllowances.includes(item.name) || (!nonTaxableAllowances.includes(item.name) && item.is_taxable !== false);
        const itemName = item.name;

        $(`#allowances-subscribed-${employeeId}-${itemId}`).remove();
        $(`#allowances-available-${employeeId}-${itemId}`).remove();

        if (isChecked) {
            const subscribedHtml = `
                <div class="form-group mb-2" id="allowances-subscribed-${employeeId}-${itemId}">
                    <div class="form-check">
                        <input class="form-check-input allowance-checkbox" type="checkbox"
                               name="allowances[${employeeId}][${itemId}]" value="1" checked
                               data-allowance-id="${itemId}">
                        <label class="form-check-label">${itemName} (${defaultRate > 0 ? `${defaultRate.toFixed(2)}% of ${basis}` : formatNumber(defaultAmount)}) ${isTaxable ? '[Taxable]' : '[Non-Taxable]'}</label>
                    </div>
                    ${defaultRate > 0 ? `
                        <input type="number" class="form-control form-control-sm allowance-rate mt-1"
                               name="allowance_rates[${employeeId}][${itemId}]" value="${defaultRate}"
                               min="0" step="0.01" placeholder="Rate (%)" onchange="updateAllowanceDisplay(this, ${employeeId}, ${itemId}, '${basis}', ${isTaxable})">
                    ` : `
                        <input type="number" class="form-control form-control-sm allowance-amount amount-input mt-1"
                               name="allowance_amounts[${employeeId}][${itemId}]" value="${defaultAmount || 0}"
                               min="0" step="0.01" placeholder="Amount" onchange="updateAllowanceDisplay(this, ${employeeId}, ${itemId}, '${basis}', ${isTaxable})">
                    `}
                </div>`;
            $subscribedContainer.append(subscribedHtml);
            $subscribedContainer.find('p').remove();
        } else {
            const availableHtml = `
                <div class="form-check mb-2" id="allowances-available-${employeeId}-${itemId}">
                    <input class="form-check-input allowance-checkbox" type="checkbox"
                           name="allowances[${employeeId}][${itemId}]" value="1"
                           data-allowance-id="${itemId}" onchange="handleAllowanceToggle(this, ${employeeId}, ${itemId})">
                    <label class="form-check-label">${itemName} (${defaultRate > 0 ? `${defaultRate.toFixed(2)}% of ${basis}` : formatNumber(defaultAmount)}) ${isTaxable ? '[Taxable]' : '[Non-Taxable]'}</label>
                </div>`;
            $availableContainer.append(availableHtml);
            if ($subscribedContainer.find('.form-group').length === 0) {
                $subscribedContainer.html('<p>No subscribed allowances.</p>');
            }
        }
    }

    function updateAllowanceDisplay(input, employeeId, itemId, basis, isTaxable) {
        const value = parseFloat(input.value) || 0;
        const $label = $(`#allowances-subscribed-${employeeId}-${itemId} .form-check-label`);
        const itemName = $label.text().split(' (')[0];
        const displayText = input.classList.contains('allowance-rate')
            ? `${itemName} (${value.toFixed(2)}% of ${basis}) ${isTaxable ? '[Taxable]' : '[Non-Taxable]'}`
            : `${itemName} (${formatNumber(value)}) ${isTaxable ? '[Taxable]' : '[Non-Taxable]'}`;
        $label.text(displayText);
    }

    function loadDeductionInputs(employee) {
        const deductions = Object.values(employee.deductions || {}).reduce((unique, deduction) => {
            if (!unique[deduction.item_id] && deduction.is_active !== false) {
                unique[deduction.item_id] = deduction;
            }
            return unique;
        }, {});
        let html = '';
        if (Object.keys(deductions).length === 0) {
            return '<p>No subscribed deductions.</p>';
        }
        Object.values(deductions).forEach(deduction => {
            const amount = parseFloat(deduction.amount) || 0;
            const rate = parseFloat(deduction.rate) || 0;
            const basis = deduction.calculation_basis || 'gross_pay';
            const isStatutory = deduction.is_statutory;
            const displayName = rate > 0 && basis
                ? `${deduction.item_name} (${rate.toFixed(2)}% of ${basis})`
                : `${deduction.item_name} (${formatNumber(amount)})`;
            html += `
            <div class="form-group mb-2" id="deductions-subscribed-${employee.id}-${deduction.item_id}">
                <div class="form-check">
                    <input class="form-check-input deduction-checkbox" type="checkbox"
                           name="deductions[${employee.id}][${deduction.item_id}]" value="1" checked
                           data-deduction-id="${deduction.item_id}">
                    <label class="form-check-label">${displayName} ${isStatutory ? '[Statutory]' : '[Optional]'}</label>
                </div>
                ${rate > 0 ? `
                    <input type="number" class="form-control form-control-sm deduction-rate mt-1"
                           name="deduction_rates[${employee.id}][${deduction.item_id}]" value="${rate}"
                           min="0" step="0.01" placeholder="Rate (%)" onchange="updateDeductionDisplay(this, ${employee.id}, ${deduction.item_id}, '${basis}', ${isStatutory})">
                ` : `
                    <input type="number" class="form-control form-control-sm deduction-amount amount-input mt-1"
                           name="deduction_amounts[${employee.id}][${deduction.item_id}]" value="${amount || 0}"
                           min="0" step="0.01" placeholder="Amount" onchange="updateDeductionDisplay(this, ${employee.id}, ${deduction.item_id}, '${basis}', ${isStatutory})">
                `}
            </div>`;
        });
        return html;
    }

    function loadAvailableDeductions(employee) {
        const subscribedIds = Object.values(employee.deductions || {}).filter(d => d.is_active !== false).map(d => d.item_id);
        const available = availableItems.deductions.filter(item => !subscribedIds.includes(item.id));
        let html = '';
        available.forEach(item => {
            const displayValue = item.rate > 0 && item.calculation_basis
                ? `${parseFloat(item.rate).toFixed(2)}% of ${item.calculation_basis}`
                : formatNumber(item.amount || 0);
            html += `
            <div class="form-check mb-2" id="deductions-available-${employee.id}-${item.id}">
                <input class="form-check-input deduction-checkbox" type="checkbox"
                       name="deductions[${employee.id}][${item.id}]" value="1"
                       data-deduction-id="${item.id}" onchange="handleDeductionToggle(this, ${employee.id}, ${item.id})">
                <label class="form-check-label">${item.name} (${displayValue}) ${item.is_statutory ? '[Statutory]' : '[Optional]'}</label>
            </div>`;
        });
        return html || '<p>No available deductions.</p>';
    }

    function handleDeductionToggle(checkbox, employeeId, itemId) {
        const $checkbox = $(checkbox);
        const $subscribedContainer = $(`#subscribed-deductions-${employeeId}`);
        const $availableContainer = $(`#available-deductions-${employeeId}`);
        const isChecked = $checkbox.is(':checked');
        const item = availableItems.deductions.find(i => i.id === itemId) || {};
        const defaultAmount = parseFloat(item.amount) || 0;
        const defaultRate = parseFloat(item.rate) || 0;
        const basis = item.calculation_basis || 'gross_pay';
        const isStatutory = item.is_statutory;
        const itemName = item.name;

        $(`#deductions-subscribed-${employeeId}-${itemId}`).remove();
        $(`#deductions-available-${employeeId}-${itemId}`).remove();

        if (isChecked) {
            const subscribedHtml = `
                <div class="form-group mb-2" id="deductions-subscribed-${employeeId}-${itemId}">
                    <div class="form-check">
                        <input class="form-check-input deduction-checkbox" type="checkbox"
                               name="deductions[${employeeId}][${itemId}]" value="1" checked
                               data-deduction-id="${itemId}">
                        <label class="form-check-label">${itemName} (${defaultRate > 0 ? `${defaultRate.toFixed(2)}% of ${basis}` : formatNumber(defaultAmount)}) ${isStatutory ? '[Statutory]' : '[Optional]'}</label>
                    </div>
                    ${defaultRate > 0 ? `
                        <input type="number" class="form-control form-control-sm deduction-rate mt-1"
                               name="deduction_rates[${employeeId}][${itemId}]" value="${defaultRate}"
                               min="0" step="0.01" placeholder="Rate (%)" onchange="updateDeductionDisplay(this, ${employeeId}, ${itemId}, '${basis}', ${isStatutory})">
                    ` : `
                        <input type="number" class="form-control form-control-sm deduction-amount amount-input mt-1"
                               name="deduction_amounts[${employeeId}][${itemId}]" value="${defaultAmount || 0}"
                               min="0" step="0.01" placeholder="Amount" onchange="updateDeductionDisplay(this, ${employeeId}, ${itemId}, '${basis}', ${isStatutory})">
                    `}
                </div>`;
            $subscribedContainer.append(subscribedHtml);
            $subscribedContainer.find('p').remove();
        } else {
            const availableHtml = `
                <div class="form-check mb-2" id="deductions-available-${employeeId}-${itemId}">
                    <input class="form-check-input deduction-checkbox" type="checkbox"
                           name="deductions[${employeeId}][${itemId}]" value="1"
                           data-deduction-id="${itemId}" onchange="handleDeductionToggle(this, ${employeeId}, ${itemId})">
                    <label class="form-check-label">${itemName} (${defaultRate > 0 ? `${defaultRate.toFixed(2)}% of ${basis}` : formatNumber(defaultAmount)}) ${isStatutory ? '[Statutory]' : '[Optional]'}</label>
                </div>`;
            $availableContainer.append(availableHtml);
            if ($subscribedContainer.find('.form-group').length === 0) {
                $subscribedContainer.html('<p>No subscribed deductions.</p>');
            }
        }
    }

    function updateDeductionDisplay(input, employeeId, itemId, basis, isStatutory) {
        const value = parseFloat(input.value) || 0;
        const $label = $(`#deductions-subscribed-${employeeId}-${itemId} .form-check-label`);
        const itemName = $label.text().split(' (')[0];
        const displayText = input.classList.contains('deduction-rate')
            ? `${itemName} (${value.toFixed(2)}% of ${basis}) ${isStatutory ? '[Statutory]' : '[Optional]'}`
            : `${itemName} (${formatNumber(value)}) ${isStatutory ? '[Statutory]' : '[Optional]'}`;
        $label.text(displayText);
    }

    function loadReliefInputs(employee) {
        const reliefs = Object.values(employee.reliefs || {}).reduce((unique, relief) => {
            if (!unique[relief.item_id] && relief.is_active !== false) {
                unique[relief.item_id] = relief;
            }
            return unique;
        }, {});
        let html = '';
        // Always include Personal Relief with fixed value of 2400
        html += `
            <div class="form-group mb-2" id="reliefs-subscribed-${employee.id}-1">
                <div class="form-check">
                    <input class="form-check-input relief-checkbox" type="checkbox"
                           name="reliefs[${employee.id}][1]" value="1" checked disabled
                           data-relief-id="1">
                    <label class="form-check-label">Personal Relief (KES ${formatNumber(2400)})</label>
                </div>
            </div>
        `;
        Object.values(reliefs).forEach(relief => {
            if (relief.item_id === 1) return; // Skip Personal Relief
            const amount = parseFloat(relief.amount) || 0;
            const item = availableItems.reliefs.find(i => i.id === relief.item_id) || {};
            const maxLimit = item.limit ? parseFloat(item.limit) : null;
            const isInsurance = item.name === 'Insurance Relief';
            const displayName = `${relief.item_name} (${formatNumber(amount)})`;
            html += `
            <div class="form-group mb-2" id="reliefs-subscribed-${employee.id}-${relief.item_id}">
                <div class="form-check">
                    <input class="form-check-input relief-checkbox" type="checkbox"
                           name="reliefs[${employee.id}][${relief.item_id}]" value="1" checked
                           data-relief-id="${relief.item_id}">
                    <label class="form-check-label">${displayName}</label>
                </div>
                ${relief.item_id === 5 ? '' : `
                    <input type="number" class="form-control form-control-sm relief-amount amount-input mt-1"
                           name="relief_amounts[${employee.id}][${relief.item_id}]" value="${amount || 0}"
                           min="0" step="0.01" ${maxLimit ? `max="${maxLimit}"` : ''}
                           placeholder="${isInsurance ? 'Premium Paid' : 'Amount'}"
                           onchange="updateReliefAmount(this, ${employee.id}, ${relief.item_id}, ${maxLimit || 'null'})">
                `}
            </div>`;
        });
        return html || '<p>No additional reliefs subscribed.</p>';
    }

    function loadAvailableReliefs(employee) {
        const subscribedIds = Object.values(employee.reliefs || {}).filter(r => r.is_active !== false).map(r => r.item_id).filter(id => id !== 1);
        const available = availableItems.reliefs.filter(item => !subscribedIds.includes(item.id) && item.id !== 1);
        let html = '';
        available.forEach(item => {
            const maxLimit = item.limit ? parseFloat(item.limit) : null;
            const defaultAmount = parseFloat(item.amount) || 0;
            const displayValue = item.id === 5 ? '100% Tax Exemption' : (item.computation_method === 'percentage' ? `${item.percentage_of_amount}% of Input` : formatNumber(defaultAmount));
            html += `
            <div class="form-check mb-2" id="reliefs-available-${employee.id}-${item.id}">
                <input class="form-check-input relief-checkbox" type="checkbox"
                       name="reliefs[${employee.id}][${item.id}]" value="1"
                       data-relief-id="${item.id}" onchange="handleReliefToggle(this, ${employee.id}, ${item.id})">
                <label class="form-check-label">${item.name} (${displayValue}${maxLimit ? `, Max KES ${formatNumber(maxLimit)}` : ''})</label>
            </div>`;
        });
        return html || '<p>No additional reliefs available.</p>';
    }

    function handleReliefToggle(checkbox, employeeId, itemId) {
        const $checkbox = $(checkbox);
        const $subscribedContainer = $(`#subscribed-reliefs-${employeeId}`);
        const $availableContainer = $(`#available-reliefs-${employeeId}`);
        const isChecked = $checkbox.is(':checked');
        const item = availableItems.reliefs.find(i => i.id === itemId) || {};
        const maxLimit = item.limit ? parseFloat(item.limit) : null;
        const isInsurance = item.name === 'Insurance Relief';
        const defaultAmount = item.id === 5 ? 0 : (parseFloat(item.amount) || 0);

        $(`#reliefs-subscribed-${employeeId}-${itemId}`).remove();
        $(`#reliefs-available-${employeeId}-${itemId}`).remove();

        if (isChecked) {
            const subscribedHtml = `
                <div class="form-group mb-2" id="reliefs-subscribed-${employeeId}-${itemId}">
                    <div class="form-check">
                        <input class="form-check-input relief-checkbox" type="checkbox"
                               name="reliefs[${employeeId}][${itemId}]" value="1" checked
                               data-relief-id="${itemId}">
                        <label class="form-check-label">${item.name} (${formatNumber(defaultAmount)})</label>
                    </div>
                    ${itemId === 5 ? '' : `
                        <input type="number" class="form-control form-control-sm relief-amount amount-input mt-1"
                               name="relief_amounts[${employeeId}][${itemId}]" value="${defaultAmount || 0}"
                               min="0" step="0.01" ${maxLimit ? `max="${maxLimit}"` : ''}
                               placeholder="${isInsurance ? 'Premium Paid' : 'Amount'}"
                               onchange="updateReliefAmount(this, ${employeeId}, ${itemId}, ${maxLimit || 'null'})">
                    `}
                </div>`;
            $subscribedContainer.append(subscribedHtml);
            if ($subscribedContainer.find('p').length > 0 && $subscribedContainer.find('.form-group').length > 1) {
                $subscribedContainer.find('p').remove();
            }
        } else {
            const availableHtml = `
                <div class="form-check mb-2" id="reliefs-available-${employeeId}-${itemId}">
                    <input class="form-check-input relief-checkbox" type="checkbox"
                           name="reliefs[${employeeId}][${itemId}]" value="1"
                           data-relief-id="${itemId}" onchange="handleReliefToggle(this, ${employeeId}, ${itemId})">
                    <label class="form-check-label">${item.name} (${item.id === 5 ? '100% Tax Exemption' : formatNumber(defaultAmount)}${maxLimit ? `, Max KES ${formatNumber(maxLimit)}` : ''})</label>
                </div>`;
            $availableContainer.append(availableHtml);
        }
    }

    function updateReliefAmount(input, employeeId, itemId, maxLimit) {
        let value = parseFloat(input.value) || 0;
        if (maxLimit && value > maxLimit) {
            value = maxLimit;
            input.value = value;
            Swal.fire('Warning', `Maximum limit for this relief is KES ${formatNumber(maxLimit)}.`, 'warning');
        }
        const $label = $(`#reliefs-subscribed-${employeeId}-${itemId} .form-check-label`);
        const itemName = $label.text().split(' (')[0];
        $label.text(`${itemName} (${formatNumber(value)})`);
    }

    function loadOvertimeInputs(employee) {
        const overtimes = Object.values(employee.overtimes || {});
        let html = '';
        if (overtimes.length === 0) {
            return '<p>No overtime available.</p>';
        }
        overtimes.forEach(overtime => {
            const hours = parseFloat(overtime.amount) || 0;
            const isSelected = overtime.is_active !== false;
            const formattedDate = formatDate(overtime.item_name.split('on ')[1] || new Date());
            const displayName = `Overtime on ${formattedDate} (${formatNumber(hours)} hrs)`;
            html += `
            <div class="form-group mb-2" id="overtime-${employee.id}-${overtime.item_id}">
                <div class="form-check">
                    <input class="form-check-input overtime-checkbox" type="checkbox"
                           name="overtime[${employee.id}][${overtime.item_id}]" value="1"
                           ${isSelected ? 'checked' : ''} data-overtime-id="${overtime.item_id}">
                    <label class="form-check-label">${displayName}</label>
                </div>
                <div class="mt-1">
                    <input type="number" class="form-control form-control-sm overtime-hours"
                           name="overtime_hours[${employee.id}][${overtime.item_id}]" value="${hours || 0}"
                           min="0" step="0.1" placeholder="Hours Worked"
                           onchange="updateOvertimeDisplay(this, ${employee.id}, ${overtime.item_id})">
                </div>
            </div>`;
        });
        return html;
    }

    function updateOvertimeDisplay(input, employeeId, itemId) {
        const hours = parseFloat(input.value) || 0;
        const $label = $(`#overtime-${employeeId}-${itemId} .form-check-label`);
        const itemName = $label.text().split(' (')[0];
        $label.text(`${itemName} (${formatNumber(hours)} hrs)`);
    }

    function loadLoanInputs(employee) {
        const loans = Object.values(employee.loans || {}).reduce((unique, loan) => {
            if (!unique[loan.item_id] && loan.is_active !== false) {
                unique[loan.item_id] = loan;
            }
            return unique;
        }, {});
        let html = '';
        if (Object.keys(loans).length === 0) {
            return '<p>No subscribed loans.</p>';
        }
        Object.values(loans).forEach(loan => {
            const remaining = parseFloat(loan.amount) || 0;
            const amountToRecover = parseFloat(loan.amount_to_recover) || remaining;
            const formattedDate = formatDate(loan.item_name.split('started ')[1] || new Date());
            if (remaining > 0) {
                const displayName = `Loan on ${formattedDate} (Remaining: ${formatNumber(remaining)})`;
                html += `
                <div class="form-group mb-2" id="loans-subscribed-${employee.id}-${loan.item_id}">
                    <div class="form-check">
                        <input class="form-check-input loan-checkbox" type="checkbox"
                               name="loans[${employee.id}][${loan.item_id}]" value="1" checked
                               data-loan-id="${loan.item_id}" data-max-amount="${remaining}">
                        <label class="form-check-label">${displayName}</label>
                    </div>
                    <input type="number" class="form-control form-control-sm loan-amount amount-input mt-1"
                           name="loan_amounts[${employee.id}][${loan.item_id}]" value="${amountToRecover || remaining}"
                           min="0" max="${remaining}" step="0.01" placeholder="Amount to Recover"
                           onchange="updateLoanDisplay(this, ${employee.id}, ${loan.item_id}, ${remaining})">
                </div>`;
            }
        });
        return html;
    }

    function loadAvailableLoans(employee) {
        const subscribedIds = Object.values(employee.loans || {}).filter(l => l.is_active !== false).map(l => l.item_id);
        const available = availableItems.loans.filter(item => item.employee_id === employee.id && !subscribedIds.includes(item.id));
        let html = '';
        available.forEach(item => {
            const remaining = parseFloat(item.remaining) || 0;
            const formattedDate = formatDate(item.start_date);
            const displayName = `Loan on ${formattedDate} (Remaining: ${formatNumber(remaining)})`;
            html += `
            <div class="form-check mb-2" id="loans-available-${employee.id}-${item.id}">
                <input class="form-check-input loan-checkbox" type="checkbox"
                       name="loans[${employee.id}][${item.id}]" value="1"
                       data-loan-id="${item.id}" data-max-amount="${remaining}" onchange="handleLoanToggle(this, ${employee.id}, ${item.id})">
                <label class="form-check-label">${displayName}</label>
            </div>`;
        });
        return html || '<p>No available loans.</p>';
    }

    function handleLoanToggle(checkbox, employeeId, itemId) {
        const $checkbox = $(checkbox);
        const $subscribedContainer = $(`#subscribed-loans-${employeeId}`);
        const $availableContainer = $(`#available-loans-${employeeId}`);
        const isChecked = $checkbox.is(':checked');
        const item = availableItems.loans.find(i => i.id === itemId) || {};
        const remaining = parseFloat(item.remaining) || 0;
        const formattedDate = formatDate(item.start_date);

        $(`#loans-subscribed-${employeeId}-${itemId}`).remove();
        $(`#loans-available-${employeeId}-${itemId}`).remove();

        if (isChecked) {
            const subscribedHtml = `
                <div class="form-group mb-2" id="loans-subscribed-${employeeId}-${itemId}">
                    <div class="form-check">
                        <input class="form-check-input loan-checkbox" type="checkbox"
                               name="loans[${employeeId}][${itemId}]" value="1" checked
                               data-loan-id="${itemId}" data-max-amount="${remaining}">
                        <label class="form-check-label">Loan on ${formattedDate} (Remaining: ${formatNumber(remaining)})</label>
                    </div>
                    <input type="number" class="form-control form-control-sm loan-amount amount-input mt-1"
                           name="loan_amounts[${employeeId}][${itemId}]" value="${remaining}"
                           min="0" max="${remaining}" step="0.01" placeholder="Amount to Recover"
                           onchange="updateLoanDisplay(this, ${employeeId}, ${itemId}, ${remaining})">
                </div>`;
            $subscribedContainer.append(subscribedHtml);
            $subscribedContainer.find('p').remove();
        } else {
            const availableHtml = `
                <div class="form-check mb-2" id="loans-available-${employeeId}-${itemId}">
                    <input class="form-check-input loan-checkbox" type="checkbox"
                           name="loans[${employeeId}][${itemId}]" value="1"
                           data-loan-id="${itemId}" data-max-amount="${remaining}" onchange="handleLoanToggle(this, ${employeeId}, ${itemId})">
                    <label class="form-check-label">Loan on ${formattedDate} (Remaining: ${formatNumber(remaining)})</label>
                </div>`;
            $availableContainer.append(availableHtml);
            if ($subscribedContainer.find('.form-group').length === 0) {
                $subscribedContainer.html('<p>No subscribed loans.</p>');
            }
        }
    }

    function updateLoanDisplay(input, employeeId, itemId, maxAmount) {
        let value = parseFloat(input.value) || 0;
        if (value > maxAmount) {
            value = maxAmount;
            input.value = value;
            Swal.fire('Warning', `Maximum recoverable amount is KES ${formatNumber(maxAmount)}.`, 'warning');
        }
        const $label = $(`#loans-subscribed-${employeeId}-${itemId} .form-check-label`);
        const itemName = $label.text().split(' (')[0];
        $label.text(`${itemName} (Remaining: ${formatNumber(maxAmount)})`);
    }

    function loadAdvanceInputs(employee) {
        const advances = Object.values(employee.advances || {}).reduce((unique, advance) => {
            if (!unique[advance.item_id] && advance.is_active !== false) {
                unique[advance.item_id] = advance;
            }
            return unique;
        }, {});
        let html = '';
        if (Object.keys(advances).length === 0) {
            return '<p>No subscribed advances.</p>';
        }
        Object.values(advances).forEach(advance => {
            const amount = parseFloat(advance.amount) || 0;
            const amountToRecover = parseFloat(advance.amount_to_recover) || amount;
            const formattedDate = formatDate(advance.item_name.split('on ')[1] || new Date());
            const displayName = `Advance on ${formattedDate} (Amount: ${formatNumber(amount)})`;
            html += `
            <div class="form-group mb-2" id="advances-subscribed-${employee.id}-${advance.item_id}">
                <div class="form-check">
                    <input class="form-check-input advance-checkbox" type="checkbox"
                           name="advances[${employee.id}][${advance.item_id}]" value="1" checked
                           data-advance-id="${advance.item_id}" data-max-amount="${amount}">
                    <label class="form-check-label">${displayName}</label>
                </div>
                <input type="number" class="form-control form-control-sm advance-amount amount-input mt-1"
                       name="advance_amounts[${employee.id}][${advance.item_id}]" value="${amountToRecover || amount}"
                       min="0" max="${amount}" step="0.01" placeholder="Amount to Recover"
                       onchange="updateAdvanceDisplay(this, ${employee.id}, ${advance.item_id}, ${amount})">
            </div>`;
        });
        return html;
    }

    function loadAvailableAdvances(employee) {
        const subscribedIds = Object.values(employee.advances || {}).filter(a => a.is_active !== false).map(a => a.item_id);
        const available = availableItems.advances.filter(item => item.employee_id === employee.id && !subscribedIds.includes(item.id));
        let html = '';
        available.forEach(item => {
            const amount = parseFloat(item.amount) || 0;
            const formattedDate = formatDate(item.date);
            const displayName = `Advance on ${formattedDate} (Amount: ${formatNumber(amount)})`;
            html += `
            <div class="form-check mb-2" id="advances-available-${employee.id}-${item.id}">
                <input class="form-check-input advance-checkbox" type="checkbox"
                       name="advances[${employee.id}][${item.id}]" value="1"
                       data-advance-id="${item.id}" data-max-amount="${amount}" onchange="handleAdvanceToggle(this, ${employee.id}, ${item.id})">
                <label class="form-check-label">${displayName}</label>
            </div>`;
        });
        return html || '<p>No available advances.</p>';
    }

    function handleAdvanceToggle(checkbox, employeeId, itemId) {
        const $checkbox = $(checkbox);
        const $subscribedContainer = $(`#subscribed-advances-${employeeId}`);
        const $availableContainer = $(`#available-advances-${employeeId}`);
        const isChecked = $checkbox.is(':checked');
        const item = availableItems.advances.find(i => i.id === itemId) || {};
        const amount = parseFloat(item.amount) || 0;
        const formattedDate = formatDate(item.date);

        $(`#advances-subscribed-${employeeId}-${itemId}`).remove();
        $(`#advances-available-${employeeId}-${itemId}`).remove();

        if (isChecked) {
            const subscribedHtml = `
                <div class="form-group mb-2" id="advances-subscribed-${employeeId}-${itemId}">
                    <div class="form-check">
                        <input class="form-check-input advance-checkbox" type="checkbox"
                               name="advances[${employeeId}][${itemId}]" value="1" checked
                               data-advance-id="${itemId}" data-max-amount="${amount}">
                        <label class="form-check-label">Advance on ${formattedDate} (Amount: ${formatNumber(amount)})</label>
                    </div>
                    <input type="number" class="form-control form-control-sm advance-amount amount-input mt-1"
                           name="advance_amounts[${employeeId}][${itemId}]" value="${amount}"
                           min="0" max="${amount}" step="0.01" placeholder="Amount to Recover"
                           onchange="updateAdvanceDisplay(this, ${employeeId}, ${itemId}, ${amount})">
                </div>`;
            $subscribedContainer.append(subscribedHtml);
            $subscribedContainer.find('p').remove();
        } else {
            const availableHtml = `
                <div class="form-check mb-2" id="advances-available-${employeeId}-${itemId}">
                    <input class="form-check-input advance-checkbox" type="checkbox"
                           name="advances[${employeeId}][${itemId}]" value="1"
                           data-advance-id="${itemId}" data-max-amount="${amount}" onchange="handleAdvanceToggle(this, ${employeeId}, ${itemId})">
                    <label class="form-check-label">Advance on ${formattedDate} (Amount: ${formatNumber(amount)})</label>
                </div>`;
            $availableContainer.append(availableHtml);
            if ($subscribedContainer.find('.form-group').length === 0) {
                $subscribedContainer.html('<p>No subscribed advances.</p>');
            }
        }
    }

    function updateAdvanceDisplay(input, employeeId, itemId, maxAmount) {
        let value = parseFloat(input.value) || 0;
        if (value > maxAmount) {
            value = maxAmount;
            input.value = value;
            Swal.fire('Warning', `Maximum recoverable amount is KES ${formatNumber(maxAmount)}.`, 'warning');
        }
        const $label = $(`#advances-subscribed-${employeeId}-${itemId} .form-check-label`);
        const itemName = $label.text().split(' (')[0];
        $label.text(`${itemName} (Amount: ${formatNumber(maxAmount)})`);
    }

    function attachEventListeners() {
        $('#payrollSettingsSection').off('change', '.allowance-checkbox').on('change', '.allowance-checkbox', function () {
            const employeeId = $(this).closest('tr').data('employee-id');
            const itemId = $(this).data('allowance-id');
            handleAllowanceToggle(this, employeeId, itemId);
        });

        $('#payrollSettingsSection').off('change', '.deduction-checkbox').on('change', '.deduction-checkbox', function () {
            const employeeId = $(this).closest('tr').data('employee-id');
            const itemId = $(this).data('deduction-id');
            handleDeductionToggle(this, employeeId, itemId);
        });

        $('#payrollSettingsSection').off('change', '.relief-checkbox').on('change', '.relief-checkbox', function () {
            const employeeId = $(this).closest('tr').data('employee-id');
            const itemId = $(this).data('relief-id');
            handleReliefToggle(this, employeeId, itemId);
        });

        $('#payrollSettingsSection').off('change', '.loan-checkbox').on('change', '.loan-checkbox', function () {
            const employeeId = $(this).closest('tr').data('employee-id');
            const itemId = $(this).data('loan-id');
            handleLoanToggle(this, employeeId, itemId);
        });

        $('#payrollSettingsSection').off('change', '.advance-checkbox').on('change', '.advance-checkbox', function () {
            const employeeId = $(this).closest('tr').data('employee-id');
            const itemId = $(this).data('advance-id');
            handleAdvanceToggle(this, employeeId, itemId);
        });

        $('#payrollSettingsSection').off('change', '.overtime-checkbox').on('change', '.overtime-checkbox', function () {
            const employeeId = $(this).closest('tr').data('employee-id');
            const itemId = $(this).data('overtime-id');
            handleItemToggle(this, 'overtime', employeeId, itemId, '', '');
        });
    }

    function handleItemToggle(checkbox, type, employeeId, itemId, itemName, additionalInfo) {
        const $checkbox = $(checkbox);
        const isChecked = $checkbox.is(':checked');
        $checkbox.prop('checked', isChecked);
    }

window.savePayrollSettings = function () {
    const year = $('#payrollForm #year').val() || $('select[name="year"]').val();
    const month = $('#payrollForm #month').val() || $('select[name="month"]').val();

    if (!year || !month) {
        Swal.fire('Error!', 'Year and Month are required.', 'error');
        return;
    }

    const employeesArray = [];

    $('#payrollSettingsSection tr[data-employee-id]').each(function () {
        const employeeId = parseInt($(this).data('employee-id'));
        if (!employeeId) return;

        const allowances = {};
        const deductions = {};
        const reliefs = {};
        const overtime = {};
        const loans = {};
        const advances = {};
        let absenteeismCharge = 0;

        const empIdStr = employeeId.toString();

        // ALLOWANCES
        $(`#subscribed-allowances-${empIdStr} .allowance-checkbox:checked`).each(function () {
            const itemId = $(this).data('allowance-id');
            if (!itemId) return;
            const $group = $(this).closest('.form-group');
            const $rateInput = $group.find('.allowance-rate');
            const $amountInput = $group.find('.allowance-amount');
            const value = parseFloat($rateInput.length ? $rateInput.val() : $amountInput.val()) || 0;
            const isRateBased = $rateInput.length > 0;

            allowances[itemId] = {
                amount: isRateBased ? 0 : value,
                rate: isRateBased ? value : 0,
                is_active: true
            };
        });

        // DEDUCTIONS
        $(`#subscribed-deductions-${empIdStr} .deduction-checkbox:checked`).each(function () {
            const itemId = $(this).data('deduction-id');
            if (!itemId) return;
            const $group = $(this).closest('.form-group');
            const $rateInput = $group.find('.deduction-rate');
            const $amountInput = $group.find('.deduction-amount');
            const value = parseFloat($rateInput.length ? $rateInput.val() : $amountInput.val()) || 0;
            const isRateBased = $rateInput.length > 0;

            deductions[itemId] = {
                amount: isRateBased ? 0 : value,
                rate: isRateBased ? value : 0,
                is_active: true
            };
        });

        // RELIEFS (including Personal Relief)
        $(`#subscribed-reliefs-${empIdStr} .relief-checkbox:checked`).each(function () {
            const itemId = $(this).data('relief-id');
            if (!itemId) return;
            const $amountInput = $(this).closest('.form-group').find('.relief-amount');
            let value = parseFloat($amountInput.val()) || 0;
            if (parseInt(itemId) === 1) value = 2400; // Force Personal Relief

            reliefs[itemId] = { amount: value, is_active: true };
        });

        // OVERTIME
        $(`#subscribed-overtime-${empIdStr} .overtime-checkbox:checked`).each(function () {
            const itemId = $(this).data('overtime-id');
            if (!itemId) return;
            const hours = parseFloat($(this).closest('.form-group').find('.overtime-hours').val()) || 0;
            overtime[itemId] = { amount: hours, is_active: true };
        });

        // LOANS
        $(`#subscribed-loans-${empIdStr} .loan-checkbox:checked`).each(function () {
            const itemId = $(this).data('loan-id');
            if (!itemId) return;
            const amount = parseFloat($(this).closest('.form-group').find('.loan-amount').val()) || 0;
            loans[itemId] = { amount: amount, is_active: true };
        });

        // ADVANCES
        $(`#subscribed-advances-${empIdStr} .advance-checkbox:checked`).each(function () {
            const itemId = $(this).data('advance-id');
            if (!itemId) return;
            const amount = parseFloat($(this).closest('.form-group').find('.advance-amount').val()) || 0;
            advances[itemId] = { amount: amount, is_active: true };
        });

        // ABSENTEEISM CHARGE
        const absInput = $(`#absenteeismTableBody input[name="absenteeism_charge[${empIdStr}]"]`);
        if (absInput.length) {
            absenteeismCharge = parseFloat(absInput.val()) || 0;
        }

        employeesArray.push({
            id: employeeId,
            allowances: allowances,
            deductions: deductions,
            reliefs: reliefs,
            overtime: overtime,
            loans: loans,
            advances: advances,
            absenteeism_charge: { amount: absenteeismCharge }
        });
    });

    console.log('FULL SETTINGS BEING SAVED →', employeesArray);

    $.ajax({
        url: '/payroll/save-settings',
        method: 'POST',
         contentType: 'application/json',
    data: JSON.stringify({
        year: year,
        month: month,
        employees: employeesArray,
        _token: $('meta[name="csrf-token"]').attr('content')
    }),
        success: function (response) {
            if (response.message === 'success' || response.status === 'success') {
                Swal.fire('Success!', 'Settings saved successfully.', 'success').then(() => {
                    togglePayrollSettings();
                });
            } else {
                Swal.fire('Error!', response.message || 'Failed to save settings.', 'error');
            }
        },
        error: function (xhr) {
            console.error(xhr.responseJSON);
            Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to save settings.', 'error');
        }
    });
};

    // window.savePayrollSettings = function () {
    //     const formData = new FormData(document.getElementById("payrollForm"));
    //     const year = formData.get('year');
    //     const month = formData.get('month');
    //     const employees = {};
    //     let allZeroAmountItems = [];

    //     const collectAllItems = (type, tableBody, checkboxClasses, amountClasses, rateClass, idAttrs) => {
    //         const itemsByEmployee = {};
    //         const zeroAmountItems = [];
    //         $(tableBody).find('tr[data-employee-id]').each(function () {
    //             const employeeId = $(this).data('employee-id');
    //             if (!itemsByEmployee[employeeId]) itemsByEmployee[employeeId] = {};

    //             const $checkboxes = $(this).find(`#subscribed-${type}-${employeeId} .${checkboxClasses.join(', .')}, #available-${type}-${employeeId} .${checkboxClasses.join(', .')}`);
    //             $checkboxes.each(function () {
    //                 const itemId = $(this).data(idAttrs[0]) || $(this).data(idAttrs[1]);
    //                 if (!itemId) return;

    //                 const isActive = $(this).is(':checked');
    //                 const $group = $(this).closest('.form-group');
    //                 const $amountInput = $group.find(`.${amountClasses.join(', .')}`);
    //                 const $rateInput = rateClass ? $group.find(`.${rateClass}`) : null;
    //                 let amount = $amountInput.length ? parseFloat($amountInput.val() || 0) : 0;
    //                 const rate = $rateInput ? parseFloat($rateInput.val() || 0) : 0;
    //                 const itemName = $(this).next('label').text().trim();

    //                 // Enforce Personal Relief (item_id = 1) to always be 2400 when active
    //                 if (type === 'reliefs' && itemId === '1' && isActive) {
    //                     amount = 2400; // Fixed value for Personal Relief
    //                 }

    //                 itemsByEmployee[employeeId][itemId] = { is_active: isActive, amount: amount, rate: rate };

    //                 // Skip Personal Relief from zero amount check since it's enforced to 2400
    //                 if (isActive && amount === 0 && (!rateClass || rate === 0) && !(type === 'reliefs' && itemId === '1')) {
    //                     zeroAmountItems.push(`${itemName} for Employee ID: ${employeeId}`);
    //                 }
    //             });
    //         });
    //         return { itemsByEmployee, zeroAmountItems };
    //     };

    //     const tables = [
    //         { type: 'allowances', tableBody: '#allowancesTableBody', checkboxClasses: ['allowance-checkbox'], amountClasses: ['allowance-amount'], rateClass: 'allowance-rate', idAttrs: ['allowance-id'] },
    //         { type: 'deductions', tableBody: '#deductionsTableBody', checkboxClasses: ['deduction-checkbox'], amountClasses: ['deduction-amount'], rateClass: 'deduction-rate', idAttrs: ['deduction-id'] },
    //         { type: 'reliefs', tableBody: '#reliefsTableBody', checkboxClasses: ['relief-checkbox'], amountClasses: ['relief-amount'], rateClass: null, idAttrs: ['relief-id'] },
    //         { type: 'loans', tableBody: '#loansTableBody', checkboxClasses: ['loan-checkbox'], amountClasses: ['loan-amount'], rateClass: null, idAttrs: ['loan-id'] },
    //         { type: 'advances', tableBody: '#advancesTableBody', checkboxClasses: ['advance-checkbox'], amountClasses: ['advance-amount'], rateClass: null, idAttrs: ['advance-id'] },
    //         { type: 'absenteeism', tableBody: '#absenteeismTableBody' },
    //         { type: 'overtime', tableBody: '#overtimeTableBody', checkboxClasses: ['overtime-checkbox'], amountClasses: ['overtime-hours'], rateClass: null, idAttrs: ['overtime-id'] },
    //     ];

    //     tables.forEach(({ type, tableBody, checkboxClasses, amountClasses, rateClass, idAttrs }) => {
    //         $(tableBody).find('tr[data-employee-id]').each(function () {
    //             const employeeId = $(this).data('employee-id');
    //             if (!employees[employeeId]) {
    //                 employees[employeeId] = {
    //                     employee_id: employeeId,
    //                     allowances: {}, deductions: {}, reliefs: {},
    //                     absenteeism_charge: 0, overtime: {}, loans: {}, advances: {}
    //                 };
    //             }

    //             if (type === 'absenteeism') {
    //                 const charge = parseFloat($(this).find(`input[name="absenteeism_charge[${employeeId}]"]`).val() || 0);
    //                 employees[employeeId].absenteeism_charge = charge;
    //             } else {
    //                 const { itemsByEmployee, zeroAmountItems } = collectAllItems(type, tableBody, checkboxClasses || [], amountClasses || [], rateClass, idAttrs || []);
    //                 Object.assign(employees[employeeId][type], itemsByEmployee[employeeId] || {});
    //                 allZeroAmountItems = allZeroAmountItems.concat(zeroAmountItems);
    //             }
    //         });
    //     });

    //     const employeesArray = Object.values(employees).map(employee => ({
    //         id: employee.employee_id,
    //         allowances: employee.allowances,
    //         deductions: employee.deductions,
    //         reliefs: employee.reliefs,
    //         overtime: employee.overtime,
    //         loans: employee.loans,
    //         advances: employee.advances,
    //         absenteeism_charge: { amount: employee.absenteeism_charge }
    //     }));

    //     if (allZeroAmountItems.length > 0) {
    //         Swal.fire({
    //             title: 'Zero Amount Detected',
    //             html: `The following items have zero amounts/rates:<ul>${allZeroAmountItems.map(item => `<li>${item}</li>`).join('')}</ul>Proceed?`,
    //             icon: 'warning',
    //             showCancelButton: true,
    //             confirmButtonText: 'Yes, Save',
    //             cancelButtonText: 'No, Edit'
    //         }).then((result) => {
    //             if (result.isConfirmed) saveData(year, month, employeesArray);
    //         });
    //     } else {
    //         saveData(year, month, employeesArray);
    //     }
    // };

    function saveData(year, month, employeesArray) {
        const data = {
            year: year,
            month: month,
            employees: employeesArray,
            _token: $('meta[name="csrf-token"]').attr('content'),
        };

        console.log('Data being sent to backend:', JSON.stringify(data, null, 2));

        $.ajax({
            url: '/payroll/save-settings',
            method: 'POST',
            data: data,
            success: function (response) {
                if (response.message === 'success') {
                    Swal.fire('Success!', 'Payroll settings saved successfully.', 'success');
                    togglePayrollSettings();
                } else {
                    Swal.fire('Error!', response.message || 'Failed to save settings.', 'error');
                }
            },
            error: function (xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to save payroll settings.', 'error');
            }
        });
    }

    function collectAllSettings() {
        const employees = {};
        const tables = [
            { type: 'allowances', tableBody: '#allowancesTableBody', checkboxClasses: ['allowance-checkbox'], amountClasses: ['allowance-amount'], rateClass: 'allowance-rate', idAttr: 'allowance-id' },
            { type: 'deductions', tableBody: '#deductionsTableBody', checkboxClasses: ['deduction-checkbox'], amountClasses: ['deduction-amount'], rateClass: 'deduction-rate', idAttr: 'deduction-id' },
            { type: 'reliefs', tableBody: '#reliefsTableBody', checkboxClasses: ['relief-checkbox'], amountClasses: ['relief-amount'], rateClass: null, idAttr: 'relief-id' },
            { type: 'loans', tableBody: '#loansTableBody', checkboxClasses: ['loan-checkbox'], amountClasses: ['loan-amount'], rateClass: null, idAttr: 'loan-id' },
            { type: 'advances', tableBody: '#advancesTableBody', checkboxClasses: ['advance-checkbox'], amountClasses: ['advance-amount'], rateClass: null, idAttr: 'advance-id' },
            { type: 'absenteeism', tableBody: '#absenteeismTableBody' },
            { type: 'overtime', tableBody: '#overtimeTableBody', checkboxClasses: ['overtime-checkbox'], amountClasses: ['overtime-hours'], rateClass: null, idAttr: 'overtime-id' },
        ];

        tables.forEach(({ type, tableBody, checkboxClasses, amountClasses, rateClass, idAttr }) => {
            $(tableBody).find('tr[data-employee-id]').each(function () {
                const employeeId = $(this).data('employee-id');
                if (!employees[employeeId]) {
                    employees[employeeId] = {
                        employee_id: employeeId,
                        allowances: {}, deductions: {}, reliefs: {},
                        absenteeism_charge: 0, overtime: {}, loans: {}, advances: {}
                    };
                }

                if (type === 'absenteeism') {
                    employees[employeeId].absenteeism_charge = parseFloat($(this).find(`input[name="absenteeism_charge[${employeeId}]"]`).val() || 0);
                } else {
                    $(this).find(`#subscribed-${type}-${employeeId} .${checkboxClasses.join(', .')}`).each(function () {
                        const itemId = $(this).data(idAttr);
                        const isActive = $(this).is(':checked');
                        if (!itemId || !isActive) return;

                        const $group = $(this).closest('.form-group');
                        const $amountInput = $group.find(`.${amountClasses.join(', .')}`);
                        const $rateInput = rateClass ? $group.find(`.${rateClass}`) : null;
                        let amount = $amountInput.length ? parseFloat($amountInput.val() || 0) : 0;
                        const rate = $rateInput ? parseFloat($rateInput.val() || 0) : 0;

                        // Ensure Personal Relief is always 2400 when active
                        if (type === 'reliefs' && itemId === '1') {
                            amount = 2400;
                        }

                        employees[employeeId][type][itemId] = { is_active: true, amount, rate };
                    });
                }
            });
        });

        return Object.values(employees);
    }

    window.processPayroll = function () {
        const formData = new FormData(document.getElementById("payrollForm"));
        const $previewContainer = $("#payrollPreviewContainer");
        const $previewLoader = $("#previewLoader");
        const settingsData = collectAllSettings();
        formData.append('settings', JSON.stringify(settingsData));

        console.log('Form Data:', JSON.stringify(Object.fromEntries(formData.entries()), null, 2));

        $previewContainer.empty();
        $previewLoader.show();

        $.ajax({
            url: '/payroll/preview',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $previewLoader.hide();

                if (response.message === "success") {
                    $previewContainer.html(response.data.html);
                    $('#payrollForm').hide();
                    $('#previewTable').DataTable({
                        responsive: true,
                        pageLength: 10,
                        searching: true,
                        ordering: true,
                        paging: true,
                        language: { search: "Filter:" }
                    });

                    $previewContainer.append(`
                        <div class="mt-4">
                            <button type="button" class="btn btn-success px-5 py-2 rounded-3 shadow-sm me-2" onclick="submitPayroll()">
                                <i class="fa fa-save me-2"></i> Submit Payroll
                            </button>
                            <button type="button" class="btn btn-secondary px-5 py-2 rounded-3 shadow-sm" onclick="$('#payrollPreviewContainer').empty(); $('#payrollForm').show();">
                                <i class="fa fa-times me-2"></i> Cancel
                            </button>
                        </div>
                    `);
                } else {
                    Swal.fire('Error!', response.error || 'Failed to preview payroll.', 'error');
                }
            },
            error: function (xhr) {
                $previewLoader.hide();
                console.log("Error Response:", xhr.responseJSON);

                if (xhr.status === 400 && xhr.responseJSON?.type === "warnings") {
                    let warningMessage = '<p>Please resolve the following issues before proceeding:</p><ul>';
                    for (const [employeeId, warningData] of Object.entries(xhr.responseJSON.warnings)) {
                        const { name, employee_code, messages } = warningData;
                        warningMessage += `<li><strong>${name} (${employee_code}):</strong> ${messages.join(', ')}</li>`;
                    }
                    warningMessage += '</ul><p>Correct these issues and try again.</p>';

                    Swal.fire({
                        title: 'Warning!',
                        html: warningMessage,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire('Error!', xhr.responseJSON?.error || 'Failed to preview payroll.', 'error');
            }
        });
    };

    window.submitPayroll = function () {
        const $previewLoader = $("#previewLoader");
        $previewLoader.show();

        $.ajax({
            url: '/payroll/store',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (response) {
                $previewLoader.hide();
                if (response.message === 'success') {
                    Swal.fire('Success!', 'Payroll processed and stored successfully.', 'success').then(() => {
                        window.location.href = response.data.redirect_url || '/payroll';
                    });
                } else {
                    Swal.fire('Error!', response.error || 'Failed to store payroll.', 'error');
                }
            },
            error: function (xhr) {
                $previewLoader.hide();
                Swal.fire('Error!', xhr.responseJSON?.error || 'Failed to store payroll.', 'error');
            }
        });
    };
})(jQuery);
