import RequestClient from "/js/client/RequestClient.js";
import TrendsService from "/js/client/TrendsService.js";

const requestClient = new RequestClient();
const trendsService = new TrendsService(requestClient);
let charts = {};

window.payrollTrends = async function (year, location = null) {

    let formData = {
        year : year,
        location : location
    };

    try {
        const trendsData = await trendsService.payroll(formData);
        if (trendsData) {
            renderPayrollChart(trendsData, year || new Date().getFullYear());
        }
    } finally {

    }
};

function renderChart(elementId, chartType, series, categories, colors, title, yAxisTitle) {
    if (charts[elementId]) {
        charts[elementId].destroy();
    }

    charts[elementId] = new ApexCharts(document.querySelector(`#${elementId}`), {
        chart: { type: chartType, height: 400 },
        series,
        xaxis: { categories, title: { text: title } },
        yaxis: { title: { text: yAxisTitle } },
        colors,
        tooltip: { shared: false },
    });

    charts[elementId].render();
}

window.loadTrends = async function (year) {
    try {
        const attendanceData = await trendsService.attendance({ year });
        renderAttendanceChart(attendanceData, year);

        const leaveData = await trendsService.leave({ year });
        console.log(leaveData);

        renderLeaveChart(leaveData, year);

        const loanData = await trendsService.loans({ year });
        renderLoanChart(loanData, year);
    } catch (error) {
        console.error("Error loading trends:", error);
    }
};

function renderPayrollChart(trendsData, selectedYear) {
    const months = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const netPays = months.map(month => Number(trendsMap.get(month)?.total_net_pay || 0));
    const grossPays = months.map(month => Number(trendsMap.get(month)?.total_gross_pay || 0));

    renderChart("payrollChart", "bar", [
        { name: "Net Pay", data: netPays },
        { name: "Gross Pay", data: grossPays }
    ], monthNames, ["#00E396", "#FF4560"], `Months of ${selectedYear}`, "Amount (KES)");
}

function renderAttendanceChart(trendsData, selectedYear) {
    const months = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const attendanceCounts = months.map(month => Number(trendsMap.get(month)?.total_attendance || 0));

    renderChart("attendanceChart", "line", [
        { name: "Attendance", data: attendanceCounts }
    ], monthNames, ["#007BFF"], `Months of ${selectedYear}`, "Number of Check-ins");
}

function renderLeaveChart(trendsData, selectedYear) {
    const months = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const leaveCounts = months.map(month => Number(trendsMap.get(month)?.total_leaves || 0));

    renderChart("leaveChart", "line", [
        { name: "Leave Requests", data: leaveCounts }
    ], monthNames, ["#FFB400"], `Months of ${selectedYear}`, "Number of Leave Requests");
}

function renderLoanChart(trendsData, selectedYear) {
    const months = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const loanAmounts = months.map(month => Number(trendsMap.get(month)?.total_loan_amount || 0));

    renderChart("loanChart", "bar", [
        { name: "Loans Disbursed", data: loanAmounts }
    ], monthNames, ["#8E44AD"], `Months of ${selectedYear}`, "Loan Amount (KES)");
}

const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
function getMonthLabels(year) {
    return Array.from({ length: 12 }, (_, i) => `${year}-${String(i + 1).padStart(2, "0")}`);
}


function renderPayrollChart2(trendsData, selectedYear) {

    const allMonths = Array.from({ length: 12 }, (_, i) =>
        `${selectedYear}-${String(i + 1).padStart(2, "0")}`
    );

    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const netPays = allMonths.map(month => Number(trendsMap.get(month)?.total_net_pay || 0));
    const grossPays = allMonths.map(month => Number(trendsMap.get(month)?.total_gross_pay || 0));

    if (payrollChart) {
        payrollChart.destroy();
    }

    payrollChart = new ApexCharts(document.querySelector("#payrollChart"), {
        chart: {
            type: "bar",
            height: 400,
            stacked: false
        },
        series: [
            {
                name: "Net Pay",
                data: netPays
            },
            {
                name: "Gross Pay",
                data: grossPays
            }
        ],
        xaxis: {
            categories: [
                "Jan", "Feb", "Mar", "Apr", "May", "Jun",
                "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
            ],
            title: { text: `Months of ${selectedYear}` }
        },
        yaxis: {
            title: { text: "Amount (KES)" }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: "85%"
            }
        },
        colors: ["#00E396", "#FF4560"],
        tooltip: { shared: false }
    });

    payrollChart.render();
}
