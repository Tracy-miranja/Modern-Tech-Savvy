import RequestClient from "/js/client/RequestClient.js";
import TrendsService from "/js/client/TrendsService.js";

const requestClient = new RequestClient();
const trendsService = new TrendsService(requestClient);
let charts = {};


// ----------------------------------------------------
// UNIVERSAL MODERN CHART STYLE (MATCHING YOUR EXAMPLE)
// ----------------------------------------------------
function renderSmoothAreaChart(elementId, series, categories, lineColor, fillColor) {
    if (charts[elementId]) charts[elementId].destroy();

    charts[elementId] = new ApexCharts(document.querySelector(`#${elementId}`), {
        chart: {
            type: "area",
            height: 320,
            toolbar: { show: false }
        },

        stroke: {
            curve: "smooth",
            width: 3
        },

        colors: [lineColor],

        fill: {
            type: "gradient",
            gradient: {
                shadeIntensity: 0.25,
                opacityFrom: 0.45,
                opacityTo: 0.1,
                stops: [0, 90, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: fillColor,
                        opacity: 0.4
                    },
                    {
                        offset: 100,
                        color: fillColor,
                        opacity: 0
                    }
                ]
            }
        },

        markers: { size: 0 },

        dataLabels: { enabled: false },

        grid: {
            strokeDashArray: 4,
            borderColor: "#e5e7eb"
        },

        xaxis: {
            categories,
            labels: {
                style: {
                    colors: "#666",
                    fontSize: "12px"
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },

        yaxis: {
            labels: {
                style: {
                    colors: "#666",
                    fontSize: "12px"
                }
            }
        },

        tooltip: { shared: true },

        series: series
    });

    charts[elementId].render();
}



// ----------------------------------------------------
// PAYROLL TRENDS
// ----------------------------------------------------
window.payrollTrends = async function (year, location = null) {
    const formData = { year, location };

    try {
        const trendsData = await trendsService.payroll(formData);
        if (trendsData) {
            renderPayrollChart(trendsData, year || new Date().getFullYear());
        }
    } catch (err) {
        console.error(err);
    }
};

function renderPayrollChart(trendsData, selectedYear) {
    const allMonths = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const netPays = allMonths.map(month => Number(trendsMap.get(month)?.total_net_pay || 0));

    // ---- MATCHING YOUR UPLOADED CHART: ORANGE CURVED AREA ----
    renderSmoothAreaChart(
        "payrollChart",
        [{ name: "Net Pay", data: netPays }],
        monthNames,
        "#d97706",          // orange line
        "#fcd34d"           // soft golden fill
    );
}



// ----------------------------------------------------
// LOAD OTHER TRENDS
// ----------------------------------------------------
window.loadTrends = async function (year) {
    try {
        const attendanceData = await trendsService.attendance({ year });
        renderAttendanceChart(attendanceData, year);

        const leaveData = await trendsService.leave({ year });
        renderLeaveChart(leaveData, year);

        const loanData = await trendsService.loans({ year });
        renderLoanChart(loanData, year);

    } catch (error) {
        console.error("Error loading trends:", error);
    }
};



// ----------------------------------------------------
// ATTENDANCE — MATCH SAME STYLE AS YOUR EXAMPLE
// ----------------------------------------------------
function renderAttendanceChart(trendsData, selectedYear) {
    const allMonths = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const attendanceCounts = allMonths.map(month => Number(trendsMap.get(month)?.total_attendance || 0));

    // Same style as the orange chart but in blue
    renderSmoothAreaChart(
        "attendanceChart",
        [{ name: "Attendance", data: attendanceCounts }],
        monthNames,
        "#2563eb",     // blue line
        "#60a5fa"      // soft blue fill
    );
}



// ----------------------------------------------------
// LEAVE TRENDS
// ----------------------------------------------------
function renderLeaveChart(trendsData, selectedYear) {
    const allMonths = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const leaveCounts = allMonths.map(month => Number(trendsMap.get(month)?.total_leaves || 0));

    renderSmoothAreaChart(
        "leaveChart",
        [{ name: "Leave Requests", data: leaveCounts }],
        monthNames,
        "#f59e0b",   // amber
        "#fcd34d"
    );
}



// ----------------------------------------------------
// LOAN TRENDS
// ----------------------------------------------------
function renderLoanChart(trendsData, selectedYear) {
    const allMonths = getMonthLabels(selectedYear);
    const trendsMap = new Map(trendsData.map(item => [item.month, item]));

    const loanAmounts = allMonths.map(month => Number(trendsMap.get(month)?.total_loan_amount || 0));

    renderSmoothAreaChart(
        "loanChart",
        [{ name: "Loans Disbursed", data: loanAmounts }],
        monthNames,
        "#7e22ce",   // purple
        "#c084fc"
    );
}



// ----------------------------------------------------
// MONTH LABEL HELPERS
// ----------------------------------------------------
const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function getMonthLabels(year) {
    return Array.from({ length: 12 }, (_, i) => `${year}-${String(i + 1).padStart(2, "0")}`);
}
