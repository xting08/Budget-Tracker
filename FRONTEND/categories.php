<?php
include '../FUNCTION/mainFunc.inc.php';

// Handle AJAX category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id']) && isset($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    $categoryId = intval($_POST['category_id']);
    $userId = intval($_SESSION['id']);

    // Only allow deletion of user's own categories (not default ones)
    $stmt = $connect->prepare("SELECT * FROM categories WHERE category_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $categoryId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Category not found or not allowed to delete.']);
        exit;
    }

    // Optionally: handle related transactions (e.g., set their category_id to NULL or delete them)
    // $connect->query("UPDATE transactions SET category_id = NULL WHERE category_id = $categoryId AND user_id = $userId");

    // Delete the category
    $stmt = $connect->prepare("DELETE FROM categories WHERE category_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $categoryId, $userId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete category.']);
    }
    $stmt->close();
    exit;
}
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
    <link rel="stylesheet" href="../CSS/categories.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 1em;
        }
        .category-header h1 {
            margin: 0;
            color: #232640;
        }
        .month-selector {
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .month-selector label {
            font-size: 0.9em;
            font-weight: 600;
            color: #516270;
        }
        .month-selector select {
            padding: 0.4em 0.8em;
            border: 1px solid #adb8c0;
            background-color: #f0f0f0;
            border-radius: 0.5em;
            cursor: pointer;
            font-size: 0.8em;
            font-weight: 600;
            color: #516270;
            min-width: 120px;
        }
    </style>
</head>

<body>
    <?php include '../FUNCTION/sideNav.inc.php'; ?>
    <section id="main">
        <span id="main-title">
            <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
            <a>CATEGORIES</a>
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
                    // Get expense by category for the current month
                    $userId = intval($_SESSION['id']);
                    $expenseQuery = "
                        SELECT c.categoryName, SUM(t.amount) as total
                        FROM transactions t
                        LEFT JOIN categories c ON t.category_id = c.category_id
                        WHERE t.user_id = $userId
                          AND t.transactionType = 'expense'
                          AND MONTH(t.date) = MONTH(CURDATE())
                          AND YEAR(t.date) = YEAR(CURDATE())
                          AND (c.user_id = 0 OR c.user_id = $userId)
                        GROUP BY t.category_id
                        ORDER BY total DESC
                    ";
                    $expenseResult = mysqli_query($connect, $expenseQuery);

                    $expenseLabels = [];
                    $expenseData = [];
                    $expenseColors = [
                        '#ffbcbc', '#ffdba8', '#fff8b7', '#e3ffa3', '#b0ffb1', '#c1fffe', '#9edbec', '#9ea3ec', '#de9eec'
                    ];
                    $incomeColors = [
                        '#772e2e', '#976e33', '#ab9f34', '#7fa131', '#319832', '#2a7c7b', '#255f6f', '#161a5b', '#44154f'
                    ];
                    while ($row = mysqli_fetch_assoc($expenseResult)) {
                        $expenseLabels[] = $row['categoryName'];
                        $expenseData[] = floatval($row['total']);
                    }
                    $hasExpenseData = array_sum($expenseData) > 0;

                    // Get income by category for the current month
                    $incomeQuery = "
                        SELECT c.categoryName, SUM(t.amount) as total
                        FROM transactions t
                        LEFT JOIN categories c ON t.category_id = c.category_id
                        WHERE t.user_id = $userId
                          AND t.transactionType = 'income'
                          AND MONTH(t.date) = MONTH(CURDATE())
                          AND YEAR(t.date) = YEAR(CURDATE())
                          AND (c.user_id = 0 OR c.user_id = $userId)
                        GROUP BY t.category_id
                        ORDER BY total DESC
                    ";
                    $incomeResult = mysqli_query($connect, $incomeQuery);

                    $incomeLabels = [];
                    $incomeData = [];
                    while ($row = mysqli_fetch_assoc($incomeResult)) {
                        $incomeLabels[] = $row['categoryName'];
                        $incomeData[] = floatval($row['total']);
                    }
                    $hasIncomeData = array_sum($incomeData) > 0;
                    // Calculate percentages for expenses
                    $totalExpense = array_sum($expenseData);
                    $expensePercentages = [];
                    foreach ($expenseData as $value) {
                        $expensePercentages[] = $totalExpense > 0 ? round(($value / $totalExpense) * 100, 2) : 0;
                    }
                    // Calculate percentages for income
                    $totalIncome = array_sum($incomeData);
                    $incomePercentages = [];
                    foreach ($incomeData as $value) {
                        $incomePercentages[] = $totalIncome > 0 ? round(($value / $totalIncome) * 100, 2) : 0;
                    }
                    ?>
                    <div style="display: flex; flex-direction: column; gap: 3em; justify-content: center; align-items: center;">
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 1.5em; width: 100%; justify-content: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; width: 210px; margin-top: 1em;">
                                <div style="font-size: 0.9em; width: 100%; text-align: left; font-weight: bold; margin-bottom: 1.7em; color: #5f2824;">Expenses by Category (RM)</div>
                                <canvas id="expenseChart" <?php if (!$hasExpenseData) echo 'style="display:none;"'; ?>></canvas>
                                <div style="text-align:center;<?php echo $hasExpenseData ? 'display:none;' : 'display:block;'; ?> color:#5f2824; font-size:1.1em; margin-top:1em;">No Expense Data Found</div>
                            </div>
                            <?php if ($hasExpenseData): ?>
                            <div style="margin-top:0; font-size: 0.8em; font-weight: 600;">
                                <?php foreach ($expenseLabels as $i => $label): ?>
                                    <div style="display:flex;align-items:center;margin-bottom:0.5em;">
                                        <span style="display:inline-block;width:16px;height:16px;background:<?= $expenseColors[$i % count($expenseColors)] ?>;border-radius:50%;margin-right:8px; border: 2px solid #5f2824;"></span>
                                        <span style="width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; color: #5f2824;"> <?= htmlspecialchars($label) ?> </span>
                                        <span style="width:80px;text-align:right; color: #5f2824;">RM<?= number_format($expenseData[$i], 2) ?></span>
                                        <span style="width:60px;text-align:right; color: #5f2824;"> <?= $expensePercentages[$i] ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 1.5em; width: 100%; justify-content: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; width: 210px;">
                                <div style="font-size: 0.9em; width: 100%; text-align: left; font-weight: bold; margin-bottom: 1.7em; color: #294c4b;">Income by Category (RM)</div>
                                <canvas id="incomeChart" <?php if (!$hasIncomeData) echo 'style="display:none;"'; ?>></canvas>
                                <div style="text-align:center;<?php echo $hasIncomeData ? 'display:none;' : 'display:block;'; ?> color:#294c4b; font-size:1.1em; margin-top:1em;">No Income Data Found</div>
                            </div>
                            <?php if ($hasIncomeData): ?>
                            <div style="margin-top:0; font-size: 0.8em; font-weight: 600;">
                                <?php foreach ($incomeLabels as $i => $label): ?>
                                    <div style="display:flex;align-items:center;margin-bottom:0.5em;">
                                        <span style="display:inline-block;width:16px;height:16px;background:<?= $incomeColors[$i % count($incomeColors)] ?>;border-radius:50%;margin-right:8px; border: 2px solid #294c4b;"></span>
                                        <span style="width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; color: #294c4b;"> <?= htmlspecialchars($label) ?> </span>
                                        <span style="width:80px;text-align:right; color: #294c4b;">RM<?= number_format($incomeData[$i], 2) ?></span>
                                        <span style="width:60px;text-align:right; color: #294c4b;"> <?= $incomePercentages[$i] ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <script>
                        const expenseLabels = <?php echo json_encode($expenseLabels); ?>;
                        const expenseData = <?php echo json_encode($expenseData); ?>;
                        const incomeLabels = <?php echo json_encode($incomeLabels); ?>;
                        const incomeData = <?php echo json_encode($incomeData); ?>;
                        const expenseColors = <?php echo json_encode($expenseColors); ?>;
                        const incomeColors = <?php echo json_encode($incomeColors); ?>;

                        if (expenseData.length > 0 && expenseData.reduce((a, b) => a + b, 0) > 0) {
                            const ctxExpense = document.getElementById("expenseChart").getContext("2d");
                            new Chart(ctxExpense, {
                                type: 'doughnut',
                                data: {
                                    labels: expenseLabels,
                                    datasets: [{
                                        label: 'Expenses (RM)',
                                        data: expenseData,
                                        backgroundColor: expenseColors,
                                        borderColor: 'rgb(26, 26, 26)',
                                        borderWidth: 1.5,
                                        hoverBorderWidth: 2,
                                        hoverBorderColor: 'rgb(26, 26, 26)'
                                    }]
                                },
                                options: {
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    }
                                }
                            });
                        }
                        if (incomeData.length > 0 && incomeData.reduce((a, b) => a + b, 0) > 0) {
                            const ctxIncome = document.getElementById("incomeChart").getContext("2d");
                            new Chart(ctxIncome, {
                                type: 'doughnut',
                                data: {
                                    labels: incomeLabels,
                                    datasets: [{
                                        label: 'Income (RM)',
                                        data: incomeData,
                                        backgroundColor: incomeColors,
                                        borderColor: 'rgb(26, 26, 26)',
                                        borderWidth: 1.5,
                                        hoverBorderWidth: 2,
                                        hoverBorderColor: 'rgb(26, 26, 26)'
                                    }]
                                },
                                options: {
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    }
                                }
                            });
                        }
                    </script>
                </div>
                
                    <button id="add-transaction-btn" style="margin-top: 1em;"
                        onclick="window.location.href='../FRONTEND/addCategory.php'">ADD NEW CATEGORY
                    </button>
                </div>
            </section>
            <section id="transaction-history">
                <div class="category-header">
                    <h1>All Categories</h1>
                    <div class="month-selector">
                        <label for="month-select">Select Month:</label>
                        <select id="month-select">
                            <?php
                            // Generate options for the last 12 months
                            $currentYear = date('Y');
                            $currentMonth = date('m');
                            for ($i = 0; $i < 12; $i++) {
                                $month = date('m', strtotime("-$i months"));
                                $year = date('Y', strtotime("-$i months"));
                                $monthName = date('F Y', strtotime("-$i months"));
                                $value = "$year-$month";
                                $selected = (date('Y-m') === $value) ? 'selected' : '';
                                echo "<option value='$value' $selected>$monthName</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <form id="category-filter-form">
                    <button type="button" id="all-btn" class="filter-btn active" data-type="all">All</button>
                    <button type="button" id="income-btn" class="filter-btn" data-type="income">Income</button>
                    <button type="button" id="expense-btn" class="filter-btn" data-type="expense">Expense</button>
                </form>
                <div id="transaction-history-container">
                    
                </div>
            </section>
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const tableContainer = document.getElementById('transaction-history-container');
            const monthSelect = document.getElementById('month-select');

            function loadCategories(type, month) {
                tableContainer.innerHTML = '<div style="text-align: center; padding: 2em;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 2em; color: #294c4b;"></i></div>';
                fetch(`categoriesTable.php?type=${type}&month=${month}`)
                    .then(response => response.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        tableContainer.innerHTML = '<div style="text-align: center; color: #5f2824;">Error loading categories.</div>';
                    });
            }

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    const type = this.dataset.type;
                    const selectedMonth = monthSelect.value;
                    loadCategories(type, selectedMonth);
                });
            });

            monthSelect.addEventListener('change', function() {
                const selectedMonth = this.value;
                const activeBtn = document.querySelector('.filter-btn.active');
                const type = activeBtn ? activeBtn.dataset.type : 'all';
                loadCategories(type, selectedMonth);
            });

            // Load categories by default on page load
            const initialType = 'all';
            const initialMonth = monthSelect.value;
            loadCategories(initialType, initialMonth);

            // Expose loadCategories globally for deleteCategory to use
            window.loadCategories = function() {
                const activeBtn = document.querySelector('.filter-btn.active');
                const type = activeBtn ? activeBtn.dataset.type : 'all';
                const selectedMonth = monthSelect.value;
                loadCategories(type, selectedMonth);
            };
        });

        function deleteCategory(categoryId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you really want to delete this category?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#232640',
                confirmButtonText: 'Yes, continue'
            }).then((firstResult) => {
                if (firstResult.isConfirmed) {
                    Swal.fire({
                        title: 'This action cannot be undone!',
                        text: 'Are you absolutely sure you want to delete this category?',
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#232640',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((secondResult) => {
                        if (secondResult.isConfirmed) {
                            fetch('categories.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'category_id=' + encodeURIComponent(categoryId) + '&ajax_delete=1'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: 'Category has been deleted.',
                                        showConfirmButton: false,
                                        timer: 1200
                                    });
                                    setTimeout(() => {
                                        if (typeof window.loadCategories === 'function') {
                                            window.loadCategories();
                                        } else {
                                            location.reload();
                                        }
                                    }, 1300);
                                } else {
                                    Swal.fire('Error', data.message || 'Failed to delete category.', 'error');
                                }
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>
</html>