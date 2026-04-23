<?php
include '../FUNCTION/mainFunc.inc.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../IMG/favicon.png">
    <title>Cash Compass</title>
    <link rel="stylesheet" href="../CSS/set.css">
    <link rel="stylesheet" href="../CSS/sideNav.css">
    <link rel="stylesheet" href="../CSS/transaction.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include '../FUNCTION/sideNav.inc.php'; ?>
    <section id="main">
        <span id="main-title">
            <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
            <a>TRANSACTION</a>
            <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                <button id="logout-btn" type="submit">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </span>
        <hr />
        <section id="transaction">
            <section id="total-transaction">
                <div id="chart-container">
                    <?php
                        // Ensure $totalIncome and $totalExpense are numbers
                        $income = floatval($totalIncome);
                        $expense = floatval($totalExpense);
                        $hasData = ($income != 0 || $expense != 0);
                    ?>
                    <canvas id="chart" <?php if (!$hasData) echo 'style="display:none;"'; ?>></canvas>
                    <div id="custom-legend">
                        <?php
                            // Fetch current month and year
                            $currentMonth = date('F');
                            echo "<h5>This Month's Transactions</h5>";
                        ?>
                        <div id="legend-items"></div>
                    </div>
                    <div id="no-data-message" style="text-align:center;<?php echo $hasData ? 'display:none;' : 'display:flex;'; ?> color:#5f2824; height: 35vh; align-items: center; justify-content: center; margin-top: 0em; font-size: 0.6em; padding-left: 3em;">
                        <p style="margin-top: 0em;">No Transaction Found</p>
                    </div>
                    <script>
                        // Update category options based on transaction type
                        function updateCategoryOptions() {
                            const type = document.getElementById('transaction-history-filter').value;
                            const categorySelect = document.getElementById('transaction-history-category');
                            const options = categorySelect.options;
                            for (let i = 0; i < options.length; i++) {
                                const option = options[i];
                                if (option.value === 'all') {
                                    option.style.display = 'block';
                                    continue;
                                }
                                const optionType = option.getAttribute('transaction-type');
                                if (type === 'all' || optionType === type) {
                                    option.style.display = 'block';
                                } else {
                                    option.style.display = 'none';
                                }
                            }
                        }

                        // Initialize chart functionality
                        let myChart = null; // Global variable to store chart instance

                        function initializeChart(income, expense) {
                            const total = income + expense;

                            if (income === 0 && expense === 0) {
                                if (myChart) {
                                    myChart.destroy();
                                    myChart = null;
                                }
                                document.getElementById('chart').style.display = 'none';
                                document.getElementById('legend-items').style.display = 'none';
                                document.getElementById('no-data-message').style.display = 'flex';
                                return;
                            }

                            document.getElementById('chart').style.display = 'block';
                            document.getElementById('legend-items').style.display = 'block';
                            document.getElementById('no-data-message').style.display = 'none';

                            // Destroy existing chart if it exists
                            if (myChart) {
                                myChart.destroy();
                                myChart = null;
                            }

                            const ctx = document.getElementById('chart').getContext('2d');
                            myChart = new Chart(ctx, {
                                type: 'pie',
                                data: {
                                    labels: ['Income', 'Expense'],
                                    datasets: [{
                                        label: 'Amount (in RM)',
                                        data: [income, expense],
                                        backgroundColor: [
                                            '#6C8396', // Income
                                            '#E5B5B2'  // Expense
                                        ],
                                        borderColor: [
                                            '#232640',
                                            '#5f2824'
                                        ],
                                        borderWidth: 2.5
                                    }]
                                },
                                options: {
                                    responsive: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    }
                                }
                            });

                            // Update legend
                            const legendData = [
                                {
                                    label: 'Income',
                                    color: '#6C8396',
                                    amount: income,
                                    percent: total ? (income / total * 100) : 0
                                },
                                {
                                    label: 'Expense',
                                    color: '#E5B5B2',
                                    amount: expense,
                                    percent: total ? (expense / total * 100) : 0
                                }
                            ];
                            document.getElementById('legend-items').innerHTML = legendData.map(item => `
                                <div style="display:flex;align-items:center;margin-bottom:0.7em; gap: 1em;">
                                    <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:${item.color};margin-right:10px;"></span>
                                    <span style="font-weight:bold;width:100px;display:inline-block; color: #232640;">${item.label}</span>
                                    <span style="color: #232640;">${item.percent.toFixed(2)}%</span>
                                </div>
                            `).join('');
                        }

                        // Initialize chart with initial data
                        const initialIncome = <?php echo $income; ?>;
                        const initialExpense = <?php echo $expense; ?>;
                        initializeChart(initialIncome, initialExpense);

                        // Add event listeners after DOM is loaded
                        document.addEventListener('DOMContentLoaded', function() {
                            // Add filter change event listener
                            document.getElementById('transaction-history-filter').addEventListener('change', updateCategoryOptions);
                            updateCategoryOptions();

                            // Add filter button click event listener
                            document.getElementById('transaction-history-filter-btn').addEventListener('click', function() {
                                const type = document.getElementById('transaction-history-filter').value;
                                const category = document.getElementById('transaction-history-category').value;
                                const date = document.getElementById('transaction-history-date').value;
                                
                                // Show loading state
                                const tableContainer = document.getElementById('transaction-history-item');
                                tableContainer.innerHTML = '<div style="text-align: center; padding: 2em;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 2em; color: #294c4b;"></i></div>';
                                
                                // Make AJAX request
                                fetch('../FUNCTION/getFilteredTransactions.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `type=${encodeURIComponent(type)}&category=${encodeURIComponent(category)}&date=${encodeURIComponent(date)}`
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.error) {
                                        console.error('Server error:', data);
                                        throw new Error(data.error + (data.details ? ': ' + data.details : ''));
                                    }

                                    // Update totals and chart
                                    if (data.totals) {
                                        document.getElementById('balance-amount').textContent = `RM ${data.totals.balance}`;
                                        document.getElementById('total-income').querySelector('h1:last-child').textContent = `RM ${data.totals.income}`;
                                        document.getElementById('total-expense').querySelector('h1:last-child').textContent = `RM ${data.totals.expense}`;
                                        
                                        // Update chart
                                        const income = parseFloat(data.totals.income.replace(/,/g, ''));
                                        const expense = parseFloat(data.totals.expense.replace(/,/g, ''));
                                        
                                        // Update chart title based on date filter
                                        let dateTitle = 'All Time';
                                        switch(date) {
                                            case 'today':
                                                dateTitle = 'Today\'s';
                                                break;
                                            case 'this-week':
                                                dateTitle = 'This Week\'s';
                                                break;
                                            case 'this-month':
                                                dateTitle = 'This Month\'s';
                                                break;
                                            case 'last-month':
                                                dateTitle = 'Last Month\'s';
                                                break;
                                            case 'last-3-month':
                                                dateTitle = 'Last 3 Months\'';
                                                break;
                                            case 'this-year':
                                                dateTitle = 'This Year\'s';
                                                break;
                                        }
                                        document.querySelector('#custom-legend h5').textContent = `${dateTitle} Transactions`;

                                        // Update chart with new data
                                        initializeChart(income, expense);
                                    }

                                    // Update transaction table
                                    if (!Array.isArray(data.transactions)) {
                                        throw new Error('Invalid transaction data received');
                                    }

                                    if (data.transactions.length === 0) {
                                        tableContainer.innerHTML = `
                                            <table id="transaction-history-table">
                                                <thead>
                                                    <tr>
                                                        <th colspan="2">Transaction Name</th>
                                                        <th>Date</th>
                                                        <th colspan="2">Amount (RM)</th>
                                                        <th>&nbsp;</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="6" style="text-align:center;">No transactions found.</td>
                                                    </tr>
                                                </tbody>
                                            </table>`;
                                        return;
                                    }

                                    let tableHtml = `
                                        <table id="transaction-history-table">
                                            <thead>
                                                <tr>
                                                    <th colspan="2">Transaction Name</th>
                                                    <th>Date</th>
                                                    <th colspan="2">Amount (RM)</th>
                                                    <th>&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;
                                    data.transactions.forEach(transaction => {
                                        const amountColor = transaction.type === 'expense' ? '#5f2824' : '#294c4b';
                                        const amountType = transaction.type === 'expense' ? '-' : '+';
                                        const sharedIndicator = transaction.isShared ? ' <i class="fa-solid fa-users" style="color:rgb(117, 59, 130); font-size: 0.7em;"></i>' : '';
                                        const savingIndicator = transaction.isSaving ? ' <i class="fa-solid fa-vault" style="color: #319832; font-size: 0.7em;"></i>' : '';
                                        const budgetIndicator = transaction.isBudget ? ' <i class="fa-solid fa-money-bill" style="color:rgb(160, 80, 74); font-size: 0.7em;"></i>' : '';
                                        const truncatedName = transaction.name.length > 29 ? transaction.name.substring(0, 28) + '...' : transaction.name;
                                        tableHtml += `
                                            <tr>
                                                <td id="table-transaction-name">${truncatedName}</td>
                                                <td id="table-icon">${sharedIndicator}${savingIndicator}${budgetIndicator}</td>
                                                <td id="table-date">${transaction.date}</td>
                                                <td id="table-amountType" style="color:${amountColor};">${amountType}</td>
                                                <td id="table-amount" style="color:${amountColor};">${transaction.amount}</td>
                                                <td><button id="table-view-btn" onclick="window.location.href='viewTransactionDetails.php?transactionID=${transaction.id}'">View</button></td>
                                            </tr>`;
                                    });
                                    tableHtml += `
                                        </tbody>
                                    </table>`;
                                    tableContainer.innerHTML = tableHtml;
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    tableContainer.innerHTML = `
                                        <table id="transaction-history-table">
                                            <thead>
                                                <tr>
                                                    <th colspan="2">Transaction Name</th>
                                                    <th>Date</th>
                                                    <th colspan="2">Amount (RM)</th>
                                                    <th>&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="6" style="text-align:center;">Error loading transactions: ${error.message}</td>
                                                </tr>
                                            </tbody>
                                        </table>`;
                                });
                            });

                            // Automatically trigger filter on page load to show current month data
                            document.getElementById('transaction-history-filter-btn').click();
                        });
                    </script>
                </div>
                <div id="amount-container">
                    <div id="balance" class="amount">
                        <h1 style="width: 40%; text-align: right;">Current Balance </h1>
                        <h1 style="width: 20%; text-align: center;"> | </h1>
                        <h1 id="balance-amount" style="width: 40%; text-align: left;">RM <?php getBalance($totalIncome, $totalExpense); ?></h1>
                    </div>
                    <div id="total-income" class="amount">
                        <h1 style="width: 40%; text-align: right;">Total Income </h1>
                        <h1 style="width: 20%; text-align: center;"> | </h1>
                        <h1 style="width: 40%; text-align: left;">RM <?php getTotalIncome($totalIncome); ?></h1>
                    </div>
                    <div id="total-expense" class="amount">
                        <h1 style="width: 40%; text-align: right;">Total Expense </h1>
                        <h1 style="width: 20%; text-align: center;"> | </h1>
                        <h1 style="width: 40%; text-align: left;">RM <?php getTotalExpense($totalExpense); ?></h1>
                    </div>
                    <button id="add-transaction-btn"
                        onclick="window.location.href='../FRONTEND/addTransaction.php'">ADD TRANSACTION
                    </button>
                </div>
            </section>
            <section id="transaction-history">
                <h1 style="padding:0em 0em 1em 0em; margin:0 0 0.5em 0; color: #232640;">All Transaction History</h1>
                <div id="transaction-history-title">
                    <form id="transaction-history-filter-form">
                        <select name="transaction-history-filter" id="transaction-history-filter">
                            <option value="all" selected>All</option>
                            <option value="budget">Budget</option>
                            <option value="saving">Saving Goals</option>
                            <option value="shared">Shared Expense</option>
                        </select>
                        <select name="transaction-history-category" id="transaction-history-category">
                            <option value="all" selected>All Categories</option>
                            <?php
                                include_once '../DB/db_connect.php';
                                if (session_status() === PHP_SESSION_NONE) session_start();
                                $userId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
                                $connect = OpenCon();
                                $categoryQuery = "SELECT * FROM categories WHERE user_id = 0 OR user_id = $userId ORDER BY transactionType ASC, categoryName ASC";
                                $categoryResult = mysqli_query($connect, $categoryQuery);
                                $categoriesByType = [];
                                while ($category = mysqli_fetch_assoc($categoryResult)) {
                                    $type = ucfirst(strtolower($category['transactionType']));
                                    $categoriesByType[$type][] = $category;
                                }
                                foreach ($categoriesByType as $type => $categories) {
                                    echo "<optgroup label='" . htmlspecialchars($type) . "'>";
                                    if (strtolower($type) === 'income') {
                                        echo "<option value='all_income' transaction-type='income'>All Income</option>";
                                    } else if (strtolower($type) === 'expense') {
                                        echo "<option value='all_expense' transaction-type='expense'>All Expense</option>";
                                    }
                                    foreach ($categories as $category) {
                                        echo "<option value='" . htmlspecialchars($category['category_id']) . "' transaction-type='" . strtolower($category['transactionType']) . "'>" . htmlspecialchars($category['categoryName']) . "</option>";
                                    }
                                    echo "</optgroup>";
                                }
                                CloseCon($connect);
                            ?>
                        </select>
                        <select name="transaction-history-date" id="transaction-history-date">
                            <option value="all">All</option>
                            <option value="today">Today</option>
                            <option value="this-week">This Week</option>
                            <option value="this-month" selected>This Month</option>
                            <option value="last-month">Last Month</option>
                            <option value="last-3-month">Last 3 Months</option>
                            <option value="this-year">This Year</option>
                        </select>
                        <button type="button" id="transaction-history-filter-btn">Filter</button>
                    </form>
                </div>
                <div id="transaction-history-container">
                    <div id="transaction-history-item">
                        <?php include 'transactionHistory.php';?>
                    </div>
                </div>
            </section>
        </section>
    </section>
</body>

</html>