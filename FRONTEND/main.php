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
        <link rel="stylesheet" href="../CSS/main.css">
        <link rel="stylesheet" href="../CSS/scrollBar.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body>
        <?php include '../FUNCTION/sideNav.inc.php'; ?>
        <?php
            // Get expense by category for current month only (up to current date)
            $userId = intval($_SESSION['id']);
            $currentYear = date('Y');
            $currentMonth = date('m');
            $currentDay = date('d');
            
            $expenseQuery = "
                SELECT c.categoryName, SUM(t.amount) as total
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.category_id
                WHERE t.user_id = $userId
                    AND t.transactionType = 'expense'
                    AND MONTH(t.date) = $currentMonth
                    AND YEAR(t.date) = $currentYear
                    AND DAY(t.date) <= $currentDay
                    AND (c.user_id = 0 OR c.user_id = $userId)
                GROUP BY t.category_id
                ORDER BY total DESC
            ";
            $expenseResult = mysqli_query($connect, $expenseQuery);

            $expenseLabels = [];
            $expenseData = [];
            $expenseColors = ['#ffbcbc', '#ffdba8', '#fff8b7', '#e3ffa3', '#b0ffb1', '#c1fffe', '#9edbec', '#9ea3ec', '#de9eec'];
            while ($row = mysqli_fetch_assoc($expenseResult)) {
                $expenseLabels[] = $row['categoryName'] ? $row['categoryName'] : 'Uncategorized';
                $expenseData[] = floatval($row['total']);
            }
            $hasExpenseData = array_sum($expenseData) > 0;

            // Get income by category for current month only (up to current date)
            $incomeQuery = "
                SELECT c.categoryName, SUM(t.amount) as total
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.category_id
                WHERE t.user_id = $userId
                AND t.transactionType = 'income'
                AND MONTH(t.date) = $currentMonth
                AND YEAR(t.date) = $currentYear
                AND DAY(t.date) <= $currentDay
                AND (c.user_id = 0 OR c.user_id = $userId)
                GROUP BY t.category_id
                ORDER BY total DESC
            ";
            $incomeResult = mysqli_query($connect, $incomeQuery);

            $incomeLabels = [];
            $incomeData = [];
            $incomeColors = ['#772e2e', '#976e33', '#ab9f34', '#7fa131', '#319832', '#2a7c7b', '#255f6f', '#161a5b', '#44154f'];
            while ($row = mysqli_fetch_assoc($incomeResult)) {
                $incomeLabels[] = $row['categoryName'] ? $row['categoryName'] : 'Uncategorized';
                $incomeData[] = floatval($row['total']);
            }
            $hasIncomeData = array_sum($incomeData) > 0;

            // For income legend
            $totalIncome = array_sum($incomeData);
            $incomePercentages = [];
            if ($totalIncome > 0) {
                foreach ($incomeData as $value) {
                    $incomePercentages[] = round(($value / $totalIncome) * 100, 2);
                }
            }

            // For expense legend
            $totalExpense = array_sum($expenseData);
            $expensePercentages = [];
            if ($totalExpense > 0) {
                foreach ($expenseData as $value) {
                    $expensePercentages[] = round(($value / $totalExpense) * 100, 2);
                }
            }
            
            $hasData = $hasIncomeData || $hasExpenseData;

            // Get Daily Expense for the current month (up to current date)
            $currentMonthName = date('F') . " (up to " . date('jS') . ")";

            $dailyLabels = [];
            $dailyData = [];

            // Generate all days from 1st to current day of the month
            for ($day = 1; $day <= $currentDay; $day++) {
                $dateString = "$currentYear-$currentMonth-$day";
                $dailyLabels[] = date('d M', strtotime($dateString));
                $dailyData[] = 0; // Initialize with 0
            }

            $dailyExpenseQuery = "
                SELECT DAY(date) as transaction_day, SUM(amount) as total_amount
                FROM transactions
                WHERE user_id = $userId
                AND transactionType = 'expense'
                AND MONTH(date) = $currentMonth
                AND YEAR(date) = $currentYear
                AND DAY(date) <= $currentDay
                GROUP BY DAY(date)
                ORDER BY DAY(date) ASC
            ";
            $dailyExpenseResult = mysqli_query($connect, $dailyExpenseQuery);
            
            if($dailyExpenseResult) {
                while($row = mysqli_fetch_assoc($dailyExpenseResult)){
                    $dayIndex = intval($row['transaction_day']) - 1;
                    if(isset($dailyData[$dayIndex])) {
                        $dailyData[$dayIndex] = floatval($row['total_amount']);
                    }
                }
            }
            
            $hasDailyData = count(array_filter($dailyData)) > 0;

        ?>
        <body>
            <section id="main">
                <span id="main-title">
                    <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
                    <a>DASHBOARD</a>
                    <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                        <button id="logout-btn" type="submit">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                </span>
                <hr/>
                <section id="dashboard-container">
                    <div class="item-container">
                        <div class="item-content" id="transaction-container">
                            <div class="chart-header">
                                <h1 style="text-align:left; margin-bottom:-0.8em;">Predicted Expense Timeline by Period <span class="click-indicator"><br/>(Click the charts for more details!)</span></h1>
                                <span id="info-icon">
                                    <i class="fa-solid fa-circle-question"></i>
                                    <div class="tooltip-container">
                                    </div>
                                    <div class="tooltip">
                                            <p>The chart is generated by an AI model and is not guaranteed to be accurate. The chart is predicted expense will be updated every 25th day of the month. Click on the chart to learn more about how predictions work.</p>
                                    </div>
                                </span>
                                <div class="filter-buttons">
                                    <button class="filter-btn active" data-period="1M">Monthly</button>
                                    <button class="filter-btn" data-period="3M">Quarterly</button>
                                    <button class="filter-btn" data-period="6M">Semi-Annual</button>
                                    <button class="filter-btn" data-period="1Y">Yearly</button>
                                </div>
                            </div>
                            <div class="item-content-chart">
                                <div class="chart-loader" id="expense-timeline-loader" style="display: none;">
                                    <div class="loader-spinner"></div>
                                    <p>Loading AI Predictions...</p>
                                </div>
                                <canvas id="expenseTimelineChart"></canvas>
                            </div>
                        </div>
                        <div class="item-content" id="expense-habit-container">
                            <div class="chart-header">
                                <h1>Ways to Control Expense</h1>
                                <span id="info-icon2">
                                    <i class="fa-solid fa-circle-question"></i>
                                    <div class="tooltip-container2">
                                    </div>
                                    <div class="tooltip2">
                                        <p>This card will show the suggested expense habit of the user based on the user's spending pattern. The suggested expense habit is generated by an AI model.</p>
                                    </div>
                                </span>
                            </div>
                            <div class="item-content-chat-box-container">
                                <div class="chart-loader" id="expense-suggestions-loader" style="display: none;">
                                    <div class="loader-spinner"></div>
                                    <p>Generating AI Suggestions...</p>
                                </div>
                                <div id="expense-suggestions-content">
                                    <div class="item-content-chat-box">
                                        <div class="chat-box-header">
                                            <p>Loading suggestions...</p>
                                        </div>
                                        <div class="chat-box-content">
                                            <p>Please wait while we analyze your spending patterns.</p>
                                        </div>
                                    </div>
                                    <div class="item-content-chat-box">
                                        <div class="chat-box-header">
                                            <p>Loading suggestions...</p>
                                        </div>
                                        <div class="chat-box-content">
                                            <p>Please wait while we analyze your spending patterns.</p>
                                        </div>
                                    </div>
                                    <div class="item-content-chat-box">
                                        <div class="chat-box-header">
                                            <p>Loading suggestions...</p>
                                        </div>
                                        <div class="chat-box-content">
                                            <p>Please wait while we analyze your spending patterns.</p>
                                        </div>
                                    </div>
                                    <div class="item-content-chat-box">
                                        <div class="chat-box-header">
                                            <p>Loading suggestions...</p>
                                        </div>
                                        <div class="chat-box-content">
                                            <p>Please wait while we analyze your spending patterns.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item-container">
                        <div class="item-content" id="category-transaction-container">
                            <div class="chart-header">
                                <h1>Categories Transaction</h1>
                                <div class="month-selector">
                                    <label for="month-select">Select Month:</label>
                                    <select id="month-select">
                                        <?php
                                        // Generate options for the last 12 months in descending order (most recent first)
                                        $currentYear = date('Y');
                                        $currentMonth = date('m');
                                        
                                        for ($i = 0; $i <= 11; $i++) {
                                            $month = $currentMonth - $i;
                                            $year = $currentYear;
                                            
                                            if ($month <= 0) {
                                                $month += 12;
                                                $year--;
                                            }
                                            
                                            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                                            $selected = ($i == 0) ? 'selected' : '';
                                            $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
                                            
                                            echo "<option value='$year-$monthStr' $selected>$monthName</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="item-content-chart">
                                <canvas id="nestedDoughnutChart" <?php if (!$hasData) echo 'style="display:none;"'; ?>></canvas>
                                <div id="no-data-message" style="text-align:center;<?php echo $hasData ? 'display:none;' : 'display:block;'; ?> color:#5f2824; font-size:1.1em; margin-top:1em;">No Transaction Data Found</div>
                                <div id="legends-container" class="legends-container" <?php if (!$hasData) echo 'style="display:none;"'; ?>>
                                    <div id="income-legend" class="chart-legend-wrapper" <?php if (!$hasIncomeData) echo 'style="display:none;"'; ?>>
                                        <h2>Income</h2>
                                        <div class="chart-legend" id="income-legend-content">
                                            <?php foreach ($incomeLabels as $i => $label): ?>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background:<?= $incomeColors[$i] ?>;"></span>
                                                    <span class="legend-label" title="<?= htmlspecialchars($label) ?>"> <?= htmlspecialchars($label) ?> </span>
                                                    <span class="legend-value">RM<?= number_format($incomeData[$i], 2) ?></span>
                                                    <span class="legend-percentage"><?= $incomePercentages[$i] ?? 0 ?>%</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div id="expense-legend" class="chart-legend-wrapper" <?php if (!$hasExpenseData) echo 'style="display:none;"'; ?>>
                                        <h2>Expense</h2>
                                        <div class="chart-legend" id="expense-legend-content">
                                            <?php foreach ($expenseLabels as $i => $label): ?>
                                                <div class="legend-item">
                                                    <span class="legend-color" style="background:<?= $expenseColors[$i] ?>;"></span>
                                                    <span class="legend-label" title="<?= htmlspecialchars($label) ?>"> <?= htmlspecialchars($label) ?> </span>
                                                    <span class="legend-value">RM<?= number_format($expenseData[$i], 2) ?></span>
                                                    <span class="legend-percentage"><?= $expensePercentages[$i] ?? 0 ?>%</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item-content" id="daily-expense-container">
                            <div class="chart-header">
                                <h1>Daily Expense</h1>
                                <div class="month-selector">
                                    <label for="daily-month-select">Select Month:</label>
                                    <select id="daily-month-select">
                                        <?php
                                        // Generate options for the last 12 months in descending order (most recent first)
                                        $currentYear = date('Y');
                                        $currentMonth = date('m');
                                        
                                        for ($i = 0; $i <= 11; $i++) {
                                            $month = $currentMonth - $i;
                                            $year = $currentYear;
                                            
                                            if ($month <= 0) {
                                                $month += 12;
                                                $year--;
                                            }
                                            
                                            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                                            $selected = ($i == 0) ? 'selected' : '';
                                            $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
                                            
                                            echo "<option value='$year-$monthStr' $selected>$monthName</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="item-content-chart">
                                <canvas id="lineChart" <?php if (!$hasDailyData) echo 'style="display:none;"'; ?>></canvas>
                                <div id="no-daily-data-message" style="text-align:center;<?php if ($hasDailyData) echo 'display:none;'; ?>">No Daily Expense Data Found</div>
                            </div>
                        </div>
                    </div>
                </section>
            </section>
            <script>
                let categoryChart = null;
                let dailyExpenseChart = null;
                let currentMonth = '<?php echo date('Y-m'); ?>';
                const currentYear = '<?php echo date('Y'); ?>';

                // Initialize category chart
                function initializeCategoryChart() {
                    const ctx = document.getElementById("nestedDoughnutChart").getContext("2d");
                    
                const incomeLabels = <?php echo json_encode($incomeLabels); ?>;
                const incomeData = <?php echo json_encode($incomeData); ?>;
                const incomeColors = <?php echo json_encode(array_slice($incomeColors, 0, count($incomeData))); ?>;

                const expenseLabels = <?php echo json_encode($expenseLabels); ?>;
                const expenseData = <?php echo json_encode($expenseData); ?>;
                const expenseColors = <?php echo json_encode(array_slice($expenseColors, 0, count($expenseData))); ?>;

                const hasData = <?php echo json_encode($hasData); ?>;

                if (hasData) {
                    const chartDatasets = [];
                    const chartLabels = incomeLabels.concat(expenseLabels);

                    if (expenseData.length > 0) {
                        chartDatasets.push({
                            label: 'Expenses (RM)',
                            data: expenseData,
                            backgroundColor: expenseColors,
                            borderColor: 'rgb(26, 26, 26)',
                            borderWidth: 1.5,
                            hoverBorderWidth: 2,
                        });
                    }

                    if (incomeData.length > 0) {
                        chartDatasets.push({
                            label: 'Income (RM)',
                            data: incomeData,
                            backgroundColor: incomeColors,
                            borderColor: 'rgb(26, 26, 26)',
                            borderWidth: 1.5,
                            hoverBorderWidth: 2,
                        });
                    }
                        categoryChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: incomeLabels.concat(expenseLabels),
                            datasets: chartDatasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            const tooltipItem = context[0];
                                            const index = tooltipItem.dataIndex;
                                            const dataset = tooltipItem.dataset;
                                            let label;
                                            if (dataset.label === 'Expenses (RM)') {
                                                label = expenseLabels[index];
                                            } else if (dataset.label === 'Income (RM)') {
                                                label = incomeLabels[index];
                                            } else {
                                                label = tooltipItem.label || 'Unknown';
                                            }
                                            return label;
                                        },
                                        label: function(context) {
                                            const index = context.dataIndex;
                                            const dataset = context.dataset;
                                            const value = dataset.data[index];
                                            let label;

                                            if (dataset.label === 'Expenses (RM)') {
                                                label = expenseLabels[index];
                                            } else if (dataset.label === 'Income (RM)') {
                                                label = incomeLabels[index];
                                            } else {
                                                label = context.label || 'Unknown';
                                            }
                                            
                                            return `${label}: RM ${parseFloat(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    }
                }

                // Initialize daily expense chart
                function initializeDailyExpenseChart() {
                const dailyLabels = <?php echo json_encode($dailyLabels); ?>;
                const dailyData = <?php echo json_encode($dailyData); ?>;
                const hasDailyData = <?php echo json_encode($hasDailyData); ?>;

                if(hasDailyData) {
                    const ctxLine = document.getElementById("lineChart").getContext("2d");
                        dailyExpenseChart = new Chart(ctxLine, {
                        type: 'line',
                        data: {
                            labels: dailyLabels,
                            datasets: [{
                                label: 'Daily Expense (RM)',
                                data: dailyData,
                                fill: true,
                                backgroundColor: 'rgba(255, 130, 130, 0.27)',
                                borderColor: 'rgb(138, 0, 0)',
                                borderWidth: 2.5,
                                hoverBorderWidth: 3,
                                tension: 0.5,
                                pointBackgroundColor: 'rgba(255, 130, 130, 0.78)',
                                pointBorderColor: 'rgb(138, 0, 0)',
                                pointBorderWidth: 2,
                                pointRadius: 3,
                                pointHoverRadius: 7,
                                pointStyle: 'circle'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                            stepSize: 20
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                    }
                }

                // Function to update category chart with new data
                function updateCategoryChart(data) {
                    if (categoryChart) {
                        categoryChart.destroy();
                    }

                    const ctx = document.getElementById("nestedDoughnutChart").getContext("2d");
                    const hasData = data.hasExpenseData || data.hasIncomeData;

                    if (hasData) {
                        const chartDatasets = [];
                        const chartLabels = data.incomeLabels.concat(data.expenseLabels);

                        if (data.expenseData.length > 0) {
                            chartDatasets.push({
                                label: 'Expenses (RM)',
                                data: data.expenseData,
                                backgroundColor: data.expenseColors,
                                borderColor: 'rgb(26, 26, 26)',
                                borderWidth: 1.5,
                                hoverBorderWidth: 2,
                            });
                        }

                        if (data.incomeData.length > 0) {
                            chartDatasets.push({
                                label: 'Income (RM)',
                                data: data.incomeData,
                                backgroundColor: data.incomeColors,
                                borderColor: 'rgb(26, 26, 26)',
                                borderWidth: 1.5,
                                hoverBorderWidth: 2,
                            });
                        }

                        categoryChart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: data.incomeLabels.concat(data.expenseLabels),
                                datasets: chartDatasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            title: function(context) {
                                                const tooltipItem = context[0];
                                                const index = tooltipItem.dataIndex;
                                                const dataset = tooltipItem.dataset;
                                                let label;
                                                if (dataset.label === 'Expenses (RM)') {
                                                    label = data.expenseLabels[index];
                                                } else if (dataset.label === 'Income (RM)') {
                                                    label = data.incomeLabels[index];
                                                } else {
                                                    label = tooltipItem.label || 'Unknown';
                                                }
                                                return label;
                                            },
                                            label: function(context) {
                                                const index = context.dataIndex;
                                                const dataset = context.dataset;
                                                const value = dataset.data[index];
                                                let label;
                                                
                                                if (dataset.label === 'Expenses (RM)') {
                                                    label = data.expenseLabels[index];
                                                } else if (dataset.label === 'Income (RM)') {
                                                    label = data.incomeLabels[index];
                                                } else {
                                                    label = context.label || 'Unknown';
                                                }
                                                
                                                return `${label}: RM ${parseFloat(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Update chart visibility
                    const chart = document.getElementById("nestedDoughnutChart");
                    const noDataMessage = document.getElementById("no-data-message");
                    const legendsContainer = document.getElementById("legends-container");

                    if (hasData) {
                        chart.style.display = "block";
                        noDataMessage.style.display = "none";
                        legendsContainer.style.display = "flex";
                    } else {
                        chart.style.display = "none";
                        noDataMessage.style.display = "block";
                        legendsContainer.style.display = "none";
                    }

                    // Update legends
                    updateLegends(data);
                }

                // Function to update daily expense chart with new data
                function updateDailyExpenseChart(data) {
                    if (dailyExpenseChart) {
                        dailyExpenseChart.destroy();
                    }

                    const ctxLine = document.getElementById("lineChart").getContext("2d");
                    const chart = document.getElementById("lineChart");
                    const noDataMessage = document.getElementById("no-daily-data-message");

                    if(data.hasDailyData) {
                        dailyExpenseChart = new Chart(ctxLine, {
                            type: 'line',
                            data: {
                                labels: data.dailyLabels,
                                datasets: [{
                                    label: 'Daily Expense (RM)',
                                    data: data.dailyData,
                                    fill: true,
                                    backgroundColor: 'rgba(255, 130, 130, 0.27)',
                                    borderColor: 'rgb(138, 0, 0)',
                                    borderWidth: 2.5,
                                    hoverBorderWidth: 3,
                                    tension: 0.5,
                                    pointBackgroundColor: 'rgba(255, 130, 130, 0.78)',
                                    pointBorderColor: 'rgb(138, 0, 0)',
                                    pointBorderWidth: 2,
                                    pointRadius: 3,
                                    pointHoverRadius: 7,
                                    pointStyle: 'circle'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 20
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });

                        chart.style.display = "block";
                        noDataMessage.style.display = "none";
                    } else {
                        chart.style.display = "none";
                        noDataMessage.style.display = "block";
                    }
                }

                // Function to update legends
                function updateLegends(data) {
                    const incomeLegend = document.getElementById("income-legend");
                    const expenseLegend = document.getElementById("expense-legend");
                    const incomeLegendContent = document.getElementById("income-legend-content");
                    const expenseLegendContent = document.getElementById("expense-legend-content");

                    // Update income legend
                    if (data.hasIncomeData) {
                        incomeLegend.style.display = "block";
                        incomeLegendContent.innerHTML = '';
                        data.incomeLabels.forEach((label, i) => {
                            const legendItem = document.createElement('div');
                            legendItem.className = 'legend-item';
                            legendItem.innerHTML = `
                                <span class="legend-color" style="background:${data.incomeColors[i]}"></span>
                                <span class="legend-label" title="${label}">${label}</span>
                                <span class="legend-value">RM${parseFloat(data.incomeData[i]).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                <span class="legend-percentage">${data.incomePercentages[i]}%</span>
                            `;
                            incomeLegendContent.appendChild(legendItem);
                        });
                    } else {
                        incomeLegend.style.display = "none";
                    }

                    // Update expense legend
                    if (data.hasExpenseData) {
                        expenseLegend.style.display = "block";
                        expenseLegendContent.innerHTML = '';
                        data.expenseLabels.forEach((label, i) => {
                            const legendItem = document.createElement('div');
                            legendItem.className = 'legend-item';
                            legendItem.innerHTML = `
                                <span class="legend-color" style="background:${data.expenseColors[i]}"></span>
                                <span class="legend-label" title="${label}">${label}</span>
                                <span class="legend-value">RM${parseFloat(data.expenseData[i]).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                <span class="legend-percentage">${data.expensePercentages[i]}%</span>
                            `;
                            expenseLegendContent.appendChild(legendItem);
                        });
                    } else {
                        expenseLegend.style.display = "none";
                    }
                }

                // Function to fetch category data for selected month
                function fetchCategoryData(selectedMonth) {
                    fetch(`../FUNCTION/getCategoryData.php?month=${selectedMonth}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateCategoryChart(data);
                                currentMonth = selectedMonth;
                            } else {
                                console.error('Failed to fetch category data:', data.message);
                            }
                        })
                        .catch(error => console.error('Error fetching category data:', error));
                }

                // Function to fetch daily expense data for selected month
                function fetchDailyExpenseData(selectedMonth) {
                    fetch(`../FUNCTION/getDailyExpenseData.php?month=${selectedMonth}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateDailyExpenseChart(data);
                            } else {
                                console.error('Failed to fetch daily expense data:', data.message);
                            }
                        })
                        .catch(error => console.error('Error fetching daily expense data:', error));
                }

                // Initialize charts on page load
                document.addEventListener('DOMContentLoaded', function() {
                    initializeCategoryChart();
                    initializeDailyExpenseChart();

                    // Add event listener for category month selector
                    const monthSelect = document.getElementById('month-select');
                    monthSelect.addEventListener('change', function() {
                        const selectedMonth = this.value;
                        fetchCategoryData(selectedMonth);
                    });

                    // Add event listener for daily expense month selector
                    const dailyMonthSelect = document.getElementById('daily-month-select');
                    dailyMonthSelect.addEventListener('change', function() {
                        const selectedMonth = this.value;
                        fetchDailyExpenseData(selectedMonth);
                    });

                    // Initialize expense timeline chart
                    const expenseTimelineCtx = document.getElementById('expenseTimelineChart').getContext('2d');
                    let expenseTimelineChart = new Chart(expenseTimelineCtx, {
                        type: 'bar',
                        data: {
                            labels: [],
                            datasets: [{
                                type: 'bar',
                                label: 'Actual Expense',
                                data: [],
                                order: 2,
                                backgroundColor: 'rgba(126, 172, 220, 0.34)',
                                borderColor: 'rgba(21, 74, 120, 0.79)',
                                borderWidth: 2.5,
                                hoverBorderWidth: 3,
                            }, {
                                type: 'line',
                                label: 'Predicted Expense',
                                data: [],
                                order: 1,
                                backgroundColor: 'rgba(255, 99, 99, 0.73)',
                                borderColor: 'rgb(144, 0, 0)',
                                borderWidth: 3,
                                fill: false,
                                pointStyle: 'circle',
                                tension: 0.1,
                                pointRadius: 4,
                                pointHoverRadius: 7,
                                pointHoverBorderWidth: 3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            onClick: function(event, elements) {
                                // Get the active period from the filter buttons
                                const activeButton = document.querySelector('#transaction-container .filter-btn.active');
                                const period = activeButton ? activeButton.dataset.period : '1M';
                                
                                // Redirect to prediction explanation page
                                window.location.href = `predictionExplanation.php?period=${period}`;
                            },
                            onHover: function(event, elements) {
                                // Change cursor to pointer when hovering over the chart
                                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return value.toLocaleString();
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Amount (RM)'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Time Period'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += 'RM ' + context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    const filterButtons = document.querySelectorAll('#transaction-container .filter-btn');
                    const expenseTimelineLoader = document.getElementById('expense-timeline-loader');

                    function fetchExpenseData(period) {
                        expenseTimelineLoader.style.display = 'flex';
                        
                        fetch(`../FUNCTION/geminiExpensePrediction.php?period=${period}`)
                            .then(response => response.json())
                            .then(data => {
                                if(data.success) {
                                    expenseTimelineChart.data.labels = data.labels;
                                    expenseTimelineChart.data.datasets[0].data = data.data; // Actuals
                                    expenseTimelineChart.data.datasets[1].data = data.predictedData; // Predictions
                                    expenseTimelineChart.update();
                                } else {
                                    console.error('Failed to fetch expense data:', data.message);
                                }
                            })
                            .catch(error => console.error('Error fetching expense data:', error))
                            .finally(() => {
                                expenseTimelineLoader.style.display = 'none';
                            });
                    }

                    filterButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            filterButtons.forEach(btn => btn.classList.remove('active'));
                            this.classList.add('active');
                            
                            const period = this.dataset.period;
                            fetchExpenseData(period);
                        });
                    });

                    // Initial fetch for Monthly
                    fetchExpenseData('1M');

                    // Load expense control suggestions
                    loadExpenseSuggestions();
                });

                // Function to load expense control suggestions
                function loadExpenseSuggestions() {
                    const suggestionsLoader = document.getElementById('expense-suggestions-loader');
                    const suggestionsContent = document.getElementById('expense-suggestions-content');
                    
                    suggestionsLoader.style.display = 'flex';
                    suggestionsContent.style.display = 'none';
                    
                    fetch('../FUNCTION/expenseControlSuggestions.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateExpenseSuggestions(data.suggestions);
                            } else {
                                console.error('Failed to fetch expense suggestions:', data.message);
                                showFallbackSuggestions();
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching expense suggestions:', error);
                            showFallbackSuggestions();
                        })
                        .finally(() => {
                            suggestionsLoader.style.display = 'none';
                            suggestionsContent.style.display = 'block';
                        });
                }

                // Function to update expense suggestions in the UI
                function updateExpenseSuggestions(suggestions) {
                    const suggestionsContent = document.getElementById('expense-suggestions-content');
                    
                    // Clear existing content
                    suggestionsContent.innerHTML = '';
                    
                    suggestions.forEach((suggestion, index) => {
                        const chatBoxClass = 'item-content-chat-box';
                        
                        const chatBox = document.createElement('div');
                        chatBox.className = chatBoxClass;
                        chatBox.innerHTML = `
                            <div class="chat-box-header">
                                <p>${suggestion.title}</p>
                            </div>
                            <div class="chat-box-content">
                                <p>${suggestion.suggestion}</p>
                            </div>
                        `;
                        
                        suggestionsContent.appendChild(chatBox);
                    });
                }

                // Function to show fallback suggestions if AI fails
                function showFallbackSuggestions() {
                    const fallbackSuggestions = [
                        {
                            title: "Track Your Spending",
                            suggestion: "Start by tracking all your daily expenses to identify where your money goes."
                        },
                        {
                            title: "Set a Budget",
                            suggestion: "Create a monthly budget for different categories to control your spending."
                        },
                        {
                            title: "Save Regularly",
                            suggestion: "Set aside 20% of your income for savings and emergency funds."
                        },
                        {
                            title: "Review Subscriptions",
                            suggestion: "Check for unused subscriptions and cancel them to save money monthly."
                        }
                    ];
                    
                    updateExpenseSuggestions(fallbackSuggestions);
                }
            </script>
        </body>
    </body>
</html>