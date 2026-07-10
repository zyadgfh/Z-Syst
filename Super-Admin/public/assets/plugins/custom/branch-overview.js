"use strict";

function currencyFormat(amount, type = "icon", decimals = 2) {
    let symbol = $("#currency_symbol").val();
    let position = $("#currency_position").val();
    let code = $("#currency_code").val();

    let formattedAmount = formatNumber(amount, decimals); // Abbreviate number

    // Apply currency format based on the position and type
    if (type == "icon" || type == "symbol") {
        return position == "right"
            ? formattedAmount + symbol
            : symbol + formattedAmount;
    } else {
        return position == "right"
            ? formattedAmount + " " + code
            : code + " " + formattedAmount;
    }
}

function formatNumber(number, decimals = 2) {
    if (number >= 1e9) {
        return removeTrailingZeros((number / 1e9).toFixed(decimals)) + "B";
    } else if (number >= 1e6) {
        return removeTrailingZeros((number / 1e6).toFixed(decimals)) + "M";
    } else if (number >= 1e3) {
        return removeTrailingZeros((number / 1e3).toFixed(decimals)) + "K";
    } else {
        return removeTrailingZeros(number.toFixed(decimals));
    }
}

function removeTrailingZeros(value) {
    return parseFloat(value).toString();
}

let branchRevenueChart;

$(document).ready(function () {
    const ctxBranchRevenue = document
        .getElementById("branchRevenueChart")
        .getContext("2d");

    function totalEarningExpenseChart(months, incomeData, expenseData) {
        if (branchRevenueChart) {
            branchRevenueChart.destroy();
        }

        const allData = [...incomeData, ...expenseData];
        const maxValue = Math.max(...allData);

        // Gradient Backgrounds
        const incomeBgGradient = ctxBranchRevenue.createLinearGradient(0, 0, 0, 400);
        incomeBgGradient.addColorStop(0, "rgba(42, 180, 249, 0.17)");
        incomeBgGradient.addColorStop(1, "rgba(34, 201, 177, 0)");

        const expenseBgGradient = ctxBranchRevenue.createLinearGradient(0, 0, 0, 400);
        expenseBgGradient.addColorStop(0, "rgba(248, 107, 35, 0.12)");
        expenseBgGradient.addColorStop(1, "rgba(249, 190, 16, 0)");

        // Solid Gradients for border lines
        const incomeLine = ctxBranchRevenue.createLinearGradient(0, 0, 400, 0);
        incomeLine.addColorStop(0, "#019934");
        incomeLine.addColorStop(1, "#019934");

        const expenseLine = ctxBranchRevenue.createLinearGradient(0, 0, 400, 0);
        expenseLine.addColorStop(0, "#FF9500");
        expenseLine.addColorStop(1, "#FF9500");

        branchRevenueChart = new Chart(ctxBranchRevenue, {
            type: "line",
            data: {
                labels: months,
                datasets: [
                    {
                        label: "Profit",
                        data: incomeData,
                        borderColor: incomeLine,
                        backgroundColor: incomeBgGradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: "#019934",
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: "Loss",
                        data: expenseData,
                        borderColor: expenseLine,
                        backgroundColor: expenseBgGradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: "#FF9500",
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "index", intersect: false },
                plugins: {
                    tooltip: {
                        backgroundColor: "#ffffff",
                        titleColor: "#000000",
                        bodyColor: "#000000",
                        borderColor: "#e5e7eb",
                        borderWidth: 1,
                        callbacks: {
                            label: function (context) {
                                const value = parseFloat(context.raw);
                                return `${context.dataset.label} : ${(value)}`;
                            },
                        },
                    },
                    legend: { display: false },
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { drawBorder: false, color: "#C2C6CE", borderDash: [4, 4] },
                        ticks: {
                            callback: function (value) {
                                if (maxValue < 1000) return value;
                                else if (maxValue < 100000) return (value / 1000) + 'k';
                                else return (value / 100000).toFixed(1) + 'M';
                            },
                        },
                    },
                    x: { grid: { display: false } },
                },
            },
        });
    }

    function fetchIncomeExpense(year) {
        const route = $("#incomeExpenseRoute").val();

        $.ajax({
            url: route,
            type: "GET",
            data: { year: year },
            success: function (data) {
                const months = [
                    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
                    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
                ];

                const incomeData = Array(12).fill(0);
                const expenseData = Array(12).fill(0);

                data.incomes.forEach((item) => {
                    const monthIndex = item.month_number - 1;
                    incomeData[monthIndex] = parseFloat(item.total);
                });

                data.expenses.forEach((item) => {
                    const monthIndex = item.month_number - 1;
                    expenseData[monthIndex] = parseFloat(item.total);
                });

                const totalIncome = incomeData.reduce((a, b) => a + b, 0);
                const totalExpense = expenseData.reduce((a, b) => a + b, 0);

                $(".profit-value").text(currencyFormat(totalIncome));
                $(".loss-value").text(currencyFormat(totalExpense));

                totalEarningExpenseChart(months, incomeData, expenseData);
            },
            error: function (err) {
                console.error("Error fetching income/expense data:", err);
            },
        });
    }

    // Initial load
    const selectedYear = $(".overview-year").val();
    fetchIncomeExpense(selectedYear);

    // On year change
    $(".overview-year").on("change", function () {
        const year = $(this).val();
        fetchIncomeExpense(year);
    });
});

// Profit Loss Reports ----------------------->
const profitLossCanvas = document.getElementById("profitLossChart");
const ctxProfitLoss = profitLossCanvas.getContext("2d");

const gradientProfit = ctxProfitLoss.createLinearGradient(0, 0, 0, profitLossCanvas.height);
gradientProfit.addColorStop(0, "#05C535");
gradientProfit.addColorStop(1, "#36F165");

const gradientLoss = ctxProfitLoss.createLinearGradient(0, 0, 0, profitLossCanvas.height);
gradientLoss.addColorStop(0, "#FF8983");
gradientLoss.addColorStop(1, "#FF3B30");

// Initial static values (will be updated dynamically)
let profit = 0;
let loss = 0;

const profitLossData = {
    labels: ["Income", "Expense"],
    datasets: [
        {
            data: [profit, loss],
            backgroundColor: [gradientProfit, gradientLoss],
            hoverOffset: 5,
        },
    ],
};

const profitLossConfig = {
    type: "pie",
    data: profitLossData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                enabled: true,
                backgroundColor: "#FFFFFF",
                titleColor: "#000000",
                bodyColor: "#000000",
                borderWidth: 1,
                displayColors: false,
                callbacks: {
                    label: function (context) {
                        const label = context.label || '';
                        const value = parseFloat(context.parsed || 0);
                        const formattedValue = value.toFixed(2);
                        return `${label}: ${formattedValue}`;
                    },
                },
            },
        },
    },
};

const profitLossChart = new Chart(ctxProfitLoss, profitLossConfig);

// Dynamic update function
const chartRoute = document.getElementById("salePurchaseChartRoute").value;
const yearSelector = document.querySelector(".overview-loss-profit-year");

function updateProfitLossChart(year) {
    fetch(`${chartRoute}?year=${year}`)
        .then(res => res.json())
        .then(data => {
            profit = data.profit ?? 0;
            loss = data.loss ?? 0;

            // Update chart dataset
            profitLossChart.data.datasets[0].data = [profit, loss];
            profitLossChart.update();

            // ✅ Use your currencyFormat for displayed values too
            document.querySelector(".profit").textContent = currencyFormat(profit);
            document.querySelector(".loss").textContent = currencyFormat(loss);
        })
        .catch(err => {
            console.error("Error fetching profit/loss data:", err);
        });
}

// Initial load
updateProfitLossChart(yearSelector.value);

// On year change
yearSelector.addEventListener("change", function () {
    updateProfitLossChart(this.value);
});

// Responsive resize
window.addEventListener("resize", function () {
    profitLossChart.resize();
});


// for sale data

$(document).ready(function() {
    var route = $('#branchWiseSaleRoute').val();

    // Load current year sales on page load
    var currentYear = $('.branch-wise-sales-year').val();
    fetchBranchWiseSales(currentYear);

    // Listen for year change
    $('.branch-wise-sales-year').on('change', function() {
        var year = $(this).val();
        fetchBranchWiseSales(year);
    });

    function fetchBranchWiseSales(year) {
        $.ajax({
            url: route,
            type: 'GET',
            data: { year: year },
            dataType: 'json',
            success: function(data) {
                var tbody = $('#sale-data');
                tbody.empty(); // clear existing table rows

                $.each(data, function(index, branch) {
                    var row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${branch.name}</td>
                            <td>${branch.sales_sum_total_amount_formatted}</td>
                            <td>${branch.sales_sum_paid_amount_formatted}</td>
                            <td>${branch.sales_sum_due_amount_formatted}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            },
            error: function(xhr, status, error) {
                console.error('Error fetching branch sales:', error);
            }
        });
    }
});

// for purchase data

$(document).ready(function() {
    function fetchBranchPurchases(year) {
        let url = $('#branchWisePurchaseRoute').val();

        $.ajax({
            url: url,
            method: 'GET',
            data: { year: year },
            dataType: 'json',
            success: function(response) {
                let tbody = $('#purchase-data');
                tbody.empty();

                $.each(response, function(index, branch) {
                    let row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${branch.name}</td>
                            <td>${branch.purchases_sum_total_amount_formatted}</td>
                            <td>${branch.purchases_sum_paid_amount_formatted}</td>
                            <td>${branch.purchases_sum_due_amount_formatted}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            },
            error: function(xhr, status, error) {
                console.error('Error fetching branch purchases:', error);
            }
        });
    }

    // Initial load for current year
    let currentYear = $('.batch-wise-purchases-year').val();
    fetchBranchPurchases(currentYear);

    // Fetch data on year change
    $('.batch-wise-purchases-year').on('change', function() {
        let selectedYear = $(this).val();
        fetchBranchPurchases(selectedYear);
    });
});

