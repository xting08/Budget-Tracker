<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
}
    include '../FUNCTION/addTransaction.inc.php';
    // Fetch shared expenses for dropdown (show each only once, prioritize recipient entry)
    $shared_expenses = [];
    if (isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
        $user_email = $email;
        $connect = OpenCon();
        $query = "SELECT se.shareExpense_id, se.name, se.amount, COALESCE(ser.percentage, 100) as percentage
                  FROM shareexpenses se
                  LEFT JOIN shared_expense_recipients ser
                    ON se.shareExpense_id = ser.shared_expense_id
                    AND ser.recipient_email = ? AND ser.status = 'accepted'
                  WHERE se.user_id = ? OR ser.recipient_email = ?
                  GROUP BY se.shareExpense_id
                  ORDER BY se.date_created DESC";
        $stmt = mysqli_prepare($connect, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sis", $user_email, $user_id, $user_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                // Calculate user's share
                $my_share = $row['amount'] * $row['percentage'] / 100;
                // Calculate used amount
                $used_query = "SELECT COALESCE(SUM(amount), 0) as used FROM transactions WHERE user_id = ? AND shared_expense_id = ? AND transactionType = 'expense'";
                $used_stmt = mysqli_prepare($connect, $used_query);
                mysqli_stmt_bind_param($used_stmt, "ii", $user_id, $row['shareExpense_id']);
                mysqli_stmt_execute($used_stmt);
                $used_result = mysqli_stmt_get_result($used_stmt);
                $used = mysqli_fetch_assoc($used_result)['used'];
                $remaining = $my_share - $used;
                $row['my_share'] = $my_share;
                $row['used'] = $used;
                $row['remaining'] = $remaining;
                $shared_expenses[] = $row;
            }
        }
    }

    // Fetch budgets for dropdown
    $budgets = [];
    if (isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
        $connect = OpenCon();
        $query = "SELECT * FROM budgets WHERE user_id = '$user_id' AND endDate >= CURDATE()";
        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
        $budgets[] = [
            'budget_id' => $row['budget_id'],
            'budgetName' => $row['budgetName'],
            'amountLimit' => $row['amountLimit'],
            'currentSpend' => $row['currentSpend']
        ];
        }
    }

    // Fetch saving goals for dropdown
    $savingGoals = [];
    if (isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
        $connect = OpenCon();
        $query = "SELECT * FROM savinggoals WHERE user_id = '$user_id' AND endDate >= CURDATE()";
        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $savingGoals[] = [
                'saving_id' => $row['saving_id'],
                'savingName' => $row['savingName'],
                'targetAmount' => $row['targetAmount'],
                'currentAmount' => $row['currentAmount']
            ];
        }
    }

    $current_shared_expense = null;
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../IMG/favicon.png">
        <title>Cash Compass</title>
        <link rel="stylesheet" href="../CSS/addTransaction.css">
        <link rel="stylesheet" href="../CSS/scrollBar.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="../JS/previewReceipt.js"></script>
    </head>

    <body>
        <section id="main">
            <span id="main-title">
                <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
                <a>ADD TRANSACTION</a>
                <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                    <button id="logout-btn" type="submit">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </span>
            <hr />    
        </section>
        <nav id="button-nav">
            <button id="back-btn" onclick="window.location.href='../FRONTEND/transaction.php'">
                <i class="fa-solid fa-circle-left"></i> &nbsp;Back
            </button>
        </nav>
        <section id="add-transaction-container">
            <form action="../FUNCTION/addTransactionPost.php" method="post" id="add-transaction-form">
                <div class="form-group">
                    <label for="transaction-type">Transaction Type</label>
                    <select name="transaction-type" id="transaction-type" class="form-item" required>
                        <?php
                            if (!empty($formattedArray)) {
                                echo "<option value='" . strtolower($formattedArray[0]) . "' selected>" . $formattedArray[0] . "</option>
                                      <option value='" . strtolower($formattedArray[0] == "Expense" ? "Income" : "Expense") . "'>" . ($formattedArray[0] == "Expense" ? "Income" : "Expense") . "</option>";
                            } else {
                                echo "<option value='' disabled selected>Select Transaction Type</option>
                                      <option value='income'>Income</option>
                                      <option value='expense'>Expense</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group" id="budget-saving-container" style="display: none;">
                    <label for="budget-saving-checkbox">Add to:</label>
                    <div class="checkbox-container">
                        <input type="checkbox" id="budget-saving-checkbox" name="budget-saving-checkbox">
                        <span id="budget-saving-label">Budget</span>
                    </div>
                </div>
                <div class="form-group" id="budget-saving-select" style="display: none;">
                    <label for="budget-saving-id">Select:</label>
                    <select name="budget-saving-id" id="budget-saving-id" class="form-item">
                        <option value="" disabled selected>-- None --</option>
                        <?php
                            if (!empty($budgets)) {
                                foreach ($budgets as $budget) {
                                    echo "<option value='" . htmlspecialchars($budget['budget_id']) . "'>" . htmlspecialchars($budget['budgetName'] . " (Limit: RM" . number_format($budget['amountLimit'], 2) . ", Used: RM" . number_format($budget['currentSpend'], 2) . ")") . "</option>";
                                }
                            }
                            if (!empty($savingGoals)) {
                                foreach ($savingGoals as $saving) {
                                    echo "<option value='" . htmlspecialchars($saving['saving_id']) . "'>" . htmlspecialchars($saving['savingName'] . " (Target: RM" . number_format($saving['targetAmount'], 2) . ", Current: RM" . number_format($saving['currentAmount'], 2) . ")") . "</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transaction-amount">Transaction Amount</label>
                    <input type="number" step="0.01" min="0" name="transaction-amount" id="transaction-amount" class="form-item" placeholder="0.00"
                    value="<?php echo !empty($formattedArray) ? htmlspecialchars($formattedArray[1]) : ""; ?>" required>
                </div>
                <div class="form-group">
                    <label for="transaction-name">Transaction Name</label>
                    <input type="text" name="transaction-name" id="transaction-name" class="form-item" placeholder="Enter Transaction Name"
                    value="<?php echo !empty($formattedArray) ? htmlspecialchars($formattedArray[2]) : ""; ?>" required>
                </div>
                <div class="form-group">
                    <label for="transaction-date">Transaction Date</label>
                    <?php 
                        $dateValue = "";
                        if (!empty($formattedArray) && !empty($formattedArray[3])) {
                            $rawDate = $formattedArray[3];
                            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                                // Convert dd/mm/yyyy to yyyy-mm-dd
                                $parts = explode('/', $rawDate);
                                $dateValue = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                                // Already yyyy-mm-dd
                                $dateValue = $rawDate;
                            } else {
                                // Unknown format, leave blank or handle as needed
                                $dateValue = "";
                            }
                        }
                    ?>
                    <input type="date" name="transaction-date" id="transaction-date" class="form-item"
                    value="<?php echo htmlspecialchars($dateValue); ?>" required>
                </div>
                <div class="form-group">
                    <label for="transaction-category">Transaction Category</label>
                    <select name="transaction-category" id="transaction-category" class="form-item" required>
                        <?php getExpenseCategory($categoryRows); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transaction-description" required>Transaction Description</label>
                    <input type="text" name="transaction-description" id="transaction-description" class="form-item" placeholder="Enter Transaction Description"
                    value="<?php echo !empty($formattedArray) ? htmlspecialchars($formattedArray[4]) : ""; ?>">
                </div>
                <!-- Shared Expense Dropdown -->
                <div class="form-group" id="shared-expense-container">
                    <label for="shared_expense_id">Related Shared Expense (optional):</label>
                    <select name="shared_expense_id" id="shared_expense_id" class="form-item">
                        <option value="">-- None --</option>
                        <?php foreach ($shared_expenses as $se): ?>
                            <?php if ($se['remaining'] > 0): ?>
                            <option value="<?= $se['shareExpense_id'] ?>" <?= ($current_shared_expense == $se['shareExpense_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($se['name']) ?> (Share: RM<?= number_format($se['my_share'], 2) ?>, Remaining: RM<?= number_format($se['remaining'], 2) ?>)
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                    <input type="hidden" name="transaction-receipt" id="transaction-receipt"
                    value="<?php echo !empty($formattedArray) ? htmlspecialchars($formattedArray[5]) : "0"; ?>">
                    
                    <?php
                    if (!empty($formattedArray2)) {
                        foreach ($formattedArray2 as $index => $item) {
                            echo '<input type="hidden" name="items[' . $index . '][]" value="' . htmlspecialchars($item[0]) . '">';
                            echo '<input type="hidden" name="items[' . $index . '][]" value="' . htmlspecialchars($item[1]) . '">';
                        }
                    }
                    ?>
                    
                <div class="form-group">
                    <button type="submit" name="submit" class="form-item" id="add-transaction-btn">Add Transaction</button>
                </div>
            </form>
            <div id="add-transaction-image">
                <form method="post" enctype="multipart/form-data">
                    <div class="tooltip">
                        <i class="fa-solid fa-circle-question"></i>
                        <span class="tooltiptext1">&nbsp;</span>
                        <span class="tooltiptext">Upload a receipt to automatically fill in the transaction details</span>
                    </div>
                    <input type="file" name="receipt" id="receipt-input" accept="image/*" required>
                    <input type="submit" value="Upload Receipt">
                </form>
                <div id="receipt-uploaded">
                    <div id="receipt-image-container">
                        <img id="receipt-image" src="" alt="Receipt Image" style="display: none;">
                        <p id="receipt-image-text" style="display: none;"><i class='fa-solid fa-triangle-exclamation'></i>&nbsp; Automatically extracted transaction details might be inaccurate. <br/>Please verify the details before added the transaction.</p>
                    </div>
                </div>
                <div id="receipt-image-modal" class="modal">
                    <span class="close" id="receipt-image-modal-close">&times;</span>
                    <img class="modal-content" id="receipt-image-modal-image">
                </div>
            </div>
        </section>
        <section id="receipt-item-container">
            <div id="receipt-item-header">
                <h3>Receipt Items</h3>
            </div>
            <div id="receipt-item-list">
                <table id="receipt-item-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Item Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (!empty($formattedArray2)) {
                                foreach ($formattedArray2 as $item) {
                                    echo "<tr><td>" . htmlspecialchars($item[0]) . "</td><td>" . htmlspecialchars($item[1]) . "</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='2'>No items found</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transactionType = document.getElementById('transaction-type');
            const budgetSavingContainer = document.getElementById('budget-saving-container');
            const budgetSavingLabel = document.getElementById('budget-saving-label');
            const budgetSavingSelect = document.getElementById('budget-saving-select');
            const budgetSavingCheckbox = document.getElementById('budget-saving-checkbox');
            const sharedExpenseContainer = document.getElementById('shared-expense-container');

            // Function to update available budgets/savings
            function updateBudgetSavingOptions() {
                const type = transactionType.value;
                const select = document.getElementById('budget-saving-id');
                select.innerHTML = '<option value="">-- None --</option>';

                if (type === 'expense') {
                    // Fetch budgets
                    fetch('../FUNCTION/getBudgets.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.budgets) {
                                data.budgets.forEach(budget => {
                                    const option = document.createElement('option');
                                    option.value = budget.id;
                                    option.textContent = `${budget.name} (Limit: RM${budget.limit}, Used: RM${budget.used})`;
                                    select.appendChild(option);
                                });
                            }
                        });
                } else if (type === 'income') {
                    // Fetch saving goals
                    fetch('../FUNCTION/getSavingGoals.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.savings) {
                                data.savings.forEach(saving => {
                                    const option = document.createElement('option');
                                    option.value = saving.id;
                                    option.textContent = `${saving.name} (Target: RM${saving.target}, Current: RM${saving.current})`;
                                    select.appendChild(option);
                                });
                            }
                        });
                }
            }

            // Update label and options when transaction type changes
            transactionType.addEventListener('change', function() {
                const type = this.value;
                if (type === 'expense') {
                    budgetSavingLabel.textContent = 'Budget';
                    budgetSavingContainer.style.display = 'flex';
                    updateBudgetSavingOptions();
                } else if (type === 'income') {
                    budgetSavingLabel.textContent = 'Saving Goals';
                    budgetSavingContainer.style.display = 'flex';
                    updateBudgetSavingOptions();
                } else {
                    budgetSavingContainer.style.display = 'none';
                    budgetSavingSelect.style.display = 'none';
                }
            });

            // Show/hide select when checkbox is toggled
            budgetSavingCheckbox.addEventListener('change', function() {
                budgetSavingSelect.style.display = this.checked ? 'flex' : 'none';
                if (this.checked) {
                    updateBudgetSavingOptions();
                }
            });

            // Initial setup
            if (transactionType.value) {
                transactionType.dispatchEvent(new Event('change'));
            }

            function updateSharedExpenseVisibility() {
                if (transactionType.value === 'expense') {
                    sharedExpenseContainer.style.display = 'flex';
                } else {
                    sharedExpenseContainer.style.display = 'none';
                }
            }

            // Initial check
            updateSharedExpenseVisibility();

            // Listen for changes
            transactionType.addEventListener('change', updateSharedExpenseVisibility);
        });
        </script>
    </body>
</html>